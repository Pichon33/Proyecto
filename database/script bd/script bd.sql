CREATE DATABASE IF NOT EXISTS ivento;
USE ivento;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL, 
    rol VARCHAR(20) NOT NULL DEFAULT 'Usuario'
);

CREATE TABLE Comun (
    id_usuario INT PRIMARY KEY,
    telefono_C VARCHAR(20),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE Organizador (
    id_usuario INT PRIMARY KEY,
    telefono_org VARCHAR(20),
    nombre_org VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE Administrador (
    id_usuario INT PRIMARY KEY,
    permiso VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE Categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(100)
);

CREATE TABLE Ubicacion (
    id_ubicacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre_lugar VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8)
);

CREATE TABLE Evento (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    fecha_fin DATE NULL,
    hora_fin TIME NULL,
    tipo_publicacion VARCHAR(30) NOT NULL DEFAULT 'Evento',
    precio_uyu DECIMAL(10,2) NOT NULL DEFAULT 0,
    edad_minima INT NOT NULL DEFAULT 0,
    estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
    imagen VARCHAR(255),
    latitud DECIMAL(10,7) NULL,
    longitud DECIMAL(10,7) NULL,
    id_usuario INT NOT NULL,
    id_categoria INT NOT NULL,
    id_ubicacion INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria),
    FOREIGN KEY (id_ubicacion) REFERENCES Ubicacion(id_ubicacion)
);

CREATE TABLE Recorrido (
    id_recorrido INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT UNIQUE,
    calle_inicio VARCHAR(150),
    calle_fin VARCHAR(150),
    descripcion TEXT,
    puntos TEXT NULL,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE CorteCalle (
    id_corte INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NULL,
    id_usuario INT NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    recorrido TEXT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE PerfilUsuario (
    id_usuario int primary key,
    ubicacion varchar(150) null,
    avatar varchar(255) null,
    tema enum('claro', 'oscuro') not null default 'claro',
    foreign key (id_usuario) references Usuario(id_usuario)
        on delete cascade
        on update cascade
);

CREATE TABLE Favorito (
    id_usuario INT NOT NULL,
    id_evento INT NOT NULL,
    PRIMARY KEY (id_usuario, id_evento),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
