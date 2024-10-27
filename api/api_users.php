<?php
include 'config.php';
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];
$request = @explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));


// Manejar la solicitud según el método y el endpoint
switch ($method) {
    case 'POST':
        if ($request[0] === 'register') {
            // Utiliza $_POST para obtener los datos en lugar de `php://input`
            if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirm_password'], $_FILES['profile_picture'])) {

                if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_FILES['profile_picture'])) {
                    $response["error"] = "Faltan campos o archivo no enviado";
                    echo json_encode($response);
                    exit;
                }

                // Validar que las contraseñas coincidan
                if ($_POST['password'] === $_POST['confirm_password']) {
                    $username = $conn->real_escape_string($_POST['username']);
                    $email = $conn->real_escape_string($_POST['email']);
                    $password = md5($_POST['password']); // Encriptar contraseña con md5

                    // Procesar la imagen de perfil
                    $profile_picture = $_FILES['profile_picture'];
                    $target_dir = "../uploads/";
                    $target_file = $target_dir . basename($profile_picture["name"]);

                    if (move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
                        $profile_picture_path = $target_file;
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al subir la foto de perfil"]);
                        exit;
                    }

                    // Verificar si el nombre de usuario o el email ya existen
                    $checkSQL = "SELECT * FROM usuarios WHERE nombre_usuario = '$username' OR email = '$email'";
                    $checkResult = $conn->query($checkSQL);

                    if ($checkResult->num_rows > 0) {
                        http_response_code(409); // Código 409: Conflicto
                        echo json_encode(["message" => "El nombre de usuario o el email ya están en uso"]);
                    } else {
                        // Insertar el usuario en la base de datos
                        $sql = "INSERT INTO usuarios (nombre_usuario, email, password, foto_perfil) VALUES ('$username', '$email', '$password', '$profile_picture_path')";

                        if ($conn->query($sql) === TRUE) {
                            http_response_code(201);
                            echo json_encode(["message" => "Usuario registrado exitosamente"]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["message" => "Error al registrar el usuario"]);
                        }
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Las contraseñas no coinciden"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Todos los campos son requeridos"]);
            }
        } elseif ($request[0] === 'login') {
            // Decodificar JSON y mostrarlo para depuración
            $data = json_decode(file_get_contents("php://input"), true);
            error_log("Datos JSON Decodificados: " . print_r($data, true));
        
            // Si $data es null, significa que no se pudo decodificar JSON
            if ($data === null) {
                echo json_encode(["message" => "Error al decodificar JSON"]);
                http_response_code(400);
                exit;
            }
        
            // Verificar que se hayan recibido email y password en $data
            if (empty($data['email']) || empty($data['password'])) {
                error_log("Campos faltantes en datos decodificados: " . print_r($data, true));
                echo json_encode(["message" => "Email y contraseña son requeridos"]);
                http_response_code(400);
                exit;
            }
        
            // Procesar el inicio de sesión con email y password
            $email = $conn->real_escape_string($data['email']);
            $password = md5($data['password']); // Encriptar la contraseña con md5
        
            // Verificar el usuario en la base de datos
            $sql = "SELECT * FROM usuarios WHERE email = '$email' AND password = '$password'";
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                // Generar un token y guardarlo en la base de datos
                $user = $result->fetch_assoc();
                $token = md5($user['email'] . $user['password'] . time()); // Generación de un token único
        
                // Guardar el token en la base de datos
                $updateTokenSQL = "UPDATE usuarios SET token = '$token' WHERE id = {$user['id']}";
                $conn->query($updateTokenSQL);
        
                http_response_code(200);
                echo json_encode(["message" => "Inicio de sesión exitoso", "token" => $token]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Credenciales incorrectas"]);
            }
        }        

        if ($request[0] === 'logout') {
            // Verificar que el token esté presente en el encabezado
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = $headers['Authorization'];

                // Verificar el token y eliminarlo
                $sql = "UPDATE usuarios SET token = NULL WHERE token = '$token'";
                $result = $conn->query($sql);

                if ($conn->affected_rows > 0) {
                    // Token eliminado exitosamente
                    http_response_code(200);
                    echo json_encode(["message" => "Sesión cerrada exitosamente"]);
                } else {
                    // Token no válido
                    http_response_code(401);
                    echo json_encode(["message" => "Token no válido o usuario no autenticado"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Token de autorización requerido"]);
            }
        }
        break;

    case 'GET':
        if ($request[0] === 'profile') {
            // Verificar que el token esté presente en el encabezado
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = $headers['Authorization'];

                // Buscar el usuario en la base de datos usando el token
                $sql = "SELECT nombre_usuario, email, foto_perfil FROM usuarios WHERE token = '$token'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    http_response_code(200);
                    echo json_encode(["message" => "Perfil obtenido exitosamente", "user" => $user]);
                } else {
                    http_response_code(401);
                    echo json_encode(["message" => "Token no válido o usuario no encontrado"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Token de autorización requerido"]);
            }
        } else {
            // Obtener todos los usuarios
            $sql = "SELECT id, nombre_usuario, email, foto_perfil, created_at FROM usuarios";
            $result = $conn->query($sql);

            $usuarios = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $usuarios[] = $row;
                }
            }
            echo json_encode($usuarios);
        }

        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

$conn->close();
?>