<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }

    // Encriptar la contraseña antes de guardarla
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Manejo de la foto de perfil
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ["jpg", "jpeg", "png"];

    if (!in_array($imageFileType, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos JPG, JPEG y PNG']);
        exit;
    }

    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        // Guardar los datos en la base de datos, incluyendo la ruta de la imagen
        $foto_perfil = basename($_FILES["profile_picture"]["name"]);
        $query = "INSERT INTO usuarios (nombre_usuario, email, password, foto_perfil) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $foto_perfil);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuario registrado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido']);
}
?>
