<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    // Si no está autenticado, redirigir al login
    header("Location: register.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div id="header-placeholder"></div>

    <main class="profile-container">
        <div class="profile-header">
            <div class="profile-pic-header">
                <img class="my-profile-image" src="https://via.placeholder.com/150" alt="Imagen de perfil">
                <!-- Imagen predeterminada -->
            </div>
            <div class="profile-info">
                <h2 class="my-username">@usuario123</h2> <!-- Nombre de usuario predeterminado -->
                <p class="bio">Esta es mi biografia. Aqui puedo contar un poco sobre mi y mis intereses.</p>
                <div class="profile-stats">
                    <span>120 publicaciones</span>
                    <span>1.5k seguidores</span>
                    <span>500 seguidos</span>
                </div>
            </div>
            <div class="profile-actions">
                <button class="edit-profile-btn">Editar perfil</button>
                <button class="settings-btn">Configuracion</button>
                <button id="logout-btn">Cerrar Sesión</button>
            </div>
        </div>
        <div class="edit-profile">
            <form id="editProfileForm" enctype="multipart/form-data">
                <label for="profile_image">Cambiar foto de perfil</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">

                <label for="username">Cambiar nombre de usuario</label>
                <input type="text" id="username" name="username" placeholder="Nuevo nombre de usuario">

                <button type="submit">Guardar cambios</button>
            </form>
        </div>

        <div class="contenedor-ventana" id="contenedor-ventana">
            <div class="ventana-reporte">
                <button id="reportar-publicacion" onclick="">Reportar Publicacion</button>
                <p></p>
                <button id="cancelar-reporte" onclick="cancelar_reporte()">Cancelar</button>
            </div>
        </div>

       

    </main>

    <div id="footer-placeholder"></div>

    <script>
        document.getElementById("header-placeholder").innerHTML = fetch('partials/header.html')
            .then(response => response.text())
            .then(data => document.getElementById("header-placeholder").innerHTML = data);
    </script>

    <script src="assets/js/app.js"></script> <!-- Carga del JavaScript -->


</body>

</html>