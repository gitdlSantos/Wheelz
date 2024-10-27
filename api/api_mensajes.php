<?php
include 'config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$request = @explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

switch ($method) {
    case 'POST':
        if ($request[0] === 'conversacion') {
            // Crear una nueva conversación (si no existe)
            $data = json_decode(file_get_contents("php://input"), true);

            if (isset($data['usuario1_id'], $data['usuario2_id'])) {
                $usuario1_id = intval($data['usuario1_id']);
                $usuario2_id = intval($data['usuario2_id']);

                // Verificar si la conversación ya existe
                $sql = "SELECT id FROM conversaciones WHERE (usuario1_id = $usuario1_id AND usuario2_id = $usuario2_id) 
                        OR (usuario1_id = $usuario2_id AND usuario2_id = $usuario1_id)";
                $result = $conn->query($sql);

                if ($result->num_rows === 0) {
                    // Crear la conversación
                    $sql = "INSERT INTO conversaciones (usuario1_id, usuario2_id) VALUES ($usuario1_id, $usuario2_id)";
                    if ($conn->query($sql) === TRUE) {
                        http_response_code(201);
                        echo json_encode(["message" => "Conversación creada", "conversacion_id" => $conn->insert_id]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al crear la conversación"]);
                    }
                } else {
                    $conversacion_id = $result->fetch_assoc()['id'];
                    echo json_encode(["message" => "Conversación ya existe", "conversacion_id" => $conversacion_id]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para crear la conversación"]);
            }
        } elseif ($request[0] === 'mensaje') {
            // Enviar un nuevo mensaje
            $data = json_decode(file_get_contents("php://input"), true);
        
            if (isset($data['conversacion_id'], $data['remitente_id'], $data['contenido'])) {
                $conversacion_id = intval($data['conversacion_id']);
                $remitente_id = intval($data['remitente_id']);
                $contenido = $conn->real_escape_string($data['contenido']);
        
                // Verificar si el remitente pertenece a la conversación
                $sql = "SELECT id FROM conversaciones 
                        WHERE id = $conversacion_id 
                        AND (usuario1_id = $remitente_id OR usuario2_id = $remitente_id)";
                $result = $conn->query($sql);
        
                if ($result->num_rows > 0) {
                    // Si el remitente pertenece a la conversación, insertar el mensaje
                    $sql = "INSERT INTO mensajes (conversacion_id, remitente_id, contenido) 
                            VALUES ($conversacion_id, $remitente_id, '$contenido')";
                    
                    if ($conn->query($sql) === TRUE) {
                        http_response_code(201);
                        echo json_encode(["message" => "Mensaje enviado"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al enviar el mensaje"]);
                    }
                } else {
                    // Si el remitente no pertenece a la conversación, enviar un mensaje de error
                    http_response_code(403);
                    echo json_encode(["message" => "El remitente no pertenece a esta conversación"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para enviar el mensaje"]);
            }
        }
        break;

    case 'GET':
        if ($request[0] === 'mensajes' && isset($_GET['conversacion_id'])) {
            // Obtener mensajes de una conversación
            $conversacion_id = intval($_GET['conversacion_id']);
            $sql = "SELECT m.id, m.remitente_id, m.contenido, m.enviado_at, u.nombre_usuario AS remitente_nombre
                    FROM mensajes m
                    JOIN usuarios u ON m.remitente_id = u.id
                    WHERE m.conversacion_id = $conversacion_id
                    ORDER BY m.enviado_at ASC";
            $result = $conn->query($sql);

            $mensajes = [];
            while ($row = $result->fetch_assoc()) {
                $mensajes[] = $row;
            }
            echo json_encode(["mensajes" => $mensajes]);
        } elseif ($request[0] === 'conversaciones' && isset($_GET['usuario_id'])) {
            // Listar conversaciones de un usuario
            $usuario_id = intval($_GET['usuario_id']);
            $sql = "SELECT c.id, c.usuario1_id, c.usuario2_id, 
                           (SELECT contenido FROM mensajes WHERE conversacion_id = c.id ORDER BY enviado_at DESC LIMIT 1) AS ultimo_mensaje
                    FROM conversaciones c
                    WHERE c.usuario1_id = $usuario_id OR c.usuario2_id = $usuario_id";
            $result = $conn->query($sql);

            $conversaciones = [];
            while ($row = $result->fetch_assoc()) {
                $conversaciones[] = $row;
            }
            echo json_encode(["conversaciones" => $conversaciones]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

$conn->close();
?>
