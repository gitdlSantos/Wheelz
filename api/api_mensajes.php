<?php
include 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$request = @explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));


switch ($method) {
    case 'POST':
        if (!empty($request[0]) && $request[0] === 'mensaje') {
            // Enviar un nuevo mensaje
            $data = json_decode(file_get_contents("php://input"), true);

            if (isset($data['id_usuario'], $data['contenido'], $data['es_grupo'])) {
                $id_usuario = intval($data['id_usuario']);
                $contenido = $conn->real_escape_string($data['contenido']);
                $es_grupo = intval($data['es_grupo']); // 1 si es mensaje de grupo, 0 si es directo

                // Insertar el mensaje en la tabla `mensaje`
                $sql = "INSERT INTO mensaje (id_usuario, contenido, es_grupo) VALUES ($id_usuario, '$contenido', $es_grupo)";

                if ($conn->query($sql) === TRUE) {
                    $id_mensaje = $conn->insert_id;

                    if ($es_grupo && isset($data['id_grupo'])) {
                        // Si es un mensaje de grupo
                        $id_grupo = intval($data['id_grupo']);
                        $sql_grupo = "INSERT INTO mensaje_grupo (id_mensaje, id_grupo) VALUES ($id_mensaje, $id_grupo)";
                        $conn->query($sql_grupo);
                    } elseif (!$es_grupo && isset($data['receptor_id'])) {
                        // Si es un mensaje directo
                        $receptor_id = intval($data['receptor_id']);
                        $sql_directo = "INSERT INTO mensaje_directo (id_mensaje, id_usuario) VALUES ($id_mensaje, $receptor_id)";
                        $conn->query($sql_directo);
                    }
                    http_response_code(201);
                    echo json_encode(["message" => "Mensaje enviado"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al enviar el mensaje"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para enviar el mensaje"]);
            }
        }
        // Nuevo caso para crear un grupo
        elseif (!empty($request[0]) && $request[0] === 'crear_grupo') {
            // Crear un nuevo grupo
            $data = json_decode(file_get_contents("php://input"), true);

            if (isset($data['nombre'], $data['user_ids']) && is_array($data['user_ids']) && count($data['user_ids']) >= 2) {
                $nombre_grupo = $conn->real_escape_string($data['nombre']);
                $user_ids = array_map('intval', $data['user_ids']);
                $id_usuario_creador = intval($user_ids[0]);  // El primer usuario en la lista es el creador

                // Insertar el grupo en la tabla `grupo`
                $sql_grupo = "INSERT INTO grupo (id_usuario, nombre_grupo, descripcion) VALUES ($id_usuario_creador, '$nombre_grupo', 'Descripción del grupo')";
                if ($conn->query($sql_grupo) === TRUE) {
                    $id_grupo = $conn->insert_id;

                    // Agregar cada usuario al grupo en la tabla `miembro_grupo`
                    foreach ($user_ids as $index => $user_id) {
                        // Asignar el rol de "administrador" al creador y "miembro" al resto
                        $rol = ($index === 0) ? 'admin' : 'miembro';
                        $sql_miembro = "INSERT INTO miembro_grupo (id_grupo, id_usuario, rol) VALUES ($id_grupo, $user_id, '$rol')";
                        $conn->query($sql_miembro);
                    }

                    http_response_code(201);
                    echo json_encode([
                        "success" => true,
                        "group" => [
                            "id" => $id_grupo,
                            "nombre" => $nombre_grupo
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al crear el grupo"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos o incorrectos para crear el grupo"]);
            }
        }

        break;

    case 'GET':
        if (!empty($request[0]) && $request[0] === 'conversations' && isset($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $conversations = [];

            // Consulta para mensajes directos
            $sql_directo = "
    SELECT DISTINCT 
        CASE 
            WHEN m.id_usuario = $user_id THEN md.id_usuario
            ELSE m.id_usuario 
        END AS conversation_user_id,
        CASE 
            WHEN m.id_usuario = $user_id THEN u.nombre_usuario 
            ELSE u2.nombre_usuario 
        END AS nombre_usuario,
        CASE 
            WHEN m.id_usuario = $user_id THEN u.avatar 
            ELSE u2.avatar 
        END AS avatar,
        0 AS es_grupo,
        (SELECT contenido 
         FROM mensaje m2
         JOIN mensaje_directo md2 ON m2.id_mensaje = md2.id_mensaje
         WHERE (m2.id_usuario = $user_id AND md2.id_usuario = conversation_user_id) 
         OR (m2.id_usuario = conversation_user_id AND md2.id_usuario = $user_id)
         ORDER BY m2.fecha_hora DESC 
         LIMIT 1) AS ultimo_mensaje,
        (SELECT fecha_hora 
         FROM mensaje m2
         JOIN mensaje_directo md2 ON m2.id_mensaje = md2.id_mensaje
         WHERE (m2.id_usuario = $user_id AND md2.id_usuario = conversation_user_id) 
         OR (m2.id_usuario = conversation_user_id AND md2.id_usuario = $user_id)
         ORDER BY m2.fecha_hora DESC 
         LIMIT 1) AS ultimo_mensaje_fecha
    FROM mensaje_directo md
    JOIN mensaje m ON m.id_mensaje = md.id_mensaje
    JOIN usuario u ON md.id_usuario = u.id_usuario
    LEFT JOIN usuario u2 ON m.id_usuario = u2.id_usuario
    WHERE (md.id_usuario = $user_id OR m.id_usuario = $user_id)
";

            $sql_grupo = "
    SELECT 
        g.id_grupo AS conversation_user_id,
        g.nombre_grupo AS nombre_usuario,
        NULL AS avatar,
        1 AS es_grupo,
        (SELECT contenido 
         FROM mensaje m
         JOIN mensaje_grupo mg ON m.id_mensaje = mg.id_mensaje
         WHERE mg.id_grupo = g.id_grupo
         ORDER BY m.fecha_hora DESC 
         LIMIT 1) AS ultimo_mensaje,
        (SELECT fecha_hora 
         FROM mensaje m
         JOIN mensaje_grupo mg ON m.id_mensaje = mg.id_mensaje
         WHERE mg.id_grupo = g.id_grupo
         ORDER BY m.fecha_hora DESC 
         LIMIT 1) AS ultimo_mensaje_fecha
    FROM grupo g
    JOIN miembro_grupo mg ON g.id_grupo = mg.id_grupo
    WHERE mg.id_usuario = $user_id
";

            $sql = "($sql_directo) UNION ($sql_grupo) ORDER BY ultimo_mensaje_fecha DESC";

            $result = $conn->query($sql);
            $conversations = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $conversations[] = $row;
                }
            }
            echo json_encode($conversations);
            exit;
        } elseif (!empty($request[0]) && $request[0] === 'mensajes' && isset($_GET['conversation_id'])) {
            // Obtener todos los mensajes de una conversación específica
            $conversation_id = intval($_GET['conversation_id']);
            $mensajes = [];

            // Consulta para mensajes directos de una conversación específica
            $sql_directos = "
                SELECT m.id_mensaje, m.contenido, m.fecha_hora, m.id_usuario, u.nombre_usuario 
                FROM mensaje m 
                JOIN mensaje_directo md ON m.id_mensaje = md.id_mensaje 
                JOIN usuario u ON m.id_usuario = u.id_usuario 
                WHERE (m.id_usuario = $conversation_id OR md.id_usuario = $conversation_id)
                ORDER BY m.fecha_hora ASC
            ";

            $result_directos = $conn->query($sql_directos);
            while ($row = $result_directos->fetch_assoc()) {
                $mensajes[] = array_merge($row, ["es_grupo" => 0]);
            }

            // Consulta para mensajes de grupo de una conversación específica
            $sql_grupos = "SELECT m.id_mensaje, m.contenido, m.fecha_hora, u.nombre_usuario, g.nombre_grupo 
                           FROM mensaje m 
                           JOIN mensaje_grupo mg ON m.id_mensaje = mg.id_mensaje 
                           JOIN grupo g ON mg.id_grupo = g.id_grupo 
                           JOIN usuario u ON m.id_usuario = u.id_usuario 
                           WHERE mg.id_grupo = $conversation_id
                           ORDER BY m.fecha_hora ASC";

            $result_grupos = $conn->query($sql_grupos);
            while ($row = $result_grupos->fetch_assoc()) {
                $mensajes[] = array_merge($row, ["es_grupo" => 1]);
            }

            echo json_encode(["mensajes" => $mensajes]);

            exit;

        } elseif (!empty($request[0]) && $request[0] === 'mensajes' && isset($_GET['id_usuario'])) {
            // Obtener todos los mensajes directos y de grupo de un usuario
            $id_usuario = intval($_GET['id_usuario']);
            $mensajes = [];

            // Obtener mensajes directos
            $sql_directos = "SELECT m.id_mensaje, m.contenido, m.fecha_hora, u.nombre_usuario 
                                 FROM mensaje m 
                                 JOIN mensaje_directo md ON m.id_mensaje = md.id_mensaje 
                                 JOIN usuario u ON m.id_usuario = u.id_usuario 
                                 WHERE md.id_usuario = $id_usuario";
            $result_directos = $conn->query($sql_directos);
            while ($row = $result_directos->fetch_assoc()) {
                $mensajes[] = array_merge($row, ["es_grupo" => 0]);
            }

            // Obtener mensajes de grupo
            $sql_grupos = "SELECT m.id_mensaje, m.contenido, m.fecha_hora, u.nombre_usuario, g.nombre_grupo 
                               FROM mensaje m 
                               JOIN mensaje_grupo mg ON m.id_mensaje = mg.id_mensaje 
                               JOIN grupo g ON mg.id_grupo = g.id_grupo 
                               JOIN usuario u ON m.id_usuario = u.id_usuario 
                               JOIN miembro_grupo mbr ON mbr.id_usuario = $id_usuario AND mbr.id_grupo = g.id_grupo";
            $result_grupos = $conn->query($sql_grupos);
            while ($row = $result_grupos->fetch_assoc()) {
                $mensajes[] = array_merge($row, ["es_grupo" => 1]);
            }
            echo json_encode(["mensajes" => $mensajes]);
        } elseif (!empty($request[0]) && $request[0] === 'grupos') {
            // Consultar todos los grupos con sus miembros y roles
            $sql_grupos = "SELECT g.id_grupo, g.nombre_grupo, g.descripcion, g.fecha_hora_creacion, 
                                  u.id_usuario AS id_creador, u.nombre_usuario AS nombre_creador, 
                                  m.id_usuario, m.rol, u2.nombre_usuario AS nombre_miembro, u2.avatar
                           FROM grupo g
                           JOIN usuario u ON g.id_usuario = u.id_usuario
                           LEFT JOIN miembro_grupo m ON g.id_grupo = m.id_grupo
                           LEFT JOIN usuario u2 ON m.id_usuario = u2.id_usuario
                           ORDER BY g.id_grupo";

            $result = $conn->query($sql_grupos);
            $grupos = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $id_grupo = $row['id_grupo'];

                    // Si el grupo no ha sido agregado al array, lo agregamos
                    if (!isset($grupos[$id_grupo])) {
                        $grupos[$id_grupo] = [
                            "id_grupo" => $id_grupo,
                            "nombre_grupo" => $row['nombre_grupo'],
                            "descripcion" => $row['descripcion'],
                            "fecha_hora_creacion" => $row['fecha_hora_creacion'],
                            "creador" => [
                                "id_usuario" => $row['id_creador'],
                                "nombre_usuario" => $row['nombre_creador']
                            ],
                            "miembros" => []
                        ];
                    }

                    // Agregar los miembros del grupo
                    if ($row['id_usuario']) {
                        $grupos[$id_grupo]['miembros'][] = [
                            "id_usuario" => $row['id_usuario'],
                            "nombre_usuario" => $row['nombre_miembro'],
                            "rol" => $row['rol'],
                            "avatar" => $row['avatar']
                        ];
                    }
                }
            }

            // Convertir el array asociativo en un array indexado para la respuesta JSON
            echo json_encode(array_values($grupos));

            exit;
        } elseif (!empty($request[0]) && $request[0] === 'mensajes' && isset($_GET['conversation_id']) && isset($_GET['es_grupo'])) {
            $conversation_id = intval($_GET['conversation_id']);
            $es_grupo = intval($_GET['es_grupo']);
            $mensajes = [];
        
            if ($es_grupo === 1) {
                $sql_grupos = "
                    SELECT 
                        m.id_mensaje, 
                        m.contenido, 
                        m.fecha_hora, 
                        u.nombre_usuario, 
                        g.nombre_grupo 
                    FROM mensaje m 
                    JOIN mensaje_grupo mg ON m.id_mensaje = mg.id_mensaje 
                    JOIN usuario u ON m.id_usuario = u.id_usuario 
                    JOIN grupo g ON mg.id_grupo = g.id_grupo 
                    WHERE mg.id_grupo = $conversation_id
                    ORDER BY m.fecha_hora ASC
                ";
                $result_grupos = $conn->query($sql_grupos);
                while ($row = $result_grupos->fetch_assoc()) {
                    $mensajes[] = $row;
                }
            } else {
                $sql_directos = "
                    SELECT 
                        m.id_mensaje, 
                        m.contenido, 
                        m.fecha_hora, 
                        m.id_usuario, 
                        u.nombre_usuario 
                    FROM mensaje m 
                    JOIN mensaje_directo md ON m.id_mensaje = md.id_mensaje 
                    JOIN usuario u ON m.id_usuario = u.id_usuario 
                    WHERE 
                        (m.id_usuario = $conversation_id OR md.id_usuario = $conversation_id)
                        AND m.es_grupo = 0
                    ORDER BY m.fecha_hora ASC
                ";
                $result_directos = $conn->query($sql_directos);
                while ($row = $result_directos->fetch_assoc()) {
                    $mensajes[] = $row;
                }
            }
            echo json_encode(["mensajes" => $mensajes]);
        }

        break;

    case 'DELETE':
        // Eliminar todos los mensajes directos y de grupo
        if (!empty($request[0]) && $request[0] === 'delete_all_messages') {
            // Eliminar de mensaje_directo
            $sql_directo = "DELETE FROM mensaje_directo";
            $result_directo = $conn->query($sql_directo);

            // Eliminar de mensaje_grupo
            $sql_grupo_mensajes = "DELETE FROM mensaje_grupo";
            $result_grupo_mensajes = $conn->query($sql_grupo_mensajes);

            // Eliminar de mensaje
            $sql_mensaje = "DELETE FROM mensaje";
            $result_mensaje = $conn->query($sql_mensaje);

            if ($result_directo && $result_grupo_mensajes && $result_mensaje) {
                http_response_code(200);
                echo json_encode(["message" => "Todos los mensajes han sido eliminados"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar los mensajes"]);
            }
        }
        // Nuevo caso para eliminar todos los grupos y sus relaciones
        elseif (!empty($request[0]) && $request[0] === 'delete_all_groups') {
            // Eliminar miembros de los grupos
            $sql_miembros_grupo = "DELETE FROM miembro_grupo";
            $result_miembros_grupo = $conn->query($sql_miembros_grupo);

            // Eliminar mensajes de grupo
            $sql_mensajes_grupo = "DELETE FROM mensaje_grupo";
            $result_mensajes_grupo = $conn->query($sql_mensajes_grupo);

            // Eliminar grupos
            $sql_grupos = "DELETE FROM grupo";
            $result_grupos = $conn->query($sql_grupos);

            if ($result_miembros_grupo && $result_mensajes_grupo && $result_grupos) {
                http_response_code(200);
                echo json_encode(["message" => "Todos los grupos han sido eliminados"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar los grupos"]);
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