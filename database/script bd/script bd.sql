CREATE DATABASE IF NOT EXISTS HayPlan;
USE HayPlan;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL, 
    rol VARCHAR(20) NOT NULL
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
    estado VARCHAR(50),
    imagen VARCHAR(255),
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
    FOREIGN KEY (id_evento) REFERENCES Evento(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

