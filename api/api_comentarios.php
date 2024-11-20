<?php
include 'config.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id_publi'])) {
                // Obtener comentarios para una publicación específica
                $id_publi = intval($_GET['id_publi']);
                $sql = "SELECT c.id_comentario AS id, c.contenido, c.fecha_hora_creacion AS created_at, 
                        u.nombre_usuario AS autor, u.avatar 
                        FROM comentario c
                        JOIN usuario u ON c.id_usuario = u.id_usuario
                        WHERE c.id_publi = $id_publi
                        ORDER BY c.fecha_hora_creacion DESC";
            } elseif (isset($_GET['foro_id'])) {
                // Obtener comentarios para un foro específico
                $foro_id = intval($_GET['foro_id']);
                $sql = "SELECT c.id_comentario AS id, c.contenido, c.fecha_hora_creacion AS created_at, 
                        u.nombre_usuario AS autor, u.avatar 
                        FROM comentario_foro c
                        JOIN usuario u ON c.id_usuario = u.id_usuario
                        WHERE c.id_foro = $foro_id
                        ORDER BY c.fecha_hora_creacion DESC";
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID de publicación o foro requerido."]);
                exit;
            }

            $result = $conn->query($sql);
            $comentarios = [];
            while ($row = $result->fetch_assoc()) {
                $comentarios[] = $row;
            }
            echo json_encode($comentarios);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['id_publi'])) {
                // Crear comentario para una publicación
                $id_publi = intval($data['id_publi']);
                $id_usuario = intval($data['id_usuario']);
                $contenido = $conn->real_escape_string($data['contenido']);
                $sql = "INSERT INTO comentario (id_publi, id_usuario, contenido, fecha_hora_creacion) 
                        VALUES ($id_publi, $id_usuario, '$contenido', NOW())";
            } elseif (isset($data['foro_id'])) {
                // Crear comentario para un foro
                $foro_id = intval($data['foro_id']);
                $id_usuario = intval($data['id_usuario']);
                $contenido = $conn->real_escape_string($data['contenido']);
                $sql = "INSERT INTO comentario_foro (id_foro, id_usuario, contenido, fecha_hora_creacion) 
                        VALUES ($foro_id, $id_usuario, '$contenido', NOW())";
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID de publicación o foro requerido."]);
                exit;
            }

            if ($conn->query($sql) === TRUE) {
                http_response_code(201);
                echo json_encode(["message" => "Comentario creado exitosamente"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al crear el comentario"]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id_comentario'])) {
                $id_comentario = intval($_GET['id_comentario']);
                $sql = "DELETE FROM comentario WHERE id_comentario = $id_comentario";

                if ($conn->query($sql) === TRUE) {
                    http_response_code(200);
                    echo json_encode(["message" => "Comentario eliminado exitosamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al eliminar el comentario"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID de comentario requerido."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

$conn->close();
?>