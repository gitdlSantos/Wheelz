<?php
include 'config.php';
session_start(); // Iniciar la sesión para guardar datos del usuario

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

        // Redirigir a la página de inicio después del login exitoso
        header("Location: ../pages/index.html");
        exit(); // Asegurarse de que el script se detenga después de la redirección
    } else {
        // Mostrar mensaje de error si el login falla
        echo "Email o contraseña incorrectos.";
    }
}
?>
