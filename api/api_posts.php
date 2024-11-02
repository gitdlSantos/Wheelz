<?php
// Incluir la configuración de la base de datos
include 'config.php';

// Configurar la cabecera para JSON
header("Content-Type: application/json");

// Detectar el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Manejar la solicitud según el método
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un post específico por ID
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM posts WHERE id = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $post = $result->fetch_assoc();
                echo json_encode($post);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Post no encontrado"]);
            }
        } else {
            // Obtener todos los posts
            $sql = "SELECT * FROM posts";
            $result = $conn->query($sql);

            $posts = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
            }
            echo json_encode($posts);
        }
        break;

    case 'POST':
        // Obtener datos de la solicitud POST y verificar la recepción
        $data = json_decode(file_get_contents("php://input"), true);

        // Imprimir los datos recibidos para depuración
        error_log(print_r($data, true)); // Esto imprime los datos en el log del servidor

        // Validar que al menos uno de los campos (contenido o imagen) esté presente
        if (!empty($data['contenido']) || !empty($data['imagen'])) {
            $usuario_id = intval($data['usuario_id']);
            $contenido = isset($data['contenido']) ? $conn->real_escape_string($data['contenido']) : null;

            // Si `imagen` es un array, unir las rutas en una cadena separada por comas
            $imagen = isset($data['imagen']) ? implode(',', array_map([$conn, 'real_escape_string'], $data['imagen'])) : null;

            // Insertar el nuevo post en la base de datos
            $sql = "INSERT INTO posts (usuario_id, contenido, imagen) VALUES ($usuario_id, '$contenido', '$imagen')";

            if ($conn->query($sql) === TRUE) {
                // Respuesta de éxito
                http_response_code(201);
                echo json_encode(["message" => "Post creado exitosamente", "id" => $conn->insert_id]);
            } else {
                // Respuesta de error de inserción
                http_response_code(500);
                echo json_encode(["message" => "Error al crear el post"]);
            }
        } else {
            // Respuesta de error de validación
            http_response_code(400);
            echo json_encode(["message" => "Debe incluir contenido o al menos una imagen"]);
        }
        break;

        case 'PUT':
            // Obtener datos de la solicitud PUT en formato JSON
            $data = json_decode(file_get_contents("php://input"), true);
        
            // Validar que el id, usuario_id y contenido están presentes
            if (isset($data['id']) && isset($data['contenido']) && isset($data['usuario_id'])) {
                $id = intval($data['id']);
                $contenido = $conn->real_escape_string($data['contenido']);
                $usuario_id = intval($data['usuario_id']);
        
                // Verificar si el usuario es el propietario del post
                $check_sql = "SELECT usuario_id FROM posts WHERE id = $id";
                $check_result = $conn->query($check_sql);
                $post_data = $check_result->fetch_assoc();
        
                if ($post_data && $post_data['usuario_id'] == $usuario_id) {
                    // Actualizar el contenido del post
                    $sql = "UPDATE posts SET contenido = '$contenido' WHERE id = $id";
        
                    if ($conn->query($sql) === TRUE) {
                        http_response_code(200);
                        echo json_encode(["message" => "Contenido actualizado exitosamente"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al actualizar el contenido"]);
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(["message" => "No tienes permiso para modificar este post"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID, contenido y usuario_id son requeridos"]);
            }
            break;

            case 'DELETE':
                // Obtener datos de la solicitud DELETE en formato JSON
                $data = json_decode(file_get_contents("php://input"), true);
            
                // Validar que el id y usuario_id están presentes
                if (isset($data['id']) && isset($data['usuario_id'])) {
                    $id = intval($data['id']);
                    $usuario_id = intval($data['usuario_id']);
            
                    // Verificar si el usuario es el propietario del post
                    $check_sql = "SELECT usuario_id FROM posts WHERE id = $id";
                    $check_result = $conn->query($check_sql);
                    $post_data = $check_result->fetch_assoc();
            
                    if ($post_data && $post_data['usuario_id'] == $usuario_id) {
                        // Eliminar el post con el ID especificado
                        $sql = "DELETE FROM posts WHERE id = $id";
            
                        if ($conn->query($sql) === TRUE) {
                            http_response_code(200);
                            echo json_encode(["message" => "Post eliminado exitosamente"]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["message" => "Error al eliminar el post"]);
                        }
                    } else {
                        http_response_code(403);
                        echo json_encode(["message" => "No tienes permiso para eliminar este post"]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID y usuario_id son requeridos"]);
                }
                break;

    default:
        // Método no soportado
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

// Cerrar la conexión a la base de datos
$conn->close();
?>