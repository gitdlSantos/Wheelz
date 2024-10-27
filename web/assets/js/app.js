document.addEventListener("DOMContentLoaded", function () {

    // ========= LOGOUT
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

    // ========= REGISTRO Y LOGIN

    // Botón de selección de foto de perfil
    const profilePictureInput = document.querySelector("#profile-picture");
    const profilePictureLabel = document.querySelector(".profile-picture-label");

    if (profilePictureInput && profilePictureLabel) {
        profilePictureInput.addEventListener("change", () => {
            if (profilePictureInput.files.length > 0) {
                profilePictureLabel.textContent = "Foto Seleccionada";
                profilePictureLabel.classList.add("selected");
            } else {
                profilePictureLabel.textContent = "Seleccionar Foto";
                profilePictureLabel.classList.remove("selected");
            }
        });
    }

    // Manejo de registro y login
    async function handleSubmit(event, type) {
        event.preventDefault();

        const form = type === 'login' ? document.getElementById("login-form") : document.getElementById("register-form");
        const formData = new FormData(form);

        let apiUrl;
        let dataToSend;

        if (type === 'login') {
            apiUrl = "http://localhost/Wheelz/api/api_users.php/login";
            dataToSend = JSON.stringify({
                email: formData.get("email"),
                password: formData.get("password")
            });
        } else {
            const password = formData.get("password");
            const confirmPassword = formData.get("confirm_password");
            if (password !== confirmPassword) {
                alert("Las contraseñas no coinciden");
                return;
            }
            apiUrl = "http://localhost/Wheelz/api/api_users.php/register";
            dataToSend = formData;
        }

        try {
            const response = await fetch(apiUrl, {
                method: "POST",
                headers: type === 'login' ? { "Content-Type": "application/json" } : {},
                body: type === 'login' ? dataToSend : formData,
            });

            const result = await response.json();
            if (response.ok) {
                console.log(type === 'login' ? "Inicio de sesión exitoso" : "Registro exitoso");
                console.log("Respuesta de la API:", result);

                if (type === 'login') {
                    window.location.href = "http://localhost/Wheelz/web/index.html";
                } else {
                    alert("Registro exitoso. Ya puedes iniciar sesión.");
                    window.location.href = "http://localhost/Wheelz/web/register.html";
                }
            } else {
                console.error("Respuesta de la API:", result);
                alert(result.message || "Error en la solicitud");
            }
        } catch (error) {
            console.error("Error al conectar con el servidor:", error);
            alert("Error al conectar con el servidor");
        }
    }

    window.handleSubmit = handleSubmit;

});
