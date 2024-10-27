<?php
include 'config.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

// Manejar la solicitud según el método y el endpoint
switch ($method) {
    case 'POST':
        if ($request[0] === 'register') {
            // Endpoint para registro de usuarios
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['username'], $data['email'], $data['password'], $data['confirm_password'])) {
                // Validar que las contraseñas coincidan
                if ($data['password'] === $data['confirm_password']) {
                    $username = $conn->real_escape_string($data['username']);
                    $email = $conn->real_escape_string($data['email']);
                    $password = md5($data['password']); // Encriptar contraseña con md5
                    $profile_picture = isset($data['profile_picture']) ? $conn->real_escape_string($data['profile_picture']) : null;
    
                    // Verificar si el nombre de usuario o el email ya existen
                    $checkSQL = "SELECT * FROM usuarios WHERE nombre_usuario = '$username' OR email = '$email'";
                    $checkResult = $conn->query($checkSQL);
    
                    if ($checkResult->num_rows > 0) {
                        http_response_code(409); // Código 409: Conflicto
                        echo json_encode(["message" => "El nombre de usuario o el email ya están en uso"]);
                    } else {
                        // Insertar el usuario en la base de datos
                        $sql = "INSERT INTO usuarios (nombre_usuario, email, password, foto_perfil) VALUES ('$username', '$email', '$password', '$profile_picture')";
                        
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
            // Endpoint para inicio de sesión
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['email'], $data['password'])) {
                $email = $conn->real_escape_string($data['email']);
                $password = md5($data['password']); // Encriptar la contraseña con md5
    
                // Verificar el usuario en la base de datos
                $sql = "SELECT * FROM usuarios WHERE email = '$email' AND password = '$password'";
                $result = $conn->query($sql);
    
                if ($result->num_rows > 0) {
                    // Generar un token y guardarlo en la base de datos
                    $user = $result->fetch_assoc();
                    $token = md5($user['email'] . $user['password'] . time()); // Token único
                    
                    // Guardar el token en la base de datos
                    $updateTokenSQL = "UPDATE usuarios SET token = '$token' WHERE id = {$user['id']}";
                    $conn->query($updateTokenSQL);
    
                    http_response_code(200);
                    echo json_encode(["message" => "Inicio de sesión exitoso", "token" => $token]);
                } else {
                    http_response_code(401);
                    echo json_encode(["message" => "Credenciales incorrectas"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Email y contraseña son requeridos"]);
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
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

$conn->close();
?>