<?php
// Incluir la configuración de la base de datos
include 'config.php';

// Configurar la cabecera para JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {

    // Detectar el método HTTP y el endpoint
    $method = $_SERVER['REQUEST_METHOD'];

    // Manejar la solicitud según el método
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // 1. Obtener un foro específico por ID junto con sus comentarios
                $id = intval($_GET['id']);
                $sql = "SELECT f.id, f.titulo, f.contenido, f.categoria, f.hashtag, f.created_at, u.nombre_usuario AS autor
                        FROM foro f
                        JOIN usuario u ON f.usuario_id = u.id_usuario
                        WHERE f.id = $id";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    $foro = $result->fetch_assoc();

                    // Obtener comentarios del foro
                    $comentarios_sql = "SELECT c.id_comentario AS id, c.contenido, c.fecha_hora_creacion AS created_at, u.nombre_usuario AS autor
                                        FROM comentario c
                                        JOIN usuario u ON c.id_usuario = u.id_usuario
                                        WHERE c.id_publi = $id
                                        ORDER BY c.fecha_hora_creacion ASC";
                    $comentarios_result = $conn->query($comentarios_sql);

                    $comentarios = [];
                    if ($comentarios_result && $comentarios_result->num_rows > 0) {
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

            } elseif (isset($_GET['usuario_id'])) {
                $usuario_id = intval($_GET['usuario_id']);
                $sql = "SELECT f.id, f.titulo, f.contenido, f.categoria, f.hashtag, f.created_at, u.nombre_usuario AS autor
                        FROM foro f
                        JOIN usuario u ON f.usuario_id = u.id_usuario
                        WHERE f.usuario_id = $usuario_id
                        ORDER BY f.created_at DESC";
                $result = $conn->query($sql);

                $foros = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $row['type'] = 'forum';
                        $foros[] = $row;
                    }
                }
                echo json_encode($foros);
            } else {
                // 3. Obtener todos los foros sin filtrar
                $sql = "SELECT f.id, f.titulo, f.contenido, f.categoria, f.hashtag, f.created_at, u.nombre_usuario AS autor
                        FROM foro f
                        JOIN usuario u ON f.usuario_id = u.id_usuario
                        ORDER BY f.created_at DESC";
                $result = $conn->query($sql);

                $foros = [];
                if ($result && $result->num_rows > 0) {
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
                    $sql = "INSERT INTO foro (usuario_id, titulo, contenido, categoria, hashtag) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param(
                            "issss",
                            $data['usuario_id'],
                            $data['titulo'],
                            $data['contenido'],
                            $data['categoria'],
                            $data['hashtag']
                        );

                        if ($stmt->execute()) {
                            http_response_code(201);
                            echo json_encode(["message" => "Foro creado exitosamente", "id" => $stmt->insert_id]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["message" => "Error al crear el foro", "error" => $stmt->error]);
                        }

                        $stmt->close();
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al preparar la consulta", "error" => $conn->error]);
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// Cerrar la conexión a la base de datos
$conn->close();
?>