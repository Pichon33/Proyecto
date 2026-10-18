-- Agrega seguridad sin modificar cuentas existentes. Ejecutar con tools/actualizar-base.php.
CREATE TABLE IF NOT EXISTS SeguridadUsuario (
    id_usuario INT PRIMARY KEY,
    correo_verificado VARCHAR(255) NULL,
    dos_pasos TINYINT(1) NOT NULL DEFAULT 0,
    version INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS DesafioAcceso (
    id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    id_usuario INT NOT NULL,
    proposito VARCHAR(20) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    codigo_hash VARCHAR(255) NOT NULL,
    sesion CHAR(64) NOT NULL,
    credencial CHAR(64) NOT NULL,
    version INT NOT NULL,
    vence INT UNSIGNED NOT NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    INDEX (vence), INDEX (id_usuario),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS RecuperacionAcceso (
    id_usuario INT NOT NULL,
    codigo_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    PRIMARY KEY (id_usuario, codigo_hash),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS IntentoAcceso (
    clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    inicio INT UNSIGNED NOT NULL,
    intentos INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX (inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
