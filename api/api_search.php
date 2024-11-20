<?php
include 'config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['search'])) {
    $searchText = $conn->real_escape_string($_GET['search']);
    $excludeId = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

    // Buscar usuarios que coincidan con el término de búsqueda y excluir al usuario logueado
    $sql = "SELECT id_usuario, nombre_usuario, avatar
            FROM usuario
            WHERE nombre_usuario LIKE '%$searchText%' AND id_usuario != $excludeId";

    $result = $conn->query($sql);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Ruta completa de la imagen en el servidor
        $avatarPath = $_SERVER['DOCUMENT_ROOT'] . "/Wheelz/uploads/" . ltrim($row['avatar'], '/');
        
        // Verificar si el archivo existe
        if (empty($row['avatar']) || !file_exists($avatarPath)) {
            $row['avatar'] = "../uploads/default-image.png";
        } else {
            $row['avatar'] = "../uploads/" . ltrim($row['avatar'], '/');
        }

        $users[] = $row;
    }

    echo json_encode($users);
} else {
    http_response_code(400);
    echo json_encode(["message" => "Parámetro de búsqueda no proporcionado."]);
}

$conn->close();
?>