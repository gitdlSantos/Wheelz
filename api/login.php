<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscar al usuario por el email
    $query = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // Verificar si el usuario existe y la contraseña es correcta
    if ($usuario && password_verify($password, $usuario['password'])) {
        // Guardar información del usuario en la sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['nombre_usuario'];

        echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso']);
        // Redirigir a la página de inicio después del login exitoso
        header("Location: ../web/index.html");
        exit(); // Asegurarse de que el script se detenga después de la redirección
    } else {
        echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido']);
}
?>
