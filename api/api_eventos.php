<?php
include 'config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un evento específico por ID
            $id = intval($_GET['id']);
            $sql = "SELECT e.id_evento, e.nombre_evento, e.descripcion, e.fecha_hora_evento, e.ubicacion, u.nombre_usuario AS autor, e.created_at
                    FROM evento e
                    JOIN usuario u ON e.id_usuario = u.id_usuario
                    WHERE e.id_evento = $id
                    ORDER BY e.created_at DESC";  // Ordenando por la fecha de creación
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $evento = $result->fetch_assoc();
                echo json_encode($evento);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Evento no encontrado"]);
            }
        } elseif (isset($_GET['usuario_id'])) {
            $usuario_id = intval($_GET['usuario_id']);
            $sql = "SELECT e.id_evento AS id, e.nombre_evento AS titulo, e.descripcion, e.fecha_hora_evento, e.ubicacion, u.nombre_usuario AS autor, e.created_at
                    FROM evento e
                    JOIN usuario u ON e.id_usuario = u.id_usuario
                    WHERE e.id_usuario = $usuario_id
                    ORDER BY e.created_at DESC"; // Ordenar por fecha de creación en orden descendente
            $result = $conn->query($sql);

            $eventos = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['type'] = 'event';
                    $eventos[] = $row;
                }
            }
            echo json_encode($eventos);
        } else {
            // Obtener todos los eventos sin filtrar y ordenarlos por fecha de creación
            $sql = "SELECT e.id_evento, e.nombre_evento, e.descripcion, e.fecha_hora_evento, e.ubicacion, u.nombre_usuario AS autor, e.created_at
                    FROM evento e
                    JOIN usuario u ON e.id_usuario = u.id_usuario
                    ORDER BY e.created_at DESC"; // Ordenar por fecha de creación en orden descendente
            $result = $conn->query($sql);

            $eventos = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $eventos[] = $row;
                }
            }
            echo json_encode($eventos);
        }
        break;

    case 'POST':
        // Crear un nuevo evento
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['usuario_id'], $data['titulo'], $data['descripcion'], $data['fecha'], $data['hora'], $data['ubicacion'])) {
            $usuario_id = intval($data['usuario_id']);

            // Verificar que el usuario existe
            $checkUserSQL = "SELECT id_usuario FROM usuario WHERE id_usuario = $usuario_id";
            $userResult = $conn->query($checkUserSQL);

            if ($userResult->num_rows === 0) {
                http_response_code(400);
                echo json_encode(["message" => "El usuario con ID $usuario_id no existe"]);
                exit;
            }

            // Preparar los datos del evento
            $titulo = $conn->real_escape_string($data['titulo']);
            $descripcion = $conn->real_escape_string($data['descripcion']);
            $fecha_hora_evento = $conn->real_escape_string($data['fecha'] . ' ' . $data['hora']);
            $ubicacion = $conn->real_escape_string($data['ubicacion']);

            // Insertar el nuevo evento en la base de datos
            $sql = "INSERT INTO evento (id_usuario, nombre_evento, descripcion, fecha_hora_evento, ubicacion) 
                        VALUES ($usuario_id, '$titulo', '$descripcion', '$fecha_hora_evento', '$ubicacion')";

            if ($conn->query($sql) === TRUE) {
                http_response_code(201);
                echo json_encode(["message" => "Evento creado exitosamente", "id" => $conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al crear el evento", "error" => $conn->error]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos para crear el evento"]);
        }

        break;

    case 'DELETE':
        // Verificar si se requiere la eliminación de todos los eventos
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['delete_all']) && $data['delete_all'] === true) {
            // Eliminar todos los eventos sin verificación de usuario
            $sql = "DELETE FROM evento";
            if ($conn->query($sql) === TRUE) {
                http_response_code(200);
                echo json_encode(["message" => "Todos los eventos han sido eliminados correctamente"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar los eventos", "error" => $conn->error]);
            }
        } else {
            // Código para eliminar un solo evento si no se especifica delete_all
            if (isset($data['id_evento'])) {
                $id_evento = intval($data['id_evento']);

                // Eliminar un evento específico
                $deleteSQL = "DELETE FROM evento WHERE id_evento = $id_evento";
                if ($conn->query($deleteSQL) === TRUE) {
                    http_response_code(200);
                    echo json_encode(["message" => "Evento eliminado correctamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al eliminar el evento", "error" => $conn->error]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para eliminar el evento"]);
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