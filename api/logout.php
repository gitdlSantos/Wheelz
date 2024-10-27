<?php
session_start();
header("Content-Type: application/json");

// Destruye la sesión actual
session_unset();
session_destroy();

// Respuesta de confirmación
echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente']);
?>
