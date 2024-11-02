<?php
include 'config.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$request = @explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

switch ($method) {
    case 'POST':
        if (isset($request[0]) && $request[0] === 'follow') {
            // Endpoint para seguir a un usuario
            $data = json_decode(file_get_contents("php://input"), true);

            if (isset($data['usuario_id'], $data['seguido_id'])) {
                $usuario_id = intval($data['usuario_id']);
                $seguido_id = intval($data['seguido_id']);

                // Insertar la relación de seguimiento
                $sql = "INSERT INTO seguidores (usuario_id, seguido_id) VALUES ($usuario_id, $seguido_id)";

                if ($conn->query($sql) === TRUE) {
                    http_response_code(201);
                    echo json_encode(["message" => "Usuario seguido exitosamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al seguir al usuario o ya lo sigues"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para seguir al usuario"]);
            }
        }
        break;

    case 'DELETE':
        if (isset($request[0]) && $request[0] === 'unfollow') {
            // Endpoint para dejar de seguir a un usuario
            $data = json_decode(file_get_contents("php://input"), true);

            if (isset($data['usuario_id'], $data['seguido_id'])) {
                $usuario_id = intval($data['usuario_id']);
                $seguido_id = intval($data['seguido_id']);

                // Eliminar la relación de seguimiento
                $sql = "DELETE FROM seguidores WHERE usuario_id = $usuario_id AND seguido_id = $seguido_id";

                if ($conn->query($sql) === TRUE && $conn->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode(["message" => "Dejaste de seguir al usuario"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al dejar de seguir al usuario o no lo sigues"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para dejar de seguir al usuario"]);
            }
        }
        break;

    case 'GET':
        if (isset($request[0]) && $request[0] === 'count_followers') {
            // Obtener el conteo de seguidores y seguidos para un usuario
            if (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);

                // Contar seguidores
                $followers_sql = "SELECT COUNT(*) AS followers FROM seguidores WHERE seguido_id = $usuario_id";
                $followers_result = $conn->query($followers_sql);
                $followers_count = $followers_result->fetch_assoc()['followers'];

                // Contar seguidos
                $following_sql = "SELECT COUNT(*) AS following FROM seguidores WHERE usuario_id = $usuario_id";
                $following_result = $conn->query($following_sql);
                $following_count = $following_result->fetch_assoc()['following'];

                echo json_encode([
                    "followers" => $followers_count,
                    "following" => $following_count
                ]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Usuario no especificado"]);
            }
        } elseif (isset($request[0]) && $request[0] === 'count_posts') {
            // Obtener el conteo de publicaciones de un usuario, dividido en posts, eventos y foros
            if (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);

                $posts_sql = "SELECT COUNT(*) AS posts FROM posts WHERE usuario_id = $usuario_id";
                $posts_count = $conn->query($posts_sql)->fetch_assoc()['posts'];

                $eventos_sql = "SELECT COUNT(*) AS eventos FROM eventos WHERE usuario_id = $usuario_id";
                $eventos_count = $conn->query($eventos_sql)->fetch_assoc()['eventos'];

                $foros_sql = "SELECT COUNT(*) AS foros FROM foros WHERE usuario_id = $usuario_id";
                $foros_count = $conn->query($foros_sql)->fetch_assoc()['foros'];

                $total = $posts_count + $eventos_count + $foros_count;

                echo json_encode([
                    "posts" => $posts_count,
                    "eventos" => $eventos_count,
                    "foros" => $foros_count,
                    "Publicaciones total" => $total
                ]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Usuario no especificado"]);
            }
        } elseif (isset($request[0]) && $request[0] === 'list_followers') {
            // Obtener la lista de seguidores de un usuario
            if (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);

                $followers_sql = "SELECT u.nombre_usuario, u.foto_perfil 
                                      FROM seguidores s
                                      JOIN usuarios u ON s.usuario_id = u.id
                                      WHERE s.seguido_id = $usuario_id";
                $result = $conn->query($followers_sql);
                $followers = [];

                while ($row = $result->fetch_assoc()) {
                    $followers[] = $row;
                }

                echo json_encode(["followers" => $followers]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Usuario no especificado"]);
            }
        } elseif (isset($request[0]) && $request[0] === 'list_following') {
            // Obtener la lista de usuarios seguidos por un usuario
            if (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);

                $following_sql = "SELECT u.nombre_usuario, u.foto_perfil 
                                      FROM seguidores s
                                      JOIN usuarios u ON s.seguido_id = u.id
                                      WHERE s.usuario_id = $usuario_id";
                $result = $conn->query($following_sql);
                $following = [];

                while ($row = $result->fetch_assoc()) {
                    $following[] = $row;
                }

                echo json_encode(["following" => $following]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Usuario no especificado"]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

// Cerrar la conexión a la base de datos
$conn->close();
?>