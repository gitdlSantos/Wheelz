<?php
include 'config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un evento específico por ID
            $id = intval($_GET['id']);
            $sql = "SELECT e.id, e.titulo, e.descripcion, e.fecha, e.hora, e.ubicacion, e.mapa_url, e.tags, e.created_at, u.nombre_usuario AS autor
                    FROM eventos e
                    JOIN usuarios u ON e.usuario_id = u.id
                    WHERE e.id = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $evento = $result->fetch_assoc();
                echo json_encode($evento);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Evento no encontrado"]);
            }
        } else {
            // Obtener todos los eventos
            $sql = "SELECT e.id, e.titulo, e.descripcion, e.fecha, e.hora, e.ubicacion, e.mapa_url, e.tags, e.created_at, u.nombre_usuario AS autor
                    FROM eventos e
                    JOIN usuarios u ON e.usuario_id = u.id";
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
            $checkUserSQL = "SELECT id FROM usuarios WHERE id = $usuario_id";
            $userResult = $conn->query($checkUserSQL);

            if ($userResult->num_rows === 0) {
                http_response_code(400);
                echo json_encode(["message" => "El usuario con ID $usuario_id no existe"]);
                exit;
            }

            // Resto del código para crear el evento
            $titulo = $conn->real_escape_string($data['titulo']);
            $descripcion = $conn->real_escape_string($data['descripcion']);
            $fecha = $conn->real_escape_string($data['fecha']);
            $hora = $conn->real_escape_string($data['hora']);
            $ubicacion = $conn->real_escape_string($data['ubicacion']);
            $tags = isset($data['tags']) ? $conn->real_escape_string($data['tags']) : null;

            // Generar la URL del mapa de Google
            $mapa_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode($ubicacion);

            // Insertar el nuevo evento en la base de datos
            $sql = "INSERT INTO eventos (usuario_id, titulo, descripcion, fecha, hora, ubicacion, mapa_url, tags) 
                        VALUES ($usuario_id, '$titulo', '$descripcion', '$fecha', '$hora', '$ubicacion', '$mapa_url', '$tags')";

            if ($conn->query($sql) === TRUE) {
                http_response_code(201);
                echo json_encode(["message" => "Evento creado exitosamente", "id" => $conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al crear el evento"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos para crear el evento"]);
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