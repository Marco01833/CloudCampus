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

INSERT INTO Roles (ID, Nombre) VALUES
(1, 'Usuario'), 
(2, 'Administrador'),
(3,'Profesor');
INSERT INTO Planes (ID, Nombre, Precio, DuracionDias, Descuento) VALUES
(1, 'Basico', 0.00, NULL, 0.00),
(2, 'Premium', 19.99, 30, 80);

INSERT IGNORE INTO Usuarios (Correo, Contrasena, Estado, Verificado, IDRol, IDPlan)
VALUES ('admin@gmail.com', '$2y$10$MO.YyljvwPgJh6.7XTC4h.MJNMr9EH0yRGlGF/6ZmMhBDnOWT.TIS', 1, 1, 2, 1);