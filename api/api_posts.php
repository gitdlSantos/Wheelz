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
            // Obtener un post específico
            $id = intval($_GET['id']);
            $id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : null;

            $sql = "SELECT p.id_publi AS id, 
                           p.contenido_texto AS contenido, 
                           p.multimedia_url AS imagen, 
                           p.fecha__hora_creacion AS created_at, 
                           u.nombre_usuario, 
                           u.avatar,
                           (SELECT COUNT(*) FROM likes_publicacion WHERE id_publi = p.id_publi) AS total_likes,
                           EXISTS (SELECT 1 FROM likes_publicacion WHERE id_usuario = $id_usuario AND id_publi = p.id_publi) AS user_liked
                    FROM publicacion p
                    JOIN usuario u ON p.id_usuario = u.id_usuario
                    WHERE p.id_publi = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $post = $result->fetch_assoc();

                // Convertir la ruta de la imagen en absoluta
                if (!empty($post['imagen'])) {
                    $imagen = json_decode($post['imagen'], true); // Decodifica si es JSON
                    if (is_array($imagen) && count($imagen) > 0) {
                        $rutaImagen = $imagen[0];
                    } else {
                        $rutaImagen = $post['imagen'];
                    }

                    // Generar una URL completa en el backend
                    $post['imagen'] = strpos($rutaImagen, "Wheelz/uploads/posts/") !== false
                        ? "http://localhost:8080/" . ltrim($rutaImagen, '/')
                        : "../uploads/posts/" . ltrim($rutaImagen, '/');
                }

                // Asegurar que el avatar también sea una ruta absoluta
                if (!empty($post['avatar'])) {
                    $post['avatar'] = strpos($post['avatar'], "Wheelz/uploads/") !== false
                        ? "http://localhost:8080/" . ltrim($post['avatar'], '/')
                        : "../uploads/" . ltrim($post['avatar'], '/');
                } else {
                    $post['avatar'] = "../uploads/default-avatar.png";
                }

                echo json_encode($post);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Post no encontrado"]);
            }
        } elseif (isset($_GET['id_usuario'])) {
            // Obtener todos los posts de un usuario específico
            $id_usuario = intval($_GET['id_usuario']);
            $sql = "SELECT p.id_publi AS id, 
                           p.contenido_texto AS contenido, 
                           p.multimedia_url AS imagen, 
                           p.fecha__hora_creacion AS created_at, 
                           u.nombre_usuario, 
                           u.avatar,
                           (SELECT COUNT(*) FROM likes_publicacion WHERE id_publi = p.id_publi) AS total_likes,
                           EXISTS (SELECT 1 FROM likes_publicacion WHERE id_usuario = $id_usuario AND id_publi = p.id_publi) AS user_liked
                    FROM publicacion p
                    JOIN usuario u ON p.id_usuario = u.id_usuario
                    WHERE p.id_usuario = $id_usuario
                    ORDER BY p.fecha__hora_creacion DESC";
            $result = $conn->query($sql);

            $posts = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Convertir la ruta de la imagen en absoluta
                    if (!empty($row['imagen'])) {
                        $imagen = json_decode($row['imagen'], true); // Decodifica si es JSON
                        if (is_array($imagen) && count($imagen) > 0) {
                            $rutaImagen = $imagen[0];
                        } else {
                            $rutaImagen = $row['imagen'];
                        }

                        // Generar una URL completa en el backend
                        $row['imagen'] = strpos($rutaImagen, "Wheelz/uploads/posts/") !== false
                            ? "http://localhost:8080/" . ltrim($rutaImagen, '/')
                            : "../uploads/posts/" . ltrim($rutaImagen, '/');
                    }

                    // Asegurar que el avatar también sea una ruta absoluta
                    if (!empty($row['avatar'])) {
                        $row['avatar'] = strpos($row['avatar'], "Wheelz/uploads/") !== false
                            ? "http://localhost:8080/" . ltrim($row['avatar'], '/')
                            : "../uploads/" . ltrim($row['avatar'], '/');
                    } else {
                        $row['avatar'] = "../uploads/default-avatar.png";
                    }

                    $posts[] = $row;
                }
            }

            echo json_encode($posts);
        } else {
            // Obtener todos los posts de todos los usuarios
            $sql = "SELECT p.id_publi AS id, 
                           p.contenido_texto AS contenido, 
                           p.multimedia_url AS imagen, 
                           p.fecha__hora_creacion AS created_at, 
                           u.nombre_usuario, 
                           u.avatar,
                           (SELECT COUNT(*) FROM likes_publicacion WHERE id_publi = p.id_publi) AS total_likes,
                           EXISTS (SELECT 1 FROM likes_publicacion WHERE id_usuario = ? AND id_publi = p.id_publi) AS user_liked
                    FROM publicacion p
                    JOIN usuario u ON p.id_usuario = u.id_usuario
                    ORDER BY p.fecha__hora_creacion DESC";
            $stmt = $conn->prepare($sql);

            // El id_usuario es opcional aquí, así que usamos un valor por defecto si no se proporciona
            $id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : null;
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            $posts = [];
            while ($row = $result->fetch_assoc()) {
                // Convertir la ruta de la imagen en absoluta
                if (!empty($row['imagen'])) {
                    $imagen = json_decode($row['imagen'], true); // Decodifica si es JSON
                    if (is_array($imagen) && count($imagen) > 0) {
                        $rutaImagen = $imagen[0];
                    } else {
                        $rutaImagen = $row['imagen'];
                    }

                    // Generar una URL completa en el backend
                    $row['imagen'] = strpos($rutaImagen, "Wheelz/uploads/posts/") !== false
                        ? "http://localhost:8080/" . ltrim($rutaImagen, '/')
                        : "../uploads/posts/" . ltrim($rutaImagen, '/');
                }

                // Asegurar que el avatar también sea una ruta absoluta
                if (!empty($row['avatar'])) {
                    $row['avatar'] = strpos($row['avatar'], "Wheelz/uploads/") !== false
                        ? "http://localhost:8080/" . ltrim($row['avatar'], '/')
                        : "../uploads/" . ltrim($row['avatar'], '/');
                } else {
                    $row['avatar'] = "../uploads/default-avatar.png";
                }

                $posts[] = $row;
            }

            echo json_encode($posts);
        }
        break;

    case 'POST':
        // Manejar creación de post o dar like
        if (isset($_POST['id_usuario'], $_POST['id_publi'])) {
            // Dar like a una publicación
            $id_usuario = intval($_POST['id_usuario']);
            $id_publi = intval($_POST['id_publi']);

            // Verificar si el like ya existe
            $checkSql = "SELECT * FROM likes_publicacion WHERE id_usuario = $id_usuario AND id_publi = $id_publi";
            $result = $conn->query($checkSql);

            if ($result->num_rows > 0) {
                http_response_code(409);
                echo json_encode(["message" => "El like ya existe"]);
            } else {
                $insertSql = "INSERT INTO likes_publicacion (id_usuario, id_publi) VALUES ($id_usuario, $id_publi)";
                if ($conn->query($insertSql) === TRUE) {
                    http_response_code(201);
                    echo json_encode(["message" => "Like registrado exitosamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al registrar el like"]);
                }
            }
        } else {
            // Crear un nuevo post
            $id_usuario = intval($_POST['id_usuario']);
            $contenido = isset($_POST['contenido']) ? $conn->real_escape_string($_POST['contenido']) : null;
            $uploadedImages = [];

            // Subir imagen
            if (isset($_FILES['imagen'])) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Wheelz/uploads/posts/';
                $fileName = basename($_FILES['imagen']['name']);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $filePath)) {
                    $uploadedImages[] = "/Wheelz/uploads/posts/" . $fileName;
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al guardar la imagen en el servidor"]);
                    exit;
                }
            }

            $imagen = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

            if (!empty($contenido) || !empty($imagen)) {
                $sql = "INSERT INTO publicacion (id_usuario, contenido_texto, multimedia_url) VALUES ($id_usuario, '$contenido', '$imagen')";
                if ($conn->query($sql) === TRUE) {
                    http_response_code(201);
                    echo json_encode([
                        "message" => "Post creado exitosamente",
                        "id" => $conn->insert_id,
                        "imagenes" => $uploadedImages
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al crear el post"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Debe incluir contenido o al menos una imagen"]);
            }
        }
        break;


    case 'PUT':
        // Obtener datos de la solicitud PUT en formato JSON
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que el id, id_usuario y contenido están presentes
        if (isset($data['id']) && isset($data['contenido']) && isset($data['id_usuario'])) {
            $id = intval($data['id']);
            $contenido = $conn->real_escape_string($data['contenido']);
            $id_usuario = intval($data['id_usuario']);

            // Verificar si el usuario es el propietario del post
            $check_sql = "SELECT id_usuario FROM publicacion WHERE id = $id";
            $check_result = $conn->query($check_sql);
            $post_data = $check_result->fetch_assoc();

            if ($post_data && $post_data['id_usuario'] == $id_usuario) {
                // Actualizar el contenido del post
                $sql = "UPDATE posts SET contenido = '$contenido' WHERE id_publi = $id";

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
            echo json_encode(["message" => "ID, contenido y id_usuario son requeridos"]);
        }
        break;

    case 'DELETE':
        // Obtener datos de la solicitud DELETE en formato JSON
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que el id y id_usuario están presentes
        if (isset($data['id']) && isset($data['id_usuario'])) {
            $id = intval($data['id']);
            $id_usuario = intval($data['id_usuario']);

            // Verificar si el usuario es el propietario del post
            $check_sql = "SELECT id_usuario FROM publicacion WHERE id = $id";
            $check_result = $conn->query($check_sql);
            $post_data = $check_result->fetch_assoc();

            if ($post_data && $post_data['id_usuario'] == $id_usuario) {
                // Eliminar el post con el ID especificado
                $sql = "DELETE FROM publicacion WHERE id = $id";

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
            echo json_encode(["message" => "ID y id_usuario son requeridos"]);
        }

        // Verificar si se solicita eliminar todos los posts
        if (isset($data['delete_all']) && $data['delete_all'] === true) {
            // Eliminar todos los archivos de imágenes en la carpeta uploads/posts/
            $directory = $_SERVER['DOCUMENT_ROOT'] . '/Wheelz/uploads/posts/';
            $files = glob($directory . '*'); // Obtener todos los archivos en el directorio

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Eliminar cada archivo
                }
            }

            // Eliminar todos los registros de publicaciones en la base de datos
            $sql = "DELETE FROM publicacion";

            if ($conn->query($sql) === TRUE) {
                http_response_code(200);
                echo json_encode(["message" => "Todos los posts y archivos de imágenes han sido eliminados exitosamente"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar todos los posts"]);
            }
        } elseif (isset($data['id']) && isset($data['id_usuario'])) {
            // Validar que el id y id_usuario están presentes
            $id = intval($data['id']);
            $id_usuario = intval($data['id_usuario']);

            // Verificar si el usuario es el propietario del post
            $check_sql = "SELECT id_usuario, multimedia_url FROM publicacion WHERE id_publi = $id";
            $check_result = $conn->query($check_sql);
            $post_data = $check_result->fetch_assoc();

            if ($post_data && $post_data['id_usuario'] == $id_usuario) {
                // Eliminar el post con el ID especificado
                $sql = "DELETE FROM publicacion WHERE id_publi = $id";

                if ($conn->query($sql) === TRUE) {
                    // Eliminar las imágenes asociadas al post
                    $images = json_decode($post_data['multimedia_url'], true);
                    if (isset($images['images']) && is_array($images['images'])) {
                        foreach ($images['images'] as $imagePath) {
                            $file = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
                            if (is_file($file)) {
                                unlink($file); // Eliminar el archivo
                            }
                        }
                    }

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
            echo json_encode(["message" => "ID y id_usuario son requeridos para eliminar un solo post o 'delete_all': true para eliminar todos los posts"]);
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