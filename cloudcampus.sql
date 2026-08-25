<<<<<<< HEAD
CREATE DATABASE cloudcampus;
use cloudcampus;
CREATE TABLE Roles (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL
);

CREATE TABLE Permisos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Descripcion VARCHAR(100)
);

CREATE TABLE Planes (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    DuracionDias INT NULL,
    Descuento DECIMAL(5,2) DEFAULT 0.00
);

CREATE TABLE Cursos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(100) NULL,
    Precio DECIMAL(10,2) NOT NULL,
    Imagen VARCHAR(255) NOT NULL
);

CREATE TABLE Usuarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Contrasena VARCHAR(255) NOT NULL,
    Correo VARCHAR(100) UNIQUE NOT NULL,
    Estado BOOLEAN DEFAULT 1,
    Verificado BOOLEAN DEFAULT 0,
    NumeroSesiones INT DEFAULT 1, 
    IDRol INT NOT NULL,
    IDPlan INT NOT NULL,
    FOREIGN KEY (IDRol) REFERENCES Roles(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID)
);


CREATE TABLE DatosPersonales (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100),
    Apellidos VARCHAR(100),
    Telefono VARCHAR(20),
    FechaNacimiento DATE,
    Genero VARCHAR(10),  
    Pais VARCHAR(50),
    Ciudad VARCHAR(50),
    Direccion VARCHAR(255),
    Foto VARCHAR(255),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE SesionesActivas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    TokenSesion VARCHAR(255) NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado BOOLEAN DEFAULT 1,  
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE Suscripciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDPlan INT NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,  
    Estado BOOLEAN DEFAULT 1,
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID) 
);

CREATE TABLE Facturas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Total DECIMAL(10,2) NOT NULL,
    Estado BOOLEAN DEFAULT 0, 
    MetodoPago VARCHAR(50),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE Inscripciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCurso INT NOT NULL,
    FechaInscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado BOOLEAN DEFAULT 0, 
    Precio DECIMAL(10,2) NOT NULL,
    Metodo VARCHAR(50),
    IDPlan INT NOT NULL,  
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) ,
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID)
);

CREATE TABLE Contenido (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCurso INT NOT NULL,
    Titulo VARCHAR(100) NOT NULL,
    Tipo ENUM('video','archivo','enlace'),
    Archivo VARCHAR(255) NOT NULL,
    OrdenContenido INT NOT NULL,  
    Bloqueado BOOLEAN DEFAULT 1, 
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID) 
);

CREATE TABLE DetalleRolesPermisos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDRol INT NOT NULL,
    IDPermiso INT NOT NULL,
    FOREIGN KEY (IDRol) REFERENCES Roles(ID),
    FOREIGN KEY (IDPermiso) REFERENCES Permisos(ID)
);

CREATE TABLE DetalleFactura (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDFactura INT NOT NULL,
    PrecioUnidad DECIMAL(10,2) NOT NULL,
    Descuento DECIMAL(10,2) DEFAULT 0.00,
    TipoCompra VARCHAR(20) NOT NULL, 
    IDReferencia INT NOT NULL,  
    FOREIGN KEY (IDFactura) REFERENCES Facturas(ID) 
);


CREATE TABLE verificacion_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    creado_el DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES Usuarios(ID) 
);

CREATE TABLE restablecer_contrasena (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    creado_el DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Usuarios(ID) 
);


ALTER TABLE Usuarios ADD COLUMN intentos_fallidos INT DEFAULT 0 AFTER Verificado;
ALTER TABLE Usuarios ADD COLUMN bloqueado_hasta DATETIME NULL AFTER intentos_fallidos;

ALTER TABLE SesionesActivas 
ADD COLUMN Dispositivo VARCHAR(100) NULL AFTER TokenSesion;

ALTER TABLE cursos
ADD COLUMN Estado ENUM('Aprobado', 'Rechazado', 'Pendiente') DEFAULT 'Pendiente';


CREATE TABLE Cuestionarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCurso INT NOT NULL,
    IDContenido INT NOT NULL,
    IDCreador INT NOT NULL, 
    Titulo VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(255) NULL,
    CantidadPreguntas INT NULL, 
    TiempoLimite INT NULL, 
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    FOREIGN KEY (IDCreador) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDContenido) REFERENCES Contenido(ID) 
);


CREATE TABLE Preguntas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCuestionario INT NOT NULL, 
    Enunciado TEXT NOT NULL,
    Tipo ENUM('opcion_unica', 'opcion_multiple', 'verdadero_falso') DEFAULT 'opcion_unica',
    Puntaje DECIMAL(5,2) NOT NULL DEFAULT 1.00, 
    FOREIGN KEY (IDCuestionario) REFERENCES Cuestionarios(ID) ON DELETE CASCADE
);


CREATE TABLE Opciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDPregunta INT NOT NULL,
    TextoOpcion VARCHAR(255) NOT NULL,
    es_correcta BOOLEAN DEFAULT FALSE, 
    OrdenOpcion INT NULL,
    FOREIGN KEY (IDPregunta) REFERENCES Preguntas(ID) ON DELETE CASCADE
);


CREATE TABLE IntentosCuestionario (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCuestionario INT NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,
    Calificacion DECIMAL(5,2) NULL, 
    Aciertos INT NULL,
    Fallos INT NULL,
    Estado ENUM('en_progreso', 'finalizado', 'cancelado') DEFAULT 'en_progreso',
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDCuestionario) REFERENCES Cuestionarios(ID)
);

CREATE TABLE RespuestasUsuario (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDIntento INT NOT NULL,
    IDPregunta INT NOT NULL,
    IDOpcionSeleccionada INT NULL, 
    FOREIGN KEY (IDIntento) REFERENCES IntentosCuestionario(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDPregunta) REFERENCES Preguntas(ID),
    FOREIGN KEY (IDOpcionSeleccionada) REFERENCES Opciones(ID)
);

CREATE TABLE NotasCurso (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCurso INT NOT NULL,
    NotaFinal DECIMAL(5,2) NULL,   
    FechaCalculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    UNIQUE KEY unique_usuario_curso (IDUsuario, IDCurso) 
);


CREATE VIEW vista_cursos_pendientes_admin AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
WHERE 
    c.Estado = 'Pendiente';


CREATE VIEW vista_cursos_aprobados_estudiante AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
WHERE 
    c.Estado = 'Aprobado';


CREATE VIEW vista_inscripciones_por_curso_admin AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    COUNT(i.ID) AS TotalInscritos,
    COUNT(DISTINCT i.IDUsuario) AS EstudiantesUnicos,
    MAX(i.FechaInscripcion) AS UltimaInscripcion,
    MIN(i.FechaInscripcion) AS PrimeraInscripcion
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
LEFT JOIN 
    Inscripciones i ON c.ID = i.IDCurso AND i.Estado = 1
WHERE 
    c.Estado = 'Aprobado'  
GROUP BY 
    c.ID, c.Nombre, c.Descripcion, c.Precio, c.Estado,
    u.ID, u.Correo, dp.Nombre, dp.Apellidos
ORDER BY 
    TotalInscritos DESC;  


CREATE VIEW vista_cursos_por_profesor AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
ORDER BY 
    u.ID, c.ID;  

INSERT INTO Roles (ID, Nombre) VALUES
(1, 'Usuario'), 
(2, 'Administrador'),
(3,'Profesor');
INSERT INTO Planes (ID, Nombre, Precio, DuracionDias, Descuento) VALUES
(1, 'Basico', 0.00, NULL, 0.00),
(2, 'Premium', 19.99, 30, 80);

INSERT IGNORE INTO Usuarios (Correo, Contrasena, Estado, Verificado, IDRol, IDPlan)
=======
CREATE DATABASE cloudcampus;
use cloudcampus;
CREATE TABLE Roles (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL
);

CREATE TABLE Permisos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Descripcion VARCHAR(100)
);

CREATE TABLE Planes (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    DuracionDias INT NULL,
    Descuento DECIMAL(5,2) DEFAULT 0.00
);

CREATE TABLE Cursos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(100) NULL,
    Precio DECIMAL(10,2) NOT NULL,
    Imagen VARCHAR(255) NOT NULL
);

CREATE TABLE Usuarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Contrasena VARCHAR(255) NOT NULL,
    Correo VARCHAR(100) UNIQUE NOT NULL,
    Estado BOOLEAN DEFAULT 1,
    Verificado BOOLEAN DEFAULT 0,
    NumeroSesiones INT DEFAULT 1, 
    IDRol INT NOT NULL,
    IDPlan INT NOT NULL,
    FOREIGN KEY (IDRol) REFERENCES Roles(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID)
);


CREATE TABLE DatosPersonales (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100),
    Apellidos VARCHAR(100),
    Telefono VARCHAR(20),
    FechaNacimiento DATE,
    Genero VARCHAR(10),  
    Pais VARCHAR(50),
    Ciudad VARCHAR(50),
    Direccion VARCHAR(255),
    Foto VARCHAR(255),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE SesionesActivas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    TokenSesion VARCHAR(255) NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado BOOLEAN DEFAULT 1,  
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE Suscripciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDPlan INT NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,  
    Estado BOOLEAN DEFAULT 1,
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID) 
);

CREATE TABLE Facturas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Total DECIMAL(10,2) NOT NULL,
    Estado BOOLEAN DEFAULT 0, 
    MetodoPago VARCHAR(50),
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) 
);

CREATE TABLE Inscripciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCurso INT NOT NULL,
    FechaInscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado BOOLEAN DEFAULT 0, 
    Precio DECIMAL(10,2) NOT NULL,
    Metodo VARCHAR(50),
    IDPlan INT NOT NULL,  
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID) ,
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    FOREIGN KEY (IDPlan) REFERENCES Planes(ID)
);

CREATE TABLE Contenido (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCurso INT NOT NULL,
    Titulo VARCHAR(100) NOT NULL,
    Tipo ENUM('video','archivo','enlace'),
    Archivo VARCHAR(255) NOT NULL,
    OrdenContenido INT NOT NULL,  
    Bloqueado BOOLEAN DEFAULT 1, 
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID) 
);

CREATE TABLE DetalleRolesPermisos (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDRol INT NOT NULL,
    IDPermiso INT NOT NULL,
    FOREIGN KEY (IDRol) REFERENCES Roles(ID),
    FOREIGN KEY (IDPermiso) REFERENCES Permisos(ID)
);

CREATE TABLE DetalleFactura (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDFactura INT NOT NULL,
    PrecioUnidad DECIMAL(10,2) NOT NULL,
    Descuento DECIMAL(10,2) DEFAULT 0.00,
    TipoCompra VARCHAR(20) NOT NULL, 
    IDReferencia INT NOT NULL,  
    FOREIGN KEY (IDFactura) REFERENCES Facturas(ID) 
);


CREATE TABLE verificacion_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    creado_el DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES Usuarios(ID) 
);

CREATE TABLE restablecer_contrasena (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    creado_el DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Usuarios(ID) 
);


ALTER TABLE Usuarios ADD COLUMN intentos_fallidos INT DEFAULT 0 AFTER Verificado;
ALTER TABLE Usuarios ADD COLUMN bloqueado_hasta DATETIME NULL AFTER intentos_fallidos;

ALTER TABLE SesionesActivas 
ADD COLUMN Dispositivo VARCHAR(100) NULL AFTER TokenSesion;

ALTER TABLE cursos
ADD COLUMN Estado ENUM('Aprobado', 'Rechazado', 'Pendiente') DEFAULT 'Pendiente';


CREATE TABLE Cuestionarios (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCurso INT NOT NULL,
    IDContenido INT NOT NULL,
    IDCreador INT NOT NULL, 
    Titulo VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(255) NULL,
    CantidadPreguntas INT NULL, 
    TiempoLimite INT NULL, 
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    FOREIGN KEY (IDCreador) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDContenido) REFERENCES Contenido(ID) 
);


CREATE TABLE Preguntas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDCuestionario INT NOT NULL, 
    Enunciado TEXT NOT NULL,
    Tipo ENUM('opcion_unica', 'opcion_multiple', 'verdadero_falso') DEFAULT 'opcion_unica',
    Puntaje DECIMAL(5,2) NOT NULL DEFAULT 1.00, 
    FOREIGN KEY (IDCuestionario) REFERENCES Cuestionarios(ID) ON DELETE CASCADE
);


CREATE TABLE Opciones (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDPregunta INT NOT NULL,
    TextoOpcion VARCHAR(255) NOT NULL,
    es_correcta BOOLEAN DEFAULT FALSE, 
    OrdenOpcion INT NULL,
    FOREIGN KEY (IDPregunta) REFERENCES Preguntas(ID) ON DELETE CASCADE
);


CREATE TABLE IntentosCuestionario (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCuestionario INT NOT NULL,
    FechaInicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FechaFin DATETIME NULL,
    Calificacion DECIMAL(5,2) NULL, 
    Aciertos INT NULL,
    Fallos INT NULL,
    Estado ENUM('en_progreso', 'finalizado', 'cancelado') DEFAULT 'en_progreso',
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDCuestionario) REFERENCES Cuestionarios(ID)
);

CREATE TABLE RespuestasUsuario (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDIntento INT NOT NULL,
    IDPregunta INT NOT NULL,
    IDOpcionSeleccionada INT NULL, 
    FOREIGN KEY (IDIntento) REFERENCES IntentosCuestionario(ID) ON DELETE CASCADE,
    FOREIGN KEY (IDPregunta) REFERENCES Preguntas(ID),
    FOREIGN KEY (IDOpcionSeleccionada) REFERENCES Opciones(ID)
);

CREATE TABLE NotasCurso (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUsuario INT NOT NULL,
    IDCurso INT NOT NULL,
    NotaFinal DECIMAL(5,2) NULL,   
    FechaCalculo DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IDUsuario) REFERENCES Usuarios(ID),
    FOREIGN KEY (IDCurso) REFERENCES Cursos(ID),
    UNIQUE KEY unique_usuario_curso (IDUsuario, IDCurso) 
);


CREATE VIEW vista_cursos_pendientes_admin AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
WHERE 
    c.Estado = 'Pendiente';


CREATE VIEW vista_cursos_aprobados_estudiante AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
WHERE 
    c.Estado = 'Aprobado';


CREATE VIEW vista_inscripciones_por_curso_admin AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    COUNT(i.ID) AS TotalInscritos,
    COUNT(DISTINCT i.IDUsuario) AS EstudiantesUnicos,
    MAX(i.FechaInscripcion) AS UltimaInscripcion,
    MIN(i.FechaInscripcion) AS PrimeraInscripcion
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
LEFT JOIN 
    Inscripciones i ON c.ID = i.IDCurso AND i.Estado = 1
WHERE 
    c.Estado = 'Aprobado'  
GROUP BY 
    c.ID, c.Nombre, c.Descripcion, c.Precio, c.Estado,
    u.ID, u.Correo, dp.Nombre, dp.Apellidos
ORDER BY 
    TotalInscritos DESC;  


CREATE VIEW vista_cursos_por_profesor AS
SELECT 
    c.ID AS CursoID,
    c.Nombre AS CursoNombre,
    c.Descripcion AS CursoDescripcion,
    c.Precio AS CursoPrecio,
    c.Imagen AS CursoImagen,
    c.Estado AS CursoEstado,
    u.ID AS ProfesorID,
    u.Correo AS ProfesorCorreo,
    dp.Nombre AS ProfesorNombre,
    dp.Apellidos AS ProfesorApellidos,
    dp.Telefono AS ProfesorTelefono
FROM 
    Cursos c
INNER JOIN 
    Usuarios u ON c.IDUsuario = u.ID
LEFT JOIN 
    DatosPersonales dp ON u.ID = dp.IDUsuario
ORDER BY 
    u.ID, c.ID;  

INSERT INTO Roles (ID, Nombre) VALUES
(1, 'Usuario'), 
(2, 'Administrador'),
(3,'Profesor');
INSERT INTO Planes (ID, Nombre, Precio, DuracionDias, Descuento) VALUES
(1, 'Basico', 0.00, NULL, 0.00),
(2, 'Premium', 19.99, 30, 80);

INSERT IGNORE INTO Usuarios (Correo, Contrasena, Estado, Verificado, IDRol, IDPlan)
>>>>>>> 74b2e15fd16a840c6153302da58b357e003119e1
VALUES ('admin@gmail.com', '$2y$10$MO.YyljvwPgJh6.7XTC4h.MJNMr9EH0yRGlGF/6ZmMhBDnOWT.TIS', 1, 1, 2, 1);