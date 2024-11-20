<?php
include 'config.php';
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];
$request = @explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
error_log(print_r($_POST, true));
error_log(print_r($_FILES, true));

// Manejar la solicitud según el método y el endpoint
switch ($method) {
    case 'POST':
        if ($request[0] === 'register') {

            error_log("Contenido de \$_POST:");
            error_log(print_r($_POST, true));
            error_log("Contenido de \$_FILES:");
            error_log(print_r($_FILES, true));

            if (!isset($_POST['nombre_usuario']) || !isset($_POST['email']) || !isset($_POST['contrasenia']) || !isset($_FILES['avatar'])) {
                $response = ["message" => "Faltan campos: "];
                $response["nombre_usuario"] = isset($_POST['nombre_usuario']) ? "Recibido" : "No recibido";
                $response["email"] = isset($_POST['email']) ? "Recibido" : "No recibido";
                $response["contrasenia"] = isset($_POST['contrasenia']) ? "Recibido" : "No recibido";
                $response["avatar"] = isset($_FILES['avatar']) ? "Recibido" : "No recibido";
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            // Verificar que todos los campos estén presentes
            if (isset($_POST['nombre_usuario'], $_POST['email'], $_POST['contrasenia'], $_POST['confirm_password'], $_FILES['avatar'])) {

                if (empty($_POST['nombre_usuario']) || empty($_POST['email']) || empty($_POST['contrasenia']) || empty($_FILES['avatar'])) {
                    echo json_encode(["message" => "Faltan campos o archivo no enviado"]);
                    http_response_code(400);
                    exit;
                }

                // Validar que las contraseñas coincidan
                if ($_POST['contrasenia'] !== $_POST['confirm_password']) {
                    echo json_encode(["message" => "Las contraseñas no coinciden"]);
                    http_response_code(400);
                    exit;
                }

                // Escapar datos y encriptar la contraseña
                $username = $conn->real_escape_string($_POST['nombre_usuario']);
                $email = $conn->real_escape_string($_POST['email']);
                $password = md5($_POST['contrasenia']); // Encriptar contraseña con md5

                // Procesar la imagen de perfil
                $profile_picture = $_FILES['avatar'];
                $target_dir = "../uploads/";
                $target_file = $target_dir . basename($profile_picture["name"]);

                if (!move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al subir la foto de perfil"]);
                    exit;
                }

                $profile_picture_path = $target_file;

                // Verificar si el nombre de usuario o el email ya existen
                $checkSQL = "SELECT * FROM usuario WHERE nombre_usuario = '$username' OR email = '$email'";
                $checkResult = $conn->query($checkSQL);

                if ($checkResult->num_rows > 0) {
                    http_response_code(409); // Código 409: Conflicto
                    echo json_encode(["message" => "El nombre de usuario o el email ya están en uso"]);
                } else {
                    // Insertar el usuario en la base de datos
                    $stmt = $conn->prepare("INSERT INTO usuario (nombre_usuario, email, contrasenia, avatar) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $email, $password, $profile_picture_path);

                    if ($stmt->execute()) {
                        http_response_code(201);
                        echo json_encode(["message" => "Usuario registrado exitosamente"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al registrar el usuario"]);
                    }

                    $stmt->close();
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Todos los campos son requeridos"]);
            }
        } elseif ($request[0] === 'login') {
            header("Content-Type: application/json");

            // Decodificar JSON y mostrarlo para depuración
            $data = json_decode(file_get_contents("php://input"), true);

            if ($data === null) {
                echo json_encode(["message" => "Error al decodificar JSON"]);
                http_response_code(400);
                exit;
            }

            // Verificar que se hayan recibido email y password en $data
            if (empty($data['email']) || empty($data['password'])) {
                echo json_encode(["message" => "Email y contraseña son requeridos"]);
                http_response_code(400);
                exit;
            }

            // Procesar el inicio de sesión con email y password
            $email = $conn->real_escape_string($data['email']);
            $password = md5($data['password']); // Encriptar la contraseña con md5

            // Verificar el usuario en la base de datos
            $sql = "SELECT * FROM usuario WHERE email = '$email' AND contrasenia = '$password'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id_usuario'];
                $nombre_usuario = $user['nombre_usuario']; // Obtener el nombre de usuario
                $avatar = $user['avatar']; // Obtener el avatar

                // Generar tokens de acceso y refresco
                $access_token = bin2hex(random_bytes(32));
                $refresh_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Guardar los tokens en la base de datos
                $stmt = $conn->prepare("INSERT INTO oauth_tokens (user_id, access_token, refresh_token, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $access_token, $refresh_token, $expires_at);

                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode([
                        "message" => "Inicio de sesión exitoso",
                        "access_token" => $access_token,
                        "refresh_token" => $refresh_token,
                        "expires_at" => $expires_at,
                        "usuario_id" => $user_id,
                        "nombre_usuario" => $nombre_usuario, // Incluir el nombre de usuario en la respuesta
                        "avatar" => $avatar // Incluir el avatar en la respuesta
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al generar los tokens"]);
                }

                $stmt->close();
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Credenciales incorrectas"]);
            }
            exit;
        }

        if ($request[0] === 'refresh') {
            header("Content-Type: application/json");

            // Decodificar los datos de entrada para obtener el refresh token
            $data = json_decode(file_get_contents("php://input"), true);

            // Verificar si el refresh token está presente
            if (empty($data['refresh_token'])) {
                echo json_encode(["message" => "Refresh token es requerido"]);
                http_response_code(400);
                exit;
            }

            $refresh_token = $data['refresh_token'];

            // Buscar el refresh token en la base de datos para verificar su validez
            $sql = "SELECT user_id FROM oauth_tokens WHERE refresh_token = '$refresh_token' AND expires_at > NOW()";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];

                // Generar un nuevo access token y fecha de expiración
                $new_access_token = bin2hex(random_bytes(32)); // Nuevo access token
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Nueva fecha de expiración (1 hora)

                // Actualizar el access token en la base de datos
                $updateSQL = "UPDATE oauth_tokens SET access_token = '$new_access_token', expires_at = '$expires_at' WHERE user_id = $user_id";
                $conn->query($updateSQL);

                // Devolver el nuevo access token y su fecha de expiración
                echo json_encode([
                    "access_token" => $new_access_token,
                    "expires_at" => $expires_at
                ]);
            } else {
                // Responder con error si el refresh token es inválido o ha expirado
                http_response_code(401);
                echo json_encode(["message" => "Refresh token inválido o expirado"]);
            }
            exit;
        }

        if ($request[0] === 'logout') {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);

                $sql = "DELETE FROM oauth_tokens WHERE access_token = '$token'";
                if ($conn->query($sql) === TRUE) {
                    http_response_code(200);
                    echo json_encode(["message" => "Sesión cerrada exitosamente"]);
                } else {
                    http_response_code(401);
                    echo json_encode(["message" => "Token no válido o usuario no autenticado"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Token de autorización requerido"]);
            }
        }

        if ($request[0] === 'validate_session') {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);

                $stmt = $conn->prepare("SELECT user_id FROM oauth_tokens WHERE access_token = ? AND expires_at > NOW()");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    http_response_code(200);
                    echo json_encode(["success" => true, "message" => "Token válido"]);
                } else {
                    http_response_code(401);
                    echo json_encode(["success" => false, "message" => "Token inválido o expirado"]);
                }
                $stmt->close();
            } else {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Token no proporcionado"]);
            }
        }

        if ($request[0] === 'update_profile') {
            $headers = getallheaders();
            $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

            $sql = "SELECT user_id FROM oauth_tokens WHERE access_token = '$token' AND expires_at > NOW()";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['user_id'];

                $nombre_usuario = $_POST['nombre_usuario'] ?? null;
                $email = $_POST['email'] ?? null;
                $bio = $_POST['bio'] ?? null;
                $password = isset($_POST['password']) ? md5($_POST['password']) : null;

                // Verificar si el nombre de usuario ya está en uso por otro usuario
                if ($nombre_usuario) {
                    $username_check_sql = "SELECT id_usuario FROM usuario WHERE nombre_usuario = '$nombre_usuario' AND id_usuario != $user_id";
                    $username_check_result = $conn->query($username_check_sql);
                    if ($username_check_result->num_rows > 0) {
                        echo json_encode(["message" => "El nombre de usuario ya está en uso"]);
                        http_response_code(409);
                        exit;
                    }
                }

                // Verificar si el correo ya está en uso por otro usuario
                if ($email) {
                    $email_check_sql = "SELECT id_usuario FROM usuario WHERE email = '$email' AND id_usuario != $user_id";
                    $email_check_result = $conn->query($email_check_sql);
                    if ($email_check_result->num_rows > 0) {
                        echo json_encode(["message" => "El correo electrónico ya está en uso"]);
                        http_response_code(409);
                        exit;
                    }
                }

                // Procesar la imagen de perfil, si se cargó
                $profile_picture_path = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "../uploads/";
                    $target_file = $target_dir . basename($_FILES["avatar"]["name"]);
                    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                        $profile_picture_path = $target_file;
                    }
                }

                // Construir la consulta de actualización
                $update_fields = [];
                if ($nombre_usuario)
                    $update_fields[] = "nombre_usuario = '$nombre_usuario'";
                if ($email)
                    $update_fields[] = "email = '$email'";
                if ($bio)
                    $update_fields[] = "bio = '$bio'";
                if ($password)
                    $update_fields[] = "contrasenia = '$password'";
                if ($profile_picture_path)
                    $update_fields[] = "avatar = '$profile_picture_path'";

                if (!empty($update_fields)) {
                    $update_sql = "UPDATE usuario SET " . implode(', ', $update_fields) . " WHERE id_usuario = $user_id";
                    if ($conn->query($update_sql) === TRUE) {
                        echo json_encode(["message" => "Perfil actualizado exitosamente", "status" => "success"]);
                    } else {
                        echo json_encode(["message" => "Error al actualizar el perfil", "status" => "error"]);
                        http_response_code(500);
                    }
                } else {
                    echo json_encode(["message" => "No se proporcionaron datos para actualizar", "status" => "no_change"]);
                    http_response_code(400);
                }
            } else {
                echo json_encode(["message" => "Token inválido o sesión expirada"]);
                http_response_code(401);
            }
        }

        break;

    case 'GET':
        if ($request[0] === 'profile') {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
                $sql = "SELECT u.nombre_usuario, u.email, u.avatar 
                            FROM usuario u 
                            JOIN oauth_tokens t ON u.id_usuario = t.user_id 
                            WHERE t.access_token = '$token' AND t.expires_at > NOW()";
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
            exit;

        } elseif ($request[0] === 'search_users' && isset($_GET['search']) && isset($_GET['user_id'])) {
            // Búsqueda de usuarios
            $search = $conn->real_escape_string($_GET['search']);
            $user_id = intval($_GET['user_id']);

            $sql = "SELECT id_usuario, nombre_usuario, avatar 
                    FROM usuario 
                    WHERE nombre_usuario LIKE '%$search%' 
                    AND id_usuario != $user_id 
                    LIMIT 10";
            $result = $conn->query($sql);

            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }

            // Retornar un array vacío si no se encuentran usuarios
            echo json_encode($usuarios);
            exit;
        } elseif ($request[0] === 'all_users') {  // Este endpoint es un ejemplo, ajusta si necesitas otra consulta general
            // Obtener todos los usuarios
            $sql = "SELECT id_usuario, nombre_usuario, email, avatar FROM usuario";
            $result = $conn->query($sql);

            $usuarios = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $usuarios[] = $row;
                }
            }

            echo json_encode($usuarios);
            exit;
        }
        break;

    case 'DELETE':
        // Verificar si el endpoint es 'delete_all_users'
        if ($request[0] === 'delete_all_users') {
            // Ejecutar la consulta para eliminar todos los usuarios
            $sql = "DELETE FROM usuario";
            if ($conn->query($sql) === TRUE) {
                http_response_code(200);
                echo json_encode(["message" => "Todos los usuarios han sido eliminados"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar usuarios"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Endpoint no válido para DELETE"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

$conn->close();
?>