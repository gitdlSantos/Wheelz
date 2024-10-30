<?php
require_once '../api/config.php';

session_start();

if (!isset($_SESSION['token']) || !isset($_SESSION['user_id'])) {
    header("Location: register.html");
    exit();
}

$token = $_SESSION['token'];
$user_id = $_SESSION['user_id'];

// 1. Obtener el conteo de publicaciones
$apiUrlPosts = "http://localhost/Wheelz/api/api_account.php/count_posts?usuario_id=$user_id";
$optionsPosts = ["http" => ["method" => "GET"]];
$responsePosts = file_get_contents($apiUrlPosts, false, stream_context_create($optionsPosts));

if ($responsePosts) {
    $postsData = json_decode($responsePosts, true);
    $totalPosts = $postsData['total'] ?? 0;
} else {
    $totalPosts = 0;
    echo "Error: No se pudo obtener la información de publicaciones.";
}

// 2. Obtener el conteo de seguidores y seguidos
$apiUrlFollowers = "http://localhost/Wheelz/api/api_account.php/count_followers?usuario_id=$user_id";
$responseFollowers = file_get_contents($apiUrlFollowers, false, stream_context_create($optionsPosts));

if ($responseFollowers) {
    $followersData = json_decode($responseFollowers, true);
    $followersCount = $followersData['followers_count'] ?? 0;
    $followingCount = $followersData['following_count'] ?? 0;
} else {
    $followersCount = 0;
    $followingCount = 0;
}

// 3. Obtener datos básicos del perfil, incluyendo la biografía y la imagen de perfil
$apiUrlProfile = "http://localhost/Wheelz/api/api_users.php/profile";
$optionsProfile = [
    "http" => [
        "header" => "Authorization: $token\r\n",
        "method" => "GET"
    ]
];
$responseProfile = file_get_contents($apiUrlProfile, false, stream_context_create($optionsProfile));

if ($responseProfile) {
    $userData = json_decode($responseProfile, true);
    $relativePath = $userData['user']['foto_perfil'] ?? 'default.png';
    $profilePicture = "http://localhost/Wheelz/uploads/" . ltrim($relativePath, '/');
    $biografia = $userData['user']['biografia'] ?? "Aquí va la biografía del usuario...";
    $username = $userData['user']['nombre_usuario'] ?? "Usuario";
} else {
    $profilePicture = "http://localhost/Wheelz/uploads/default.png";
    $biografia = "Aquí va la biografía del usuario...";
    $username = "Usuario";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>

<body>
    <div id="header-placeholder"></div>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-pic-wrapper">
                <div class="profile-pic-header">
                    <img id="profile-image-usr" src="<?php echo htmlspecialchars($profilePicture); ?>"
                        alt="Imagen de perfil">
                </div>
            </div>
            <div class="profile-info">
                <h2 class="profile-username">@<?php echo htmlspecialchars($username); ?></h2>
                <p class="profile-bio"><?php echo htmlspecialchars($biografia); ?></p>
                <p class="profile-stats">
                    <span class="profile-posts-count"><?php echo $totalPosts; ?> publicaciones</span> |
                    <span class="profile-followers-count"><?php echo $followersCount; ?> seguidores</span> |
                    <span class="profile-following-count"><?php echo $followingCount; ?> seguidos</span>
                </p>
            </div>
        </div>
        <div class="profile-actions">
            <button class="edit-profile-btn">Editar perfil</button>
            <button class="settings-btn">Configuración</button>
            <button id="logout-btn" onclick="logout()">Cerrar Sesión</button>
        </div>
    </div>
    <script src="./assets/js/app.js"></script>
    <script>
        fetch('./partials/header.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById("header-placeholder").innerHTML = data;
            })
            .catch(error => console.error('Error cargando el header:', error));
    </script>
    <script>
        function logout() {
            localStorage.removeItem('token');
            window.location.href = "register.html";
        }
    </script>
</body>

</html>