<?php
// Incluir la configuración de la base de datos
include 'config.php';

// Configurar la cabecera para JSON
header("Content-Type: application/json");

// Detectar el método HTTP y el endpoint
$method = $_SERVER['REQUEST_METHOD'];

// Manejar la solicitud según el método
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un foro específico por ID junto con sus comentarios
            $id = intval($_GET['id']);
            $sql = "SELECT f.id, f.titulo, f.contenido, f.tags, f.created_at, u.nombre_usuario AS autor
                    FROM foros f
                    JOIN usuarios u ON f.usuario_id = u.id
                    WHERE f.id = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $foro = $result->fetch_assoc();
                
                // Obtener comentarios del foro
                $comentarios_sql = "SELECT c.id, c.contenido, c.created_at, u.nombre_usuario AS autor
                                    FROM comentarios c
                                    JOIN usuarios u ON c.usuario_id = u.id
                                    WHERE c.foro_id = $id
                                    ORDER BY c.created_at ASC";
                $comentarios_result = $conn->query($comentarios_sql);

                $comentarios = [];
                if ($comentarios_result->num_rows > 0) {
                    while ($row = $comentarios_result->fetch_assoc()) {
                        $comentarios[] = $row;
                    }
                }
                
                $foro['comentarios'] = $comentarios;
                echo json_encode($foro);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Foro no encontrado"]);
            }
        } else {
            // Obtener todos los foros
            $sql = "SELECT f.id, f.titulo, f.contenido, f.tags, f.created_at, u.nombre_usuario AS autor
                    FROM foros f
                    JOIN usuarios u ON f.usuario_id = u.id";
            $result = $conn->query($sql);

            $foros = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $foros[] = $row;
                }
            }
            echo json_encode($foros);
        }
        break;

    case 'POST':
        if (isset($_GET['foro_id']) && $_GET['foro_id'] !== '') {
            // Crear un nuevo comentario en un foro
            $foro_id = intval($_GET['foro_id']);
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['usuario_id'], $data['contenido'])) {
                $usuario_id = intval($data['usuario_id']);
                $contenido = $conn->real_escape_string($data['contenido']);

                // Insertar el comentario en la base de datos
                $sql = "INSERT INTO comentarios (foro_id, usuario_id, contenido) VALUES ($foro_id, $usuario_id, '$contenido')";
                
                if ($conn->query($sql) === TRUE) {
                    http_response_code(201);
                    echo json_encode(["message" => "Comentario creado exitosamente", "id" => $conn->insert_id]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al crear el comentario"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para crear el comentario"]);
            }
        } else {
            // Crear un nuevo foro
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['usuario_id'], $data['titulo'], $data['contenido'])) {
                $usuario_id = intval($data['usuario_id']);
                $titulo = $conn->real_escape_string($data['titulo']);
                $contenido = $conn->real_escape_string($data['contenido']);
                $tags = isset($data['tags']) ? $conn->real_escape_string($data['tags']) : null;

                // Insertar el nuevo foro en la base de datos
                $sql = "INSERT INTO foros (usuario_id, titulo, contenido, tags) VALUES ($usuario_id, '$titulo', '$contenido', '$tags')";
                
                if ($conn->query($sql) === TRUE) {
                    http_response_code(201);
                    echo json_encode(["message" => "Foro creado exitosamente", "id" => $conn->insert_id]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al crear el foro"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos para crear el foro"]);
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
