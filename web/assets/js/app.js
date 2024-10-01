// Verificar si existe el botón de comentarios antes de agregar el evento
const commentBtn = document.querySelector('.comment-btn');
if (commentBtn) {
    commentBtn.addEventListener('click', function() {
        const commentsSection = document.querySelector('.comments-section');
        commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
    });
}

// Verificar si existen botones de "me gusta" en comentarios antes de agregar los eventos
const commentLikeBtns = document.querySelectorAll('.comment-like-btn');
if (commentLikeBtns.length > 0) {
    commentLikeBtns.forEach(button => {
        button.addEventListener('click', function() {
            this.textContent = this.textContent === 'Me gusta' ? 'Te gusta' : 'Me gusta';
        });
    });
}

// Verificar si existen botones de respuesta en comentarios antes de agregar los eventos
const commentReplyBtns = document.querySelectorAll('.comment-reply-btn');
if (commentReplyBtns.length > 0) {
    commentReplyBtns.forEach(button => {
        button.addEventListener('click', function() {
            const commentContent = this.closest('.comment').querySelector('.comment-content');
            const replyInput = document.createElement('textarea');
            replyInput.className = 'new-comment-input';
            replyInput.placeholder = 'Escribe tu respuesta...';
            commentContent.appendChild(replyInput);
        });
    });
}

// Verificar si existe el botón para publicar un nuevo comentario
const postCommentBtn = document.querySelector('.post-comment-btn');
if (postCommentBtn) {
    postCommentBtn.addEventListener('click', function() {
        const commentText = document.querySelector('.new-comment-input').value;
        if (commentText.trim() !== '') {
            const newComment = createCommentElement(commentText, 'TuUsuario', false);
            document.querySelector('.comments-section').insertBefore(newComment, document.querySelector('.new-comment-form'));
            document.querySelector('.new-comment-input').value = '';
        }
    });
}

const abrir_reporte = document.getElementById('abrir-reporte');
const contenedor_ventana = document.getElementById('contenedor-ventana');
const cancelar_reporte = document.getElementById('cancelar-reporte');

if (abrir_reporte && contenedor_ventana && cancelar_reporte) {
    abrir_reporte.addEventListener('click', () => {
        contenedor_ventana.classList.add('mostrar_report');
    });

    cancelar_reporte.addEventListener('click', () => {
        contenedor_ventana.classList.remove('mostrar_report');
    });
}



document.addEventListener('DOMContentLoaded', function() {
    const likeButtons = document.querySelectorAll('.like-btn');
    
    if (likeButtons.length > 0) {  // Verificar si hay botones de "me gusta"
        likeButtons.forEach(function(likeButton) {
            const heartIcon = likeButton.querySelector('.heart-icon');

            likeButton.addEventListener('click', function() {
                this.classList.toggle('liked');
                if (this.classList.contains('liked')) {
                    heartIcon.src = 'https://www.svgrepo.com/show/473235/heart-filled.svg';
                } else {
                    heartIcon.src = 'https://www.svgrepo.com/show/473234/heart.svg';
                }
            });
        });
    }
});


// ========== COMENTARIOS DE LA PUBLICACIÓN ============
// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Función para abrir/cerrar la sección de comentarios
    const commentBtn = document.querySelector('.comment-btn');
    if (commentBtn) {
        commentBtn.addEventListener('click', function() {
            const commentsSection = document.querySelector('.comments-section');
            if (commentsSection) {
                commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
            }
        });
    }

    // Función para manejar el "me gusta" en comentarios
    const commentLikeBtns = document.querySelectorAll('.comment-like-btn');
    if (commentLikeBtns.length > 0) {
        commentLikeBtns.forEach(button => {
            button.addEventListener('click', function() {
                this.textContent = this.textContent === 'Me gusta' ? 'Te gusta' : 'Me gusta';
            });
        });
    }

    // Función para manejar la respuesta a comentarios
    const commentReplyBtns = document.querySelectorAll('.comment-reply-btn');
    if (commentReplyBtns.length > 0) {
        commentReplyBtns.forEach(button => {
            button.addEventListener('click', function() {
                const commentContent = this.closest('.comment').querySelector('.comment-content');
                const replyInput = document.createElement('textarea');
                replyInput.className = 'new-comment-input';
                replyInput.placeholder = 'Escribe tu respuesta...';
                commentContent.appendChild(replyInput);
            });
        });
    }

    // Publicar un nuevo comentario
    const postCommentBtn = document.querySelector('.post-comment-btn');
    if (postCommentBtn) {
        postCommentBtn.addEventListener('click', function() {
            const commentText = document.querySelector('.new-comment-input')?.value;
            if (commentText && commentText.trim() !== '') {
                const newComment = createCommentElement(commentText, 'TuUsuario', false);
                const commentsSection = document.querySelector('.comments-section');
                const newCommentForm = document.querySelector('.new-comment-form');
                if (commentsSection && newCommentForm) {
                    commentsSection.insertBefore(newComment, newCommentForm);
                    document.querySelector('.new-comment-input').value = '';
                }
            }
        });
    }

    // Manejo de la ventana de reporte
    const abrir_reporte = document.getElementById('abrir-reporte');
    const contenedor_ventana = document.getElementById('contenedor-ventana');
    const cancelar_reporte = document.getElementById('cancelar-reporte');
    
    if (abrir_reporte && contenedor_ventana && cancelar_reporte) {
        abrir_reporte.addEventListener('click', () => {
            contenedor_ventana.classList.add('mostrar_report');
        });
        
        cancelar_reporte.addEventListener('click', () => {
            contenedor_ventana.classList.remove('mostrar_report');
        });
    }

    // Manejar "me gusta" en otros botones
    const likeButtons = document.querySelectorAll('.like-btn');
    if (likeButtons.length > 0) {
        likeButtons.forEach(function(likeButton) {
            const heartIcon = likeButton.querySelector('.heart-icon');
            likeButton.addEventListener('click', function() {
                this.classList.toggle('liked');
                heartIcon.src = this.classList.contains('liked') 
                    ? 'https://www.svgrepo.com/show/473235/heart-filled.svg' 
                    : 'https://www.svgrepo.com/show/473234/heart.svg';
            });
        });
    }

    // Función para crear un comentario o respuesta
    function createCommentElement(text, username, isReply) {
        const comment = document.createElement('div');
        comment.className = isReply ? 'comment reply' : 'comment';
        comment.innerHTML = `
            <div class="comment-header">
                <img src="https://via.placeholder.com/30" alt="Foto de perfil" class="comment-profile-pic">
                <span class="comment-username">${username}</span>
            </div>
            <div class="comment-content">
                <p>${text}</p>
            </div>
            <div class="comment-actions">
                <button class="comment-like-btn">Me gusta</button>
                <button class="comment-reply-btn">Responder</button>
                <button class="toggle-replies-btn ${isReply ? 'hidden' : ''}">Ocultar respuestas</button>
            </div>
            <div class="replies hidden"></div>
        `;

        // Event listener para el botón de respuesta
        comment.querySelector('.comment-reply-btn').addEventListener('click', function() {
            const replyText = prompt('Escribe tu respuesta:');
            if (replyText && replyText.trim() !== '') {
                const reply = createCommentElement(replyText, 'TuUsuario', true);
                const repliesDiv = comment.querySelector('.replies');
                repliesDiv.appendChild(reply);
                repliesDiv.classList.remove('hidden');
            }
        });

        // Event listener para el botón de alternar respuestas
        if (!isReply) {
            comment.querySelector('.toggle-replies-btn').addEventListener('click', function() {
                const repliesDiv = comment.querySelector('.replies');
                if (repliesDiv.classList.contains('hidden')) {
                    repliesDiv.classList.remove('hidden');
                    this.textContent = 'Ocultar respuestas';
                } else {
                    repliesDiv.classList.add('hidden');
                    this.textContent = 'Mostrar respuestas';
                }
            });
        }

        return comment;
    }

    // Cargar el perfil del usuario
    fetch('/Proyecto/1.0/wheelz/api/profile.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.status} - ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
        } else {
            document.querySelector('.my-profile-image').src = `/Proyecto/1.0/wheelz/uploads/${data.foto_perfil}`;
            document.querySelector('.my-username').textContent = `@${data.nombre_usuario}`;
        }
    })
    .catch(error => console.error('Error al cargar el perfil:', error));

    // Manejo del formulario de edición de perfil
    document.getElementById('editProfileForm')?.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('/api/update-profile.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Perfil actualizado con éxito');
                location.reload();
            } else {
                alert('Error al actualizar el perfil: ' + data.error);
            }
        })
        .catch(error => console.error('Error al actualizar el perfil:', error));
    });
});


// ========== USUARIO ============
document.addEventListener("DOMContentLoaded", function() {
    fetch('/Proyecto/1.0/wheelz/api/profile.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.status} - ${response.statusText}`);
        }
        return response.json();  // Asegúrate de que la respuesta sea JSON válida
    })
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
        } else {
            document.querySelector('.my-profile-image').src = `/Proyecto/1.0/wheelz/uploads/${data.foto_perfil}`;
            document.querySelector('.my-username').textContent = `@${data.nombre_usuario}`;
        }
    })
    .catch(error => console.error('Error al cargar el perfil:', error));
});




document.getElementById('editProfileForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this); // Crear FormData con los datos del formulario

    fetch('/api/update-profile.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin' // Envía las cookies de sesión actuales
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Perfil actualizado con éxito');
            location.reload(); // Recargar para ver los cambios
        } else {
            alert('Error al actualizar el perfil: ' + data.error);
        }
    })
    .catch(error => console.error('Error al actualizar el perfil:', error));
});


