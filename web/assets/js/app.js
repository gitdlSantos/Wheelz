document.addEventListener("DOMContentLoaded", function () {
    const logoutButton = document.getElementById("logout-btn");

    if (logoutButton) {
        logoutButton.addEventListener("click", function () {
            fetch("http://localhost/Wheelz/api/logout.php", {
                method: "GET",
                credentials: "same-origin" // Asegura que las cookies de sesión se envíen con la solicitud
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = "http://localhost/Wheelz/web/index.html"; // Redirige a la página de inicio de sesión
                    } else {
                        alert("Error al cerrar la sesión");
                    }
                })
                .catch(error => console.error("Error al cerrar la sesión:", error));
        });
    }
});
