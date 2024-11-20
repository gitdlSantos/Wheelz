<?php
include 'config.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;

if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de usuario requerido."]);
    exit;
}

switch ($method) {
    case 'GET': // Obtener todas las notificaciones
        if (!isset($_GET['usuario_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID de usuario requerido."]);
            exit;
        }

        $usuario_id = intval($_GET['usuario_id']);

        // Consulta para obtener las notificaciones del usuario
        $sql = "SELECT n.id_notificacion, n.id_tipo_notificacion, n.fecha_notificacion, n.visto,
                       n.extra_info,
                       u.nombre_usuario, u.avatar
                FROM notificacion n
                JOIN usuario u ON u.id_usuario = n.id_usuario
                WHERE n.id_usuario = $usuario_id
                ORDER BY n.fecha_notificacion DESC";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $notificaciones = [];
            while ($row = $result->fetch_assoc()) {
                // Construir cada notificación con los datos necesarios
                $notificaciones[] = [
                    "id_notificacion" => $row["id_notificacion"],
                    "id_tipo_notificacion" => $row["id_tipo_notificacion"],
                    "fecha_notificacion" => $row["fecha_notificacion"],
                    "visto" => $row["visto"],
                    "usuario" => [
                        "nombre_usuario" => $row["nombre_usuario"],
                        "avatar" => $row["avatar"]
                    ],
                    "extra_info" => json_decode($row["extra_info"], true) // Decodificar JSON si aplica
                ];
            }

            // Devolver las notificaciones en formato JSON
            echo json_encode($notificaciones);
        } else {
            // No se encontraron notificaciones
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron notificaciones."]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['usuario_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID de usuario requerido."]);
            exit;
        }

        $usuario_id = intval($_GET['usuario_id']);

        // Actualizar notificaciones para marcar como leídas
        $sql = "UPDATE notificacion SET visto = 1 WHERE id_usuario = $usuario_id AND visto = 0";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Notificaciones marcadas como leídas."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error al marcar notificaciones como leídas."]);
        }
        break;

    case 'POST': // Crear una nueva notificación
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_usuario_destino'], $data['id_tipo_notificacion'], $data['mensaje'])) {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos para crear la notificación."]);
            exit;
        }

        $id_usuario_destino = intval($data['id_usuario_destino']);
        $id_tipo_notificacion = intval($data['id_tipo_notificacion']);
        $mensaje = $conn->real_escape_string($data['mensaje']);
        $extra_info = isset($data['extra_info']) ? json_encode($data['extra_info']) : null;

        // Insertar la notificación
        $sql = "INSERT INTO notificacion (id_usuario, id_tipo_notificacion, extra_info)
                        VALUES ($id_usuario_destino, $id_tipo_notificacion, '$extra_info')";

        if ($conn->query($sql) === TRUE) {
            http_response_code(201);
            echo json_encode(["message" => "Notificación creada exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error al crear la notificación."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido."]);
        break;
}

$conn->close();
