<?php

try {
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
                    $sql = "INSERT INTO seguimiento (id_seguidor, id_seguido) VALUES ($usuario_id, $seguido_id)";

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
                    $sql = "DELETE FROM seguimiento WHERE id_seguidor = ? AND id_seguido = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ii', $usuario_id, $seguido_id);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        http_response_code(200);
                        echo json_encode(["message" => "Dejaste de seguir al usuario"]);
                    } else {
                        http_response_code(400); // Cambia el código a 400 para errores esperados
                        echo json_encode(["message" => "No se pudo dejar de seguir al usuario o ya no lo sigues"]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Datos incompletos para dejar de seguir al usuario"]);
                }
                exit;
            }
            break;

        case 'GET':

            if (isset($request[0]) && $request[0] === 'check_follow_status' && isset($_GET['follower_id'], $_GET['followed_id'])) {
                $follower_id = intval($_GET['follower_id']);
                $followed_id = intval($_GET['followed_id']);

                // Consultar si existe la relación de seguimiento
                $sql = "SELECT COUNT(*) AS isFollowing FROM seguimiento WHERE id_seguidor = ? AND id_seguido = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $follower_id, $followed_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                // Devolver el estado de seguimiento
                echo json_encode(["isFollowing" => $result['isFollowing'] > 0]);
                exit; // Detener ejecución aquí
            }

            // Verifica si el 'usuario_id' está presente en la URL
            if (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);  // Asegura que el usuario_id sea un número entero

                // Contar seguidores y seguidos
                if (isset($request[0]) && $request[0] === 'count_followers') {
                    $followers_sql = "SELECT COUNT(*) AS followers FROM seguimiento WHERE id_seguido = ?";
                    $stmt = $conn->prepare($followers_sql);
                    $stmt->bind_param('i', $usuario_id);
                    $stmt->execute();
                    $followers_count = $stmt->get_result()->fetch_assoc()['followers'] ?? 0;

                    $following_sql = "SELECT COUNT(*) AS following FROM seguimiento WHERE id_seguidor = ?";
                    $stmt = $conn->prepare($following_sql);
                    $stmt->bind_param('i', $usuario_id);
                    $stmt->execute();
                    $following_count = $stmt->get_result()->fetch_assoc()['following'] ?? 0;

                    echo json_encode([
                        "followers" => $followers_count,
                        "following" => $following_count
                    ]);
                }

                // Contar publicaciones
                elseif (isset($request[0]) && $request[0] === 'count_posts') {
                    // Consulta combinada para contar publicaciones, eventos y foros
                    $publicaciones_sql = "
                        SELECT 
                            (SELECT COUNT(*) FROM publicacion WHERE id_usuario = ?) AS total_posts,
                            (SELECT COUNT(*) FROM evento WHERE id_usuario = ?) AS total_events,
                            (SELECT COUNT(*) FROM foro WHERE usuario_id = ?) AS total_forums
                    ";

                    $stmt = $conn->prepare($publicaciones_sql);
                    $stmt->bind_param('iii', $usuario_id, $usuario_id, $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();

                    // Calcular el total de publicaciones
                    $totalPosts = $result['total_posts'] ?? 0;
                    $totalEvents = $result['total_events'] ?? 0;
                    $totalForums = $result['total_forums'] ?? 0;
                    $totalPublicaciones = $totalPosts + $totalEvents + $totalForums;

                    // Devolver la respuesta en JSON
                    echo json_encode([
                        "Publicaciones total" => $totalPublicaciones,
                        "Detalles" => [
                            "posts" => $totalPosts,
                            "events" => $totalEvents,
                            "forums" => $totalForums
                        ]
                    ]);
                }

                // Listar seguidores
                elseif (isset($request[0]) && $request[0] === 'list_followers') {
                    $followers_sql = "SELECT u.nombre_usuario, u.avatar 
                                        FROM seguimiento s
                                        JOIN usuario u ON s.id_seguidor = u.id_usuario
                                        WHERE s.id_seguido = ?";
                    $stmt = $conn->prepare($followers_sql);
                    $stmt->bind_param('i', $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $followers = [];
                    while ($row = $result->fetch_assoc()) {
                        $followers[] = $row;
                    }
                    echo json_encode(["followers" => $followers]);
                }

                // Listar seguidos
                elseif (isset($request[0]) && $request[0] === 'list_following') {
                    $following_sql = "SELECT u.nombre_usuario, u.avatar 
                                          FROM seguimiento s
                                          JOIN usuario u ON s.id_seguido = u.id_usuario
                                          WHERE s.id_seguidor = ?";
                    $stmt = $conn->prepare($following_sql);
                    $stmt->bind_param('i', $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $following = [];
                    while ($row = $result->fetch_assoc()) {
                        $following[] = $row;
                    }
                    echo json_encode(["following" => $following]);
                }

                // Obtener todas las publicaciones del usuario
                elseif (isset($request[0]) && $request[0] === 'all_posts') {
                    $sql = "
                    (
                        SELECT 
                            p.id_publi AS id, 
                            p.contenido_texto AS contenido, 
                            p.multimedia_url AS imagen, 
                            p.fecha__hora_creacion AS fecha_hora, 
                            p.fecha__hora_creacion AS created_at, -- Incluimos created_at como en las otras subconsultas
                            NULL AS ubicacion, 
                            NULL AS nombre_evento, -- Estas columnas no aplican a publicaciones
                            NULL AS titulo,
                            u.nombre_usuario, 
                            u.avatar, 
                            'post' AS type
                        FROM publicacion p
                        JOIN usuario u ON p.id_usuario = u.id_usuario
                        WHERE p.id_usuario = ?
                    )
                    UNION ALL
                    (
                        SELECT 
                            f.id AS id, 
                            f.contenido AS contenido, 
                            NULL AS imagen, 
                            f.created_at AS fecha_hora, 
                            f.created_at AS created_at, -- Incluimos created_at como en las otras subconsultas
                            NULL AS ubicacion, 
                            NULL AS nombre_evento, 
                            f.titulo AS titulo, -- Nombre del foro
                            u.nombre_usuario, 
                            u.avatar, 
                            'forum' AS type
                        FROM foro f
                        JOIN usuario u ON f.usuario_id = u.id_usuario
                        WHERE f.usuario_id = ?
                    )
                    UNION ALL
                    (
                        SELECT 
                            e.id_evento AS id, 
                            e.descripcion AS contenido, 
                            NULL AS imagen, 
                            e.fecha_hora_evento AS fecha_hora, -- Hora del evento establecido por el usuario
                            e.created_at AS created_at, 
                            e.ubicacion AS ubicacion, -- Incluimos la ubicación
                            e.nombre_evento AS nombre_evento, -- Nombre del evento
                            NULL AS titulo, -- No aplica a eventos
                            u.nombre_usuario, 
                            u.avatar, 
                            'event' AS type
                        FROM evento e
                        JOIN usuario u ON e.id_usuario = u.id_usuario
                        WHERE e.id_usuario = ?
                    )
                    ORDER BY created_at DESC;
                    ";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('iii', $usuario_id, $usuario_id, $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $publicaciones = [];
                    while ($row = $result->fetch_assoc()) {
                        $row['type'] = strtolower($row['type']); // Aseguramos que el tipo sea en minúsculas
                        if (!empty($row['imagen'])) {
                            $imageUrls = json_decode($row['imagen'], true);

                            if (is_array($imageUrls)) {
                                foreach ($imageUrls as &$imageUrl) {
                                    if (is_string($imageUrl) && strpos($imageUrl, "/Wheelz/uploads/posts/") !== 0) {
                                        $imageUrl = "/Wheelz/uploads/posts/" . ltrim($imageUrl, '/');
                                    }
                                }
                                $row['imagen'] = $imageUrls;
                            } else {
                                if (is_string($row['imagen']) && strpos($row['imagen'], "/Wheelz/uploads/posts/") !== 0) {
                                    $row['imagen'] = "/Wheelz/uploads/posts/" . ltrim($row['imagen'], '/');
                                }
                            }
                        }

                        if (!empty($row['avatar'])) {
                            $row['avatar'] = str_replace("../", "", $row['avatar']);
                            if (strpos($row['avatar'], "uploads/") === 0) {
                                $row['avatar'] = "/Wheelz/" . $row['avatar'];
                            } elseif (strpos($row['avatar'], "/Wheelz/uploads/") !== 0) {
                                $row['avatar'] = "/Wheelz/uploads/" . ltrim($row['avatar'], '/');
                            }
                        }

                        $publicaciones[] = $row;
                    }

                    echo json_encode(["publicaciones" => $publicaciones]);
                }

                // Obtener perfil de usuario
                elseif (isset($request[0]) && $request[0] === 'user_profile') {
                    $profile_sql = "SELECT nombre_usuario, avatar, bio AS biografia FROM usuario WHERE id_usuario = ?";
                    $stmt = $conn->prepare($profile_sql);
                    $stmt->bind_param('i', $usuario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $profile = $result->fetch_assoc();
                        $profile['avatar'] = strpos($profile['avatar'], 'http') === 0 ? $profile['avatar'] : "../uploads/" . ltrim($profile['avatar'], '/');
                        echo json_encode(["user" => $profile]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Usuario no encontrado"]);
                    }
                }

                // Obtener usuarios por ID
                elseif (isset($request[0]) && $request[0] === 'get_users_by_ids' && isset($_GET['user_ids'])) {
                    $userIds = explode(',', $_GET['user_ids']);
                    $userIds = array_map('intval', $userIds);

                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    $stmt = $conn->prepare("SELECT id_usuario, nombre_usuario, avatar FROM usuario WHERE id_usuario IN ($placeholders)");
                    $types = str_repeat('i', count($userIds));
                    $stmt->bind_param($types, ...$userIds);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $users = [];
                    while ($row = $result->fetch_assoc()) {
                        $row['avatar'] = strpos($row['avatar'], 'http') === 0 ? $row['avatar'] : "../uploads/" . ltrim($row['avatar'], '/');
                        $users[] = $row;
                    }

                    echo json_encode(["users" => $users]);
                }

            } else {
                http_response_code(400);
                echo json_encode(["message" => "Usuario no especificado"]);
            }
            break;


        default:
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido"]);
            break;
    }

    // Cerrar la conexión a la base de datos
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>