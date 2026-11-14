
-- Registrar los roles del sistema
INSERT INTO roles (nombre)
VALUES
    ('administrador'),
    ('operador');

-- Registrar los permisos disponibles
INSERT INTO permisos (clave)
VALUES
    ('ver_usuarios'),
    ('crear_usuario'),
    ('editar_usuario'),
    ('eliminar_usuario'),
    ('ver_categorias'),
    ('crear_categoria'),
    ('editar_categoria'),
    ('eliminar_categoria'),
    ('ver_equipos'),
    ('crear_equipo'),
    ('editar_equipo'),
    ('eliminar_equipo'),
    ('ver_prestamos'),
    ('crear_prestamo'),
    ('gestionar_prestamos'),
    ('ver_reportes');

-- Asignar los permisos del administrador
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre = 'administrador'
AND p.clave IN (
    'ver_usuarios',
    'crear_usuario',
    'editar_usuario',
    'eliminar_usuario',
    'ver_categorias',
    'crear_categoria',
    'editar_categoria',
    'eliminar_categoria',
    'ver_equipos',
    'crear_equipo',
    'editar_equipo',
    'eliminar_equipo',
    'ver_reportes'
);

-- Asignar los permisos del operador
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre = 'operador'
AND p.clave IN (
    'ver_equipos',
    'ver_prestamos',
    'crear_prestamo',
    'gestionar_prestamos'
);

-- Registrar el usuario administrador de prueba
INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, intentos_fallidos)
SELECT id, 'Administrador SIGEL', 'admin@sigel.local',
       '$2y$10$PxStt/eecC6/iWP1QMkqXO2eERna8U0GnIdvmaahPTxBfw3IVDZuW', 1, 0 -- Admin123
FROM roles
WHERE nombre = 'administrador';

-- Registrar el usuario operador de prueba
INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, intentos_fallidos)
SELECT id, 'Operador SIGEL', 'operador@sigel.local',
       '$2y$10$CjFTtS5d/uyRDU8suiRddu3PXA7pZg48E6XOeISRmN95wfseodjf2', 1, 0 -- Operador123
FROM roles
WHERE nombre = 'operador';
