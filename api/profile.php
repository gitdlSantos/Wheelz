<?php
session_start();
require 'config.php';  // Incluye el archivo de conexión a la base de datos

// Verifica si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

// Obtiene el ID del usuario desde la sesión
$user_id = $_SESSION['user_id'];

// Prepara la consulta SQL para obtener los datos del usuario
$query = $conn->prepare("SELECT nombre_usuario, foto_perfil FROM usuarios WHERE id = ?");
$query->bind_param('i', $user_id);
$query->execute();
$result = $query->get_result();

// Verifica si encontró datos
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    echo json_encode($user_data);  // Devuelve los datos en formato JSON
} else {
    echo json_encode(['error' => 'No se encontraron datos para el usuario']);
}

$query->close();
$conn->close();
?>
