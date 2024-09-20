<?php
require 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Procesar el cambio de la foto de perfil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $imageName = basename($_FILES['profile_image']['name']);
        $uploadFile = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $imageName, $user_id);
            $stmt->execute();
        }
    }

    // Procesar el cambio del nombre de usuario
    if (isset($_POST['username']) && !empty($_POST['username'])) {
        $username = $_POST['username'];
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Usuario no autenticado']);
}
?>
