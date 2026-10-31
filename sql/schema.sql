CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos INT NOT NULL DEFAULT 0,

    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE rol_permiso (
    rol_id INT NOT NULL,
    permiso_id INT NOT NULL,

    PRIMARY KEY (rol_id, permiso_id),

    FOREIGN KEY (rol_id) REFERENCES roles(id),
    FOREIGN KEY (permiso_id) REFERENCES permisos(id)
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado VARCHAR(30) NOT NULL DEFAULT 'disponible',
    activo TINYINT(1) NOT NULL DEFAULT 1,

    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE profesores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    selector VARCHAR(50) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expira_en DATETIME NOT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(100) NOT NULL,
    entidad_id INT NOT NULL,
    valores_antes JSON,
    valores_despues JSON,
    ip VARCHAR(45),
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profesor_id INT NOT NULL,
    operador_id INT NOT NULL,
    estado ENUM(
        'solicitado',
        'aprobado',
        'entregado',
        'devuelto',
        'cancelado'
    ) NOT NULL DEFAULT 'solicitado',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,

    FOREIGN KEY (profesor_id) REFERENCES profesores(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id),

    CONSTRAINT chk_fechas_prestamo
        CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE prestamo_detalle (
    prestamo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_devolucion DATETIME NULL,

    PRIMARY KEY (prestamo_id, equipo_id),

    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;