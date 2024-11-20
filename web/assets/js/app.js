
document.addEventListener("DOMContentLoaded", function () {

    const userId = localStorage.getItem("usuario_id"); // Asegúrate de que el usuario_id esté almacenado en localStorage
    console.log("ID del usuario desde localStorage:", userId);  // Verifica que el valor es correcto


    // ========= MAPA
    // Define la clave de API de OpenCage solo una vez
    const openCageApiKey = '1ebe402d9bd04b219a659b39ea8a8ba1';

    // Función global para inicializar el mapa de Leaflet
    window.initializeMap = function (containerId, location) {
        fetch(`https://api.opencagedata.com/geocode/v1/json?q=${encodeURIComponent(location)}&key=${openCageApiKey}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.results && data.results.length > 0) {
                    const { lat, lng } = data.results[0].geometry;
                    const map = L.map(containerId).setView([lat, lng], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map)
                        .bindPopup(location)
                        .openPopup();
                } else {
                    document.getElementById(containerId).innerText = "No se encontraron coordenadas para esta ubicación.";
                }
            })
            .catch(error => {
                console.error("Error al obtener coordenadas:", error);
                document.getElementById(containerId).innerText = "Error al cargar el mapa.";
            });
    };



    const mapContainers = document.querySelectorAll(".event-map");

    // Iterar sobre cada contenedor y obtener la ubicación desde un atributo de datos
    mapContainers.forEach(container => {
        const location = container.getAttribute("data-location"); // Obtener la ubicación del atributo data-location
        const containerId = container.id;

        if (location) {
            // Llamar a la función para inicializar el mapa en este contenedor
            initializeMap(containerId, location);
        } else {
            console.warn(`No se encontró una ubicación para el mapa con id: ${containerId}`);
        }
    });

    function loadEvents() {
        fetch("http://localhost:8080/api_eventos.php")
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error del servidor: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Datos de eventos recibidos:", data); // Para verificar los nombres en consola
                const eventList = document.getElementById("event-list");
                eventList.innerHTML = "";

                data.forEach(event => {
                    const eventCard = document.createElement("div");
                    eventCard.classList.add("event-card");

                    // Verifica si las propiedades del objeto `event` son correctas
                    const titulo = event.nombre_evento || event.titulo || "Sin título";
                    const descripcion = event.descripcion || "Sin descripción";
                    const fechaHora = event.fecha_hora_evento || event.fecha || "Fecha no disponible";
                    const ubicacion = event.ubicacion || "Ubicación no disponible";
                    const autor = event.autor || "Autor desconocido";
                    const tags = event.tags ? `#${event.tags}` : "";

                    eventCard.innerHTML = `
                    <h2 class="event-title">${titulo}</h2>
                    <p class="event-description">${descripcion}</p>
                    <div class="event-details">
                        <span class="event-detail">Fecha y Hora: ${fechaHora}</span>
                        <span class="event-detail">Ubicación: ${ubicacion}</span>
                        <span class="event-detail">Autor: ${autor}</span>
                    </div>
                    ${tags ? `<span class="event-hashtag">${tags}</span>` : ""}
                    <div id="map-${event.id_evento}" class="event-map" data-location="${ubicacion}"></div>
                `;

                    eventList.appendChild(eventCard);
                    initializeMap(`map-${event.id_evento}`, ubicacion);
                });
            })
            .catch(error => {
                console.error("Error al cargar los eventos:", error);
                const eventList = document.getElementById("event-list");
                eventList.innerHTML = "<p>No se pudieron cargar los eventos. Intenta nuevamente más tarde.</p>";
            });
    }


    // Cargar eventos automáticamente al cargar la página
    document.addEventListener("DOMContentLoaded", loadEvents);

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


    async function handleSubmit(event, action) {
        event.preventDefault();

        const form = document.getElementById("register-form");
        const formData = new FormData(form);

        try {
            const response = await fetch(`http://localhost:8080/api_users.php/${action}`, {
                method: "POST",
                body: formData // Enviar FormData sin el encabezado JSON
            });

            if (response.ok) {
                const data = await response.json();
                alert(data.message); // Mostrar mensaje de éxito
                window.location.href = "register.html"; // Redirigir al formulario de inicio de sesión
            } else {
                const errorData = await response.json();
                alert(errorData.message); // Mostrar el mensaje de error
            }
        } catch (error) {
            console.error("Error de conexión:", error);
        }
    }
    window.handleSubmit = handleSubmit;

    function handleLogin(event) {
        event.preventDefault();

        const form = document.getElementById("login-form");
        const formData = new FormData(form);
        const email = formData.get("email");
        const password = formData.get("password");

        fetch("http://localhost:8080/api_users.php/login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        })
            .then(response => response.json())
            .then(data => {
                console.log("Respuesta completa de la API:", data); // Verifica todos los datos de la respuesta
                console.log("Nombre de usuario:", data.nombre_usuario); // Verifica el nombre de usuario
                console.log("Avatar:", data.avatar); // Verifica el avatar

                if (data.access_token) {
                    localStorage.setItem("access_token", data.access_token);
                    localStorage.setItem("usuario_id", data.usuario_id);
                    localStorage.setItem("expires_at", data.expires_at);
                    localStorage.setItem("nombre_usuario", data.nombre_usuario); // Guardar nombre de usuario
                    localStorage.setItem("avatar", data.avatar); // Guardar avatar
                    alert("Inicio de sesión exitoso");
                    window.location.href = "index.html";
                } else {
                    alert(data.message || "Error de inicio de sesión");
                }
            })
            .catch(error => console.error("Error al iniciar sesión:", error));
    }

    window.handleLogin = handleLogin;

    if (window.location.pathname.includes("register.html")) return;
    // Verificar si el token existe al cargar la página
    const token = localStorage.getItem("access_token");
    // Verificar si el token existe al cargar la página
    if (!token) {
        // Redirige a `register.html` si el usuario no está autenticado
        if (!window.location.pathname.includes("register.html")) {
            window.location.href = "register.html";
            return;
        }
    }


    // ========= INDEX y MYPROFILE =========

    const path = window.location.pathname;
    const postContainer = document.getElementById("postContainer");

    // Verificar si estamos en index.html o myprofile.html
    if (postContainer) {
        // Función para cargar todos los posts de todos los usuarios (para index.html)
        function loadAllPosts() {
            fetch("http://localhost:8080/api_posts.php")
                .then(response => response.json())
                .then(posts => {
                    postContainer.innerHTML = ""; // Limpiar posts actuales antes de cargar nuevos
                    posts.forEach(post => {
                        const postElement = createPostElement(post);
                        postContainer.appendChild(postElement);
                    });
                })
                .catch(error => console.error("Error al cargar los posts:", error));
        }

        // Función para cargar los posts del usuario autenticado (para myprofile.html)
        function loadUserPosts() {
            if (!userId) {
                console.error("User ID no encontrado.");
                return;
            }

            fetch(`http://localhost:8080/api_account.php/all_posts?usuario_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    const posts = data.publicaciones.filter(pub => pub.type === "post");
                    posts.forEach(post => {
                        const postElement = createPostElement(post);
                        postContainer.appendChild(postElement);
                    });
                })
                .catch(error => console.error("Error al cargar los posts del usuario:", error));
        }

        // Decidir cuál función de carga ejecutar
        if (path.includes("myprofile.html")) {
            loadUserPosts(); // Cargar solo los posts del usuario en myprofile.html
        } else if (path.includes("index.html")) {
            loadAllPosts(); // Cargar todos los posts en index.html
        }

        // Función para mostrar imagen en pantalla completa
        window.showImageFullscreen = function (imgSrc) {
            const overlay = document.createElement("div");
            overlay.classList.add("image-overlay");

            overlay.innerHTML = `
                <div class="fullscreen-image-container">
                    <img src="${imgSrc}" alt="Imagen ampliada" class="fullscreen-image">
                    <button class="close-btn" onclick="window.closeImageFullscreen()">✕</button>
                </div>`;

            document.body.appendChild(overlay);
        };


        // Función para cerrar la imagen en pantalla completa
        window.closeImageFullscreen = function () {
            const overlay = document.querySelector(".image-overlay");
            if (overlay) {
                document.body.removeChild(overlay);
            }
        };


        // Añadir evento de clic a las imágenes del carrusel
        document.addEventListener('click', function (event) {
            if (event.target.closest('.carousel-slide img')) {
                const imgSrc = event.target.getAttribute('src');
                showImageFullscreen(imgSrc);
            }
        });

        const newPostForm = document.getElementById("newPostForm");
        const postContentInput = document.getElementById("postContent");
        const postImagesInput = document.getElementById("postImages");

        newPostForm.addEventListener("submit", async function (event) {
            event.preventDefault();

            const postContent = postContentInput.innerText.trim();
            const imageFile = postImagesInput.files[0];

            if (!postContent && !imageFile) {
                alert("No puedes publicar un post vacío. Agrega contenido o una imagen.");
                return;
            }

            const formData = new FormData();
            formData.append("id_usuario", localStorage.getItem("usuario_id"));
            formData.append("contenido", postContent);
            if (imageFile) {
                formData.append("imagen", imageFile);
            }

            try {
                const response = await fetch("http://localhost:8080/api_posts.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message || "Post creado exitosamente");
                    postContentInput.innerText = "";
                    postImagesInput.value = "";
                    previewPostSection.style.display = "none";
                    loadAllPosts(); // Recargar todos los posts para mostrar el nuevo
                } else {
                    alert(result.message || "Error al crear el post");
                }
            } catch (error) {
                console.error("Error al enviar el post:", error);
                alert("Hubo un error al intentar publicar el post.");
            }
        });

        function createCommentSection(postId, comments) {
            const commentsSection = document.createElement("div");
            commentsSection.classList.add("comments-section");

            commentsSection.innerHTML = `
                <h3>Comentarios</h3>
                <div class="comments-list">
                    ${comments.map(comment => `
                        <div class="comment" data-comment-id="${comment.id_comentario}">
                            <div class="comment-header">
                                <img src="${comment.avatar}" alt="Foto de perfil" class="comment-profile-pic">
                                <span class="comment-username">${comment.nombre_usuario}</span>
                            </div>
                            <div class="comment-content">
                                <p>${comment.contenido}</p>
                            </div>
                            <div class="comment-actions">
                                <button class="comment-like-btn ${comment.liked ? 'liked' : ''}">
                                    Me gusta
                                </button>
                            </div>
                        </div>
                    `).join("")}
                </div>
                <div class="new-comment-form">
                    <input type="text" class="new-comment-input" placeholder="Escribe un comentario...">
                    <div class="new-comment-actions">
                        <button class="post-comment-btn">Comentar</button>
                    </div>
                </div>`;

            commentsSection.querySelector(".post-comment-btn").addEventListener("click", async function () {
                const input = commentsSection.querySelector(".new-comment-input");
                const content = input.value.trim();
                if (!content) {
                    alert("No puedes publicar un comentario vacío.");
                    return;
                }
                // API para guardar el comentario
                const response = await fetch("http://localhost:8080/api_interacciones.php/comentario", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ usuario_id: userId, id_objeto: postId, contenido: content, tipo: "publicacion" })
                });

                if (response.ok) {
                    alert("Comentario registrado exitosamente");
                    loadComments(postId, commentsSection); // Recargar comentarios
                    input.value = ""; // Limpiar el campo de entrada
                }
            });

            return commentsSection;
        }

        async function loadComments(postId, commentsSection) {
            const response = await fetch(`http://localhost:8080/api_interacciones.php/comentarios?id_objeto=${postId}&tipo=publicacion`);
            const data = await response.json();
            if (response.ok && data.comentarios) {
                const commentsHTML = data.comentarios.map(comment => `
                    <div class="comment" data-comment-id="${comment.id_comentario}">
                        <div class="comment-header">
                            <img src="${comment.avatar}" alt="Foto de perfil" class="comment-profile-pic">
                            <span class="comment-username">${comment.nombre_usuario}</span>
                        </div>
                        <div class="comment-content">
                            <p>${comment.contenido}</p>
                        </div>
                        <div class="comment-actions">
                            <button class="comment-like-btn ${comment.liked ? 'liked' : ''}">Me gusta</button>
                        </div>
                    </div>
                `).join("");
                commentsSection.querySelector(".comments-list").innerHTML = commentsHTML;
            }
        }

        function createPostElement(post) {

            const postDiv = document.createElement("div");
            postDiv.classList.add("post");

            // Asegúrate de que el post tenga una propiedad de identificador correcta
            const postId = post.id_publi || post.id || post.id_publicacion;

            if (!postId) {
                console.error("ID de la publicación no encontrado:", post);
                return;
            }

            // Procesar imágenes
            let imagesHTML = '';
            if (post.imagen) {
                imagesHTML = `
                    <div class="media-content">
                        <img src="${post.imagen}" alt="Imagen del post" class="post-image" onclick="showImageFullscreen('${post.imagen}')">
                    </div>`;
            }

            // Generar el HTML de la imagen si existe una URL válida
            if (post.imagen && typeof post.imagen === "string") {
                imagesHTML = `
                    <div class="media-content">
                        <img src="${post.imagen}" alt="Imagen del post" class="post-image" onclick="showImageFullscreen('${post.imagen}')">
                    </div>`;
            }


            // Construcción del contenido del post
            const postContent = `
                <div class="post-content">
                    ${post.contenido ? `<p>${post.contenido}</p>` : ""}
                    <span class="date">Fecha de Creación: ${post.created_at}</span>
                </div>`;

            const avatar = post.avatar || "http://localhost:8080/Wheelz-main/uploads/avatars/default-avatar.png"; // Avatar predeterminado si falta
            const postHeader =
                `<div class="post-header">
                        <div class="profile-pic">
                            <!-- Avatar del usuario con el data-user-id -->
                            <img src="${avatar}" alt="Avatar" class="user-avatar">
                        </div>
                        <h3 class="post-username">${post.nombre_usuario}</h3>
                        <button class="report-btn abrir-reporte">
                            <img src="https://www.svgrepo.com/show/479045/exclamation-mark.svg" alt="Reportar">
                        </button>
                    </div>`;


            const postFooter = `
                <div class="post-footer">
                    <button class="like-btn">
                        <img src="https://www.svgrepo.com/show/473234/heart.svg" alt="Me gusta" class="heart-icon">
                    </button>
                    <button class="comment-btn" onclick="togglePostComments(${postId})">
                        <img src="https://www.svgrepo.com/show/473144/comment-right.svg" alt="Comentarios">
                    </button>
                    <button>
                        <img src="https://www.svgrepo.com/show/379628/share.svg" alt="Compartir">
                    </button>
                </div>
                <div class="comments-container hidden" id="comments-${postId}">
                    <!-- Aquí se cargarán los comentarios dinámicamente -->
                    <div class="new-comment">
                        <input type="text" id="newComment-${postId}" placeholder="Escribe un comentario...">
                        <button onclick="addComment(${postId})">Comentar</button>
                    </div>
                </div>
            `;


            postDiv.innerHTML = postHeader + imagesHTML + postContent + postFooter;

            // Función del botón de "like"
            const likeButton = postDiv.querySelector('.like-btn');
            const heartIcon = likeButton.querySelector('.heart-icon');

            // Alterna el icono de "like" en cada clic
            likeButton.addEventListener('click', () => {
                const isLiked = likeButton.classList.toggle('liked'); // Cambia la clase 'liked' visualmente
                heartIcon.src = isLiked
                    ? 'https://www.svgrepo.com/show/473235/heart-filled.svg'
                    : 'https://www.svgrepo.com/show/473234/heart.svg';
            });

            return postDiv;
        }

        window.togglePostComments = function (postId) {
            const commentsContainer = document.getElementById(`comments-${postId}`);
            // Solo cargar los comentarios si no están ya cargados
            if (!commentsContainer.classList.contains("loaded")) {
                loadPostComments(postId);
            }
            // Alternar la visibilidad sin recargar los comentarios
            commentsContainer.classList.toggle("hidden");
        };

        window.addPostComment = function (postId) {
            const newCommentInput = document.getElementById(`newComment-${postId}`);
            const content = newCommentInput.value;
            const userName = localStorage.getItem("nombre_usuario") || "Usuario";
            const userAvatar = localStorage.getItem("avatar") || "http://localhost:8080/Wheelz-main/uploads/default-image.png";

            if (!content.trim()) {
                alert("El comentario no puede estar vacío.");
                return;
            }

            fetch('http://localhost:8080/api_comentarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_publi: postId,
                    id_usuario: parseInt(userId, 10),
                    contenido: content
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        const commentsContainer = document.getElementById(`comments-${postId}`);
                        commentsContainer.insertAdjacentHTML('afterbegin', `
                            <div class="comment">
                                <div class="comment-header">
                                    <img src="${userAvatar}" alt="Avatar" class="comment-avatar">
                                    <span class="comment-author">${userName}</span>
                                    <span class="comment-date">${new Date().toLocaleString()}</span>
                                </div>
                                <p class="comment-text">${content}</p>
                            </div>
                        `);
                        newCommentInput.value = ''; // Limpiar el campo de entrada después de añadir el comentario
                    }
                })
                .catch(error => console.error("Error al añadir comentario en publicación:", error));
        };


        function loadPostComments(postId) {
            const commentsContainer = document.getElementById(`comments-${postId}`);

            fetch(`http://localhost:8080/api_comentarios.php?id_publi=${postId}`)
                .then(response => response.json())
                .then(data => {
                    const commentsHTML = data.map(comment => `
                        <div class="comment">
                            <div class="comment-header">
                                <img src="${comment.avatar || 'http://localhost:8080/Wheelz-main/uploads/default-image.png'}" alt="Avatar" class="comment-avatar">
                                <span class="comment-author">${comment.autor}</span>
                                <span class="comment-date">${comment.created_at}</span>
                            </div>
                            <p class="comment-text">${comment.contenido}</p>
                        </div>
                    `).join('');

                    commentsContainer.innerHTML = commentsHTML + `
                        <div class="new-comment">
                            <input type="text" id="newComment-${postId}" placeholder="Escribe un comentario...">
                            <button onclick="addPostComment(${postId})">Comentar</button>
                        </div>
                    `;
                    commentsContainer.classList.add("loaded"); // Marcar como cargado
                })
                .catch(error => console.error("Error al cargar comentarios de publicación:", error));
        }



        // Selección de elementos
        const previewPostSection = document.querySelector(".preview-post-section");
        const previewPost = document.getElementById("preview-post");

        // Función para actualizar la vista previa
        function updatePreview() {
            const userName = localStorage.getItem("nombre_usuario") || "Usuario";
            const userPhoto = localStorage.getItem("avatar") || "/Wheelz/uploads/default-image.png";
            const postContent = postContentInput.innerText.trim();
            const postDate = new Date().toLocaleString();

            const imageFile = postImagesInput.files[0];
            let imageUrl = "";
            if (imageFile) {
                imageUrl = URL.createObjectURL(imageFile);
            }

            if (postContent || imageUrl) {
                previewPostSection.style.display = "block";
            } else {
                previewPostSection.style.display = "none";
                return;
            }

            const postHeader = `
            <div class="post-header">
                <div class="profile-pic">
                    <img src="${userPhoto}" alt="Foto de perfil" class="profile-image">
                </div>
                <h3 class="nombre_usuario">${userName}</h3>
                <button class="report-btn" onclick="    ">
                    <img src="https://www.svgrepo.com/show/479045/exclamation-mark.svg" alt="Reportar">
                </button>
            </div>`;

            const postContentHTML = `
            <div class="post-content">
                ${postContent ? `<p>${postContent}</p>` : ""}
                <span class="date">Fecha de Creación: ${postDate}</span>
            </div>`;

            const postFooter = `
            <div class="post-footer">
                <button class="like-btn">
                    <img src="https://www.svgrepo.com/show/473234/heart.svg" alt="Me gusta" class="heart-icon">
                </button>
                <button class="comment-btn">
                    <img src="https://www.svgrepo.com/show/473144/comment-right.svg" alt="Comentarios">
                </button>
                <button>
                    <img src="https://www.svgrepo.com/show/379628/share.svg" alt="Compartir">
                </button>
            </div>`
                ;



            const imageHTML = imageUrl ? `<img src="${imageUrl}" alt="Imagen del post" class="post-image" onclick="showImageFullscreen('${imageUrl}')">` : "";

            previewPost.innerHTML = postHeader + imageHTML + postContentHTML + postFooter;
        }


        // Actualizar vista previa al cambiar el contenido
        postContentInput.addEventListener("input", updatePreview);

        // Actualizar vista previa al seleccionar una imagen
        postImagesInput.addEventListener("change", updatePreview);

        // Escuchar cambios en las imágenes del post
        postImagesInput.addEventListener("change", (event) => {
            const file = event.target.files[0];
            const imageUrl = file ? URL.createObjectURL(file) : null;
            updatePreview(imageUrl);
        });
        postContentInput.addEventListener("input", () => updatePreview());

    }




    // ========= Cargar foros en forums.html =========

    if (window.location.pathname.includes("forums.html")) {
        loadForums();
    }

    function loadForums() {
        fetch("http://localhost:8080/api_foros.php")
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error del servidor: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Datos de foros recibidos:", data);

                if (!Array.isArray(data)) {
                    throw new Error("La respuesta no es un arreglo válido.");
                }

                const forumList = document.getElementById("forum-list");
                forumList.innerHTML = ""; // Limpiar el contenedor antes de cargar nuevos datos

                data.forEach(forum => {
                    // Asegúrate de que el ID del foro esté presente
                    const forumId = forum.id;

                    const forumCard = document.createElement("div");
                    forumCard.classList.add("forum-post");

                    forumCard.innerHTML = `
                        <div class="post-header-foro">
                            <div>
                                <h2 class="post-title">${forum.titulo}</h2>
                                <p class="post-meta">Publicado por ${forum.autor} el ${forum.created_at}</p>
                            </div>
                            <button class="report-btn">!</button>
                        </div>
                        <div class="post-content">
                            <p>${forum.contenido}</p>
                        </div>
                        <div class="post-tags">
                            ${forum.hashtag ? forum.hashtag.split(',').map(tag => `<span class="tag">#${tag.trim()}</span>`).join(' ') : ""}
                        </div>
                        <div class="post-actions">
                            <div>
                                <button class="like-btn">
                                    <img src="https://www.svgrepo.com/show/473234/heart.svg" alt="Me gusta" class="heart-icon">
                                </button>
                                <button class="comment-btn" onclick="toggleForumComments(${forumId})">
                                    <img src="https://www.svgrepo.com/show/473144/comment-right.svg" alt="Comentarios">
                                </button>
                            </div>
                            <button>
                                <img src="https://www.svgrepo.com/show/379628/share.svg" alt="Compartir">
                            </button>
                        </div>
                        <div class="comments-container hidden" id="comments-${forumId}">
                            <!-- Contenedor de comentarios -->
                            <div class="new-comment">
                                <input type="text" id="newComment-${forumId}" placeholder="Escribe un comentario...">
                                <button onclick="addForumComment(${forumId})">Comentar</button>
                            </div>
                        </div>
                    `;

                    forumList.appendChild(forumCard);
                });
            })
            .catch(error => {
                console.error("Error al cargar los foros:", error);
                const forumList = document.getElementById("forum-list");
                if (forumList) {
                    forumList.innerHTML = "<p>No se pudieron cargar los foros. Intenta nuevamente más tarde.</p>";
                }
            });
    }


    function loadForumComments(forumId) {
        const commentsContainer = document.getElementById(`comments-${forumId}`);

        fetch(`http://localhost:8080/api_comentarios.php?foro_id=${forumId}`)
            .then(response => response.json())
            .then(data => {
                const commentsHTML = data.map(comment => `
                    <div class="comment">
                        <div class="comment-header">
                            <img src="${comment.avatar || 'http://localhost:8080/Wheelz-main/uploads/default-image.png'}" alt="Avatar" class="comment-avatar">
                            <span class="comment-author">${comment.autor}</span>
                            <span class="comment-date">${comment.created_at}</span>
                        </div>
                        <p class="comment-text">${comment.contenido}</p>
                    </div>
                `).join('');

                commentsContainer.innerHTML = commentsHTML + `
                    <div class="new-comment">
                        <input type="text" id="newComment-${forumId}" placeholder="Escribe un comentario...">
                        <button onclick="addForumComment(${forumId})">Comentar</button>
                    </div>
                `;
                commentsContainer.classList.add("loaded");
            })
            .catch(error => console.error("Error al cargar comentarios de foro:", error));
    }

    window.toggleForumComments = function (forumId) {
        const commentsContainer = document.getElementById(`comments-${forumId}`);
        if (!commentsContainer.classList.contains("loaded")) {
            loadForumComments(forumId);
        }
        commentsContainer.classList.toggle("hidden");
    };

    window.addForumComment = function (forumId) {
        const newCommentInput = document.getElementById(`newComment-${forumId}`);
        const content = newCommentInput.value;
        const userName = localStorage.getItem("nombre_usuario") || "Usuario";
        const userAvatar = localStorage.getItem("avatar") || "http://localhost:8080/Wheelz-main/uploads/default-image.png";

        if (!content.trim()) {
            alert("El comentario no puede estar vacío.");
            return;
        }

        fetch('http://localhost:8080/api_comentarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                foro_id: forumId,
                id_usuario: parseInt(userId, 10),
                contenido: content
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    const commentsContainer = document.getElementById(`comments-${forumId}`);
                    commentsContainer.insertAdjacentHTML('afterbegin', `
                        <div class="comment">
                            <div class="comment-header">
                                <img src="${userAvatar}" alt="Avatar" class="comment-avatar">
                                <span class="comment-author">${userName}</span>
                                <span class="comment-date">${new Date().toLocaleString()}</span>
                            </div>
                            <p class="comment-text">${content}</p>
                        </div>
                    `);
                    newCommentInput.value = ''; // Limpiar el campo de entrada después de añadir el comentario
                }
            })
            .catch(error => console.error("Error al añadir comentario en foro:", error));
    };

    if (window.location.pathname.endsWith("forums.html")) {
        function createNewForum(event) {
            event.preventDefault(); // Evita el envío del formulario por defecto.

            // Capturar los datos del formulario
            const contenido = document.getElementById("postContent").value;
            const categoria = document.getElementById("postCategory").value;
            const hashtag = document.getElementById("postHashtags").value;

            // Crear el objeto de datos
            const formData = {
                usuario_id: localStorage.getItem("usuario_id"), // Dinámico basado en el usuario autenticado
                contenido,
                categoria,
                hashtag,
            };

            console.log("Datos enviados al backend:", formData);

            // Enviar los datos al backend usando fetch
            fetch("http://localhost:8080/api_foros.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(formData),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("No se pudo crear la publicación.");
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("Publicación creada:", data);

                    // Actualizar la lista de foros
                    loadForums();

                    // Cerrar el modal y resetear el formulario
                    closeModal();
                    document.getElementById("newPostForm").reset();
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Error al crear la publicación. Intenta nuevamente.");
                });
        }
    }

    const newPostForm = document.getElementById("newPostForm");
    if (newPostForm) {
        newPostForm.addEventListener("submit", createNewForum);
    }


    window.handleLogin = handleLogin;







    window.getUserProfile = function (userId) {
        // Actualiza la URL para hacer la solicitud al endpoint correcto
        return fetch(`http://localhost:8080/api_account.php/user_profile?usuario_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.user) {
                    return data.user;
                } else {
                    throw new Error("Usuario no encontrado");
                }
            });
    }

    window.openUserProfile = function (userId) {
        // Llamamos a la función getUserProfile para obtener los datos del usuario
        getUserProfile(userId)
            .then(user => {
                // Llenar el modal con los datos del usuario
                document.getElementById('profileAvatar').src = user.avatar;
                document.getElementById('profileUsername').textContent = user.nombre_usuario;
                document.getElementById('profileBio').textContent = user.biografia || 'Sin biografía';

                // Mostrar el modal
                document.getElementById('profileModal').classList.add('show');
            })
            .catch(error => {
                console.error("Error al obtener el perfil del usuario:", error);
                alert("No se pudo cargar la información del usuario.");
            });
    }






    function loadUserPosts(userId) {
        fetch(`http://localhost:8080/api_account.php/all_posts?usuario_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                const postsContainer = document.getElementById('userPostsContainer');
                postsContainer.innerHTML = ''; // Limpiar el contenedor

                if (data.publicaciones) {
                    data.publicaciones.forEach(post => {
                        const postElement = document.createElement('p');
                        postElement.textContent = post.contenido || "Sin contenido";
                        postsContainer.appendChild(postElement);
                    });
                } else {
                    postsContainer.innerHTML = '<p>No se encontraron publicaciones.</p>';
                }
            })
            .catch(error => {
                console.error("Error al cargar las publicaciones del usuario:", error);
                document.getElementById('userPostsContainer').innerHTML = '<p>No se pudo cargar las publicaciones.</p>';
            });
    }


    // BARRA DE BUSQUEDA DE UNA JAJAJAAJAJ
    // Cargar el header dinámicamente y ejecutar los scripts una vez cargado
    fetch('./partials/header.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById("header-placeholder").innerHTML = data;

            // Inicializar el script de la barra de búsqueda después de cargar el header
            initializeSearchBar();
        })
        .catch(error => console.error('Error cargando el header:', error));

    // Función para inicializar la barra de búsqueda
    function initializeSearchBar() {
        const userSearchInput = document.getElementById("userSearchInput");
        const userResultsContainer = document.getElementById("userResults");
        const loggedInUserId = localStorage.getItem("usuario_id"); // ID del usuario logueado

        // Verificar si los elementos existen antes de trabajar con ellos
        if (!userSearchInput || !userResultsContainer) {
            console.warn("Elementos de búsqueda no encontrados en esta página.");
            return; // Salir si los elementos no están presentes
        }

        userSearchInput.addEventListener("input", () => {
            const searchTerm = userSearchInput.value.trim();

            if (searchTerm.length > 0) {
                fetch(`http://localhost:8080/api_search.php?search=${searchTerm}&exclude_id=${loggedInUserId}`)
                    .then(response => response.json())
                    .then(data => {
                        userResultsContainer.innerHTML = ""; // Limpia los resultados previos
                        if (data.length > 0) {
                            data.forEach(user => {
                                const userItem = document.createElement("div");
                                userItem.classList.add("search-result-item");

                                // URL de la imagen del usuario o la predeterminada
                                const avatarUrl = user.avatar.startsWith('http')
                                    ? user.avatar
                                    : `/Wheelz/uploads/${user.avatar}`;

                                // Verificar si la imagen existe antes de mostrarla
                                fetch(avatarUrl, { method: 'HEAD' })
                                    .then(response => {
                                        const imgSrc = response.ok ? avatarUrl : '/Wheelz/uploads/default-image.png';
                                        userItem.innerHTML = `
                                    <img src="${imgSrc}" 
                                        alt="${user.nombre_usuario}" 
                                        class="search-avatar">
                                    <span class="search-username">${user.nombre_usuario}</span>
                                `;

                                        // Redirigir al perfil del usuario al hacer clic
                                        userItem.addEventListener("click", () => {
                                            window.location.href = `http://localhost:8080/Wheelz-main/web/profile.html?id=${user.id_usuario}`;
                                        });

                                        userResultsContainer.appendChild(userItem);
                                    })
                                    .catch(() => {
                                        // Si falla la verificación, usa la imagen predeterminada
                                        userItem.innerHTML = `
                                    <img src="/Wheelz/uploads/default-image.png" 
                                        alt="${user.nombre_usuario}" 
                                        class="search-avatar">
                                    <span class="search-username">${user.nombre_usuario}</span>
                                `;

                                        userItem.addEventListener("click", () => {
                                            window.location.href = `http://localhost:8080/Wheelz-main/web/profile.html?id=${user.id_usuario}`;
                                        });

                                        userResultsContainer.appendChild(userItem);
                                    });
                            });
                        } else {
                            userResultsContainer.innerHTML = "<p>No se encontraron usuarios.</p>";
                        }
                    })
                    .catch(error => {
                        console.error("Error al buscar usuarios:", error);
                        userResultsContainer.innerHTML = "<p>Error al buscar usuarios.</p>";
                    });
            } else {
                userResultsContainer.innerHTML = ""; // Limpia si no hay texto
            }
        });
    }

});