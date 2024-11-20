use Wheelz

-- Tabla de Roles
CREATE TABLE roles (
    rol VARCHAR(255) PRIMARY KEY,
    descripcion_permisos VARCHAR(255) NOT NULL
);

-- Tabla de Usuarios
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contrasenia VARCHAR(255) NOT NULL,
    bio VARCHAR(255),
    avatar VARCHAR(255) -- Campo para URL o ruta de la foto de perfil del usuario
);

-- Tabla de Publicaciones
CREATE TABLE publicacion (
    id_publi INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,  -- FK a la tabla usuario
    contenido_texto TEXT NOT NULL,  -- Campo para el contenido textual
    multimedia_url VARCHAR(255),  -- Campo para la URL o ruta de archivo multimedia (imágenes, videos)
    fecha__hora_creacion DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- Tabla de Hashtags
CREATE TABLE hashtag (
    etiqueta VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY
);

-- Tabla de Comentarios
CREATE TABLE comentario (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_publi INT NOT NULL,
    id_usuario INT NOT NULL,
    contenido VARCHAR(255) NOT NULL,
    fecha_hora_creacion DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_publi) REFERENCES publicacion(id_publi) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Mensajes
CREATE TABLE mensaje (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,  -- FK a la tabla usuario
    contenido TEXT NOT NULL,
    imagen VARCHAR(255),
    es_grupo BOOLEAN NOT NULL,  -- Columna para distinguir si es mensaje de grupo o directo
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- Tabla de Mensaje Directo
CREATE TABLE mensaje_directo (
    id_mensaje INT PRIMARY KEY,  -- FK y PK a la tabla mensaje
    id_usuario INT NOT NULL,  -- FK a la tabla usuario
    FOREIGN KEY (id_mensaje) REFERENCES mensaje(id_mensaje),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

-- Tabla de Grupos
CREATE TABLE grupo (
    id_grupo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nombre_grupo VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_hora_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Miembros del Grupo
CREATE TABLE miembro_grupo (
    id_grupo INT NOT NULL,
    id_usuario INT NOT NULL,
    rol VARCHAR(255),
    PRIMARY KEY (id_grupo, id_usuario),
    FOREIGN KEY (id_grupo) REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (rol) REFERENCES roles(rol) ON DELETE CASCADE
);

-- Tabla de Mensaje de Grupo
CREATE TABLE mensaje_grupo (
    id_mensaje INT PRIMARY KEY,  -- FK y PK a la tabla mensaje
    id_grupo INT NOT NULL,  -- FK a la tabla grupo
    FOREIGN KEY (id_mensaje) REFERENCES mensaje(id_mensaje),
    FOREIGN KEY (id_grupo) REFERENCES grupo(id_grupo)
);

-- Tabla de Tipos de Notificaciones
CREATE TABLE tipo_notificacion (
    id_tipo_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo ENUM('like', 'comentario', 'nuevo_seguidor', 'mensaje') NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Notificaciones
CREATE TABLE notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_tipo_notificacion INT NOT NULL,
    fecha_notificacion DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_tipo_notificacion) REFERENCES tipo_notificacion(id_tipo_notificacion) ON DELETE CASCADE
);

-- Tabla de Eventos
CREATE TABLE evento (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nombre_evento VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_hora_evento DATETIME NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Publicidad
CREATE TABLE publicidad (
    id_publicidad INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    contenido VARCHAR(255) NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Usuarios Eliminados
CREATE TABLE usuario_eliminado (
    id_usuario INT PRIMARY KEY,
    fecha__hora_eliminacion DATETIME NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Contiene Hashtag
CREATE TABLE contiene_hashtag (
    id_publicacion INT NOT NULL,
    etiqueta VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_publicacion, etiqueta),
    FOREIGN KEY (id_publicacion) REFERENCES publicacion(id_publi) ON DELETE CASCADE,
    FOREIGN KEY (etiqueta) REFERENCES hashtag(etiqueta) ON DELETE CASCADE
);

-- Tabla de Seguimiento
CREATE TABLE seguimiento (
    id_seguidor INT NOT NULL,
    id_seguido INT NOT NULL,
    PRIMARY KEY (id_seguidor, id_seguido),
    FOREIGN KEY (id_seguidor) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_seguido) REFERENCES usuario(id_usuario),
    CHECK (id_seguidor != id_seguido)  -- Restricción que impide seguirse a sí mismo
);

-- Tabla de Razones de Reporte
CREATE TABLE razon_reporte (
    id_razon INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(255) NOT NULL
);

-- Tabla de Reportes
CREATE TABLE reporte (
    id_usuario INT NOT NULL,
    id_publicacion INT NOT NULL,
    id_razon INT NOT NULL,
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id_usuario, id_publicacion),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_publicacion) REFERENCES publicacion(id_publi) ON DELETE CASCADE,
    FOREIGN KEY (id_razon) REFERENCES razon_reporte(id_razon) ON DELETE CASCADE
);

-- Tabla de TOKENS OAuth 2.0
CREATE TABLE oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    access_token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- Tabla de Foros
CREATE TABLE foro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,  -- FK a la tabla usuario
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    categoria VARCHAR(255),
    hashtag VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);


-- Inserción de roles iniciales
INSERT INTO roles (rol, descripcion_permisos) VALUES 
('miembro', 'Miembro regular de un grupo'), 
('admin', 'Administrador del grupo');

-- Tabla para Likes en Publicaciones
CREATE TABLE likes_publicacion (
    id_like INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_publi INT NOT NULL,
    fecha_like DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_publi) REFERENCES publicacion(id_publi) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_publi)  -- Evita que un usuario le dé like más de una vez a la misma publicación
);

-- Tabla para Likes en Foros
CREATE TABLE likes_foro (
    id_like INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_foro INT NOT NULL,
    fecha_like DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_foro) REFERENCES foro(id) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_foro)  -- Evita que un usuario le dé like más de una vez al mismo foro
);

-- Tabla para Comentarios en Foros
CREATE TABLE comentario_foro (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_foro INT NOT NULL,
    id_usuario INT NOT NULL,
    contenido VARCHAR(255) NOT NULL,
    fecha_hora_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_foro) REFERENCES foro(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------

-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ----------------------------- IMPLEMENTACION DE VISTAS -----------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------

-- Vista para comentarios de una publicacion.
-- Muestra todas las publicaciones con el nombre del usuario que la creó, el contenido de la publicación y el número de comentarios que tiene cada una.
CREATE VIEW vista_publicaciones_comentarios AS
SELECT 
    p.id_publi,
    p.contenido_texto AS contenido_publicacion,
    u.nombre_usuario AS autor,
    p.fecha__hora_creacion,
    COUNT(c.id_comentario) AS num_comentarios
FROM 
    publicacion p
JOIN 
    usuario u ON p.id_usuario = u.id_usuario
LEFT JOIN 
    comentario c ON p.id_publi = c.id_publi
GROUP BY 
    p.id_publi;
    

-- Vista para seguidores.
-- Esta vista muestra los nombres de los usuarios que siguen a un usuario específico.
CREATE VIEW vista_seguidores AS
SELECT 
    u.id_usuario AS usuario_id,
    u.nombre_usuario AS nombre_usuario,
    s.id_seguidor AS seguidor_id,
    us.nombre_usuario AS nombre_seguidor
FROM 
    usuario u
JOIN 
    seguimiento s ON u.id_usuario = s.id_seguido
JOIN 
    usuario us ON s.id_seguidor = us.id_usuario;


-- Vista de usuarios y cantidad de publicaciones
-- Esta vista cuenta la cantidad de publicaciones que cada usuario ha realizado.
CREATE VIEW vista_usuario_publicaciones AS
SELECT 
    u.id_usuario,
    u.nombre_usuario,
    COUNT(p.id_publi) AS num_publicaciones
FROM 
    usuario u
LEFT JOIN 
    publicacion p ON u.id_usuario = p.id_usuario
GROUP BY 
    u.id_usuario;


-- Vista de mensajes directos entre dos usuarios.
-- Muestra todos los mensajes directos entre dos usuarios específicos.
CREATE VIEW vista_conversacion_directa AS
SELECT 
    m.id_mensaje,
    u1.nombre_usuario AS emisor,
    u2.nombre_usuario AS receptor,
    m.contenido,
    m.fecha_hora
FROM 
    mensaje m
JOIN 
    mensaje_directo md ON m.id_mensaje = md.id_mensaje
JOIN 
    usuario u1 ON m.id_usuario = u1.id_usuario
JOIN 
    usuario u2 ON md.id_usuario = u2.id_usuario;
    

-- Vista de grupos y cantidad de miembros.
-- Muestra una lista de todos los grupos con el número de miembros en cada uno.
CREATE VIEW vista_grupo_miembros AS
SELECT 
    g.id_grupo,
    g.nombre_grupo,
    g.descripcion,
    COUNT(mg.id_usuario) AS num_miembros
FROM 
    grupo g
LEFT JOIN 
    miembro_grupo mg ON g.id_grupo = mg.id_grupo
GROUP BY 
    g.id_grupo;
    

-- Vista de reportes de publicaciones
-- Muestra una lista de todas las publicaciones reportadas, junto con la razón del reporte y el nombre del usuario que hizo el reporte.
CREATE VIEW vista_reportes_publicaciones AS
SELECT 
    p.id_publi,
    p.contenido_texto AS publicacion,
    u.nombre_usuario AS reportado_por,
    r.id_razon,
    r.fecha_reporte
FROM 
    reporte r
JOIN 
    publicacion p ON r.id_publicacion = p.id_publi
JOIN 
    usuario u ON r.id_usuario = u.id_usuario;

-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------



-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ----------------------------- APARTADO PARA DATOS DE PRUEBA ------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------

INSERT INTO usuario (nombre_usuario, email, contraseña, bio, avatar, verificado) VALUES
('usuario1', 'usuario1@correo.com', 'password1', 'Bio del usuario 1', 'avatar1.jpg', 1),
('usuario2', 'usuario2@correo.com', 'password2', 'Bio del usuario 2', 'avatar2.jpg', 0),
('usuario3', 'usuario3@correo.com', 'password3', 'Bio del usuario 3', 'avatar3.jpg', 1);

INSERT INTO publicacion (id_usuario, contenido, fecha_creacion) VALUES
(1, 'Primera publicación del usuario 1', NOW()),
(2, 'Primera publicación del usuario 2', NOW()),
(3, 'Primera publicación del usuario 3', NOW());

INSERT INTO hashtag (etiqueta) VALUES
('#bienvenido'),
('#nuevoPost'),
('#primeraPublicacion');

INSERT INTO comentario (id_publi, id_usuario, contenido, fecha_hora_creacion) VALUES
(1, 2, 'Gran post!', NOW()),
(2, 3, '¡Excelente publicación!', NOW()),
(3, 1, 'Sigue adelante', NOW());

INSERT INTO mensaje (id_usuario, contenido, tipo, fecha_hora) VALUES
(1, 'Hola, cómo estás?', 'texto', NOW()),
(2, 'Estoy bien, gracias', 'texto', NOW()),
(3, 'Aquí trabajando en el proyecto', 'texto', NOW());

INSERT INTO mensaje_directo (id_mensaje, id_usuario) VALUES
(1, 2),
(2, 1),
(3, 2);

INSERT INTO mensaje_grupo (id_mensaje, id_grupo) VALUES
(1, 1),
(2, 2),
(3, 1);

INSERT INTO grupo (id_grupo, id_usuario, nombre_grupo, descripcion) VALUES
(1, 1, 'Grupo de trabajo', 'Grupo para coordinar el proyecto'),
(2, 2, 'Grupo de amigos', 'Grupo de amigos para charlas'),
(3, 3, 'Grupo de soporte', 'Grupo de soporte para dudas técnicas');

INSERT INTO roles (rol, id_usuario, descripcion_permisos) VALUES
('admin', 1, 'Todos los permisos en el sistema'),
('usuario', 2, 'Permisos básicos de usuario'),
('invitado', 3, 'Acceso de solo lectura');

INSERT INTO notificacion (id_usuario, tipo_notificacion, fecha_notificacion) VALUES
(1, 1, NOW()),
(2, 2, NOW()),
(3, 1, NOW());

INSERT INTO tipo_notificacion (tipo, id_usuario) VALUES
('like', 1),
('comentario', 2),
('nuevo_seguidor', 3);

INSERT INTO razon (tipo, reporte) VALUES
('Contenido ofensivo', 1),
('Spam', 2),
('Violación de términos', 3);

INSERT INTO evento (id_usuario, nombre_evento, descripcion, fecha_evento, ubicacion) VALUES
(1, 'Lanzamiento de producto', 'Evento de lanzamiento', '2024-09-25', '40.7128, -74.0060'),
(2, 'Charla técnica', 'Sesión técnica sobre desarrollo web', '2024-10-01', '34.0522, -118.2437'),
(3, 'Hackathon', 'Hackathon de fin de semana', '2024-11-15', '37.7749, -122.4194');

INSERT INTO publicidad (id_usuario, contenido, fecha_inicio, fecha_fin) VALUES
(1, 'Publicidad sobre producto A', '2024-09-01', '2024-09-30'),
(2, 'Publicidad sobre servicio B', '2024-10-01', '2024-10-31'),
(3, 'Publicidad sobre evento C', '2024-11-01', '2024-11-30');

INSERT INTO usuario_eliminado (id_usuario, fecha_eliminacion) VALUES
(1, '2024-01-01'),
(2, '2024-02-01'),
(3, '2024-03-01');

INSERT INTO contiene_hashtag (id_publicacion, id_hashtag) VALUES
(1, 1),
(2, 2),
(3, 3);

INSERT INTO seguimiento (id_seguidor, id_seguido) VALUES
(1, 2),
(2, 3),
(3, 1);

INSERT INTO miembro_grupo (id_grupo, id_usuario) VALUES
(1, 1),
(2, 2),
(3, 3);

INSERT INTO reporte (id_usuario, id_publicacion, razon, fecha_reporte) VALUES
(1, 1, 'Contenido ofensivo', NOW()),
(2, 2, 'Spam', NOW()),
(3, 3, 'Violación de términos', NOW());

-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------
-- ------------------------------------------------------------------------------------