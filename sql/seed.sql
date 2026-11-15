
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

-- =====================================================
-- Datos de ejemplo para probar el sistema
-- =====================================================

-- Registrar categorias de prueba
INSERT INTO categorias (nombre, descripcion)
VALUES
    ('Computadoras', 'Laptops y equipos de escritorio'),
    ('Proyectores', 'Proyectores para clases'),
    ('Redes', 'Routers, switches y access points'),
    ('Cables y adaptadores', 'Cables HDMI, VGA, USB y adaptadores'),
    ('Audio', 'Parlantes, microfonos y audifonos'),
    ('Tabletas', 'Tabletas para practicas y pruebas'),
    ('Camaras', 'Camaras web y camaras de video'),
    ('Almacenamiento', 'Discos externos y memorias USB'),
    ('Electronica', 'Arduinos, Raspberry Pi y sensores'),
    ('Perifericos', 'Teclados, mouse y monitores');

-- Registrar equipos de prueba buscando la categoria por nombre
INSERT INTO equipos (categoria_id, codigo, nombre, descripcion)
SELECT c.id, d.codigo, d.nombre, d.descripcion
FROM (
    SELECT 'Computadoras' AS categoria, 'LAP-001' AS codigo, 'Laptop Dell Latitude' AS nombre, 'Laptop para uso en laboratorio' AS descripcion
    UNION ALL SELECT 'Computadoras', 'LAP-002', 'Laptop HP ProBook', 'Laptop para uso en laboratorio'
    UNION ALL SELECT 'Proyectores', 'PRO-001', 'Proyector Epson', 'Proyector con entrada HDMI'
    UNION ALL SELECT 'Proyectores', 'PRO-002', 'Proyector BenQ', 'Proyector portatil'
    UNION ALL SELECT 'Redes', 'RED-001', 'Router Cisco 1941', 'Router para practicas de redes'
    UNION ALL SELECT 'Redes', 'RED-002', 'Switch Cisco 2960', 'Switch de 24 puertos'
    UNION ALL SELECT 'Cables y adaptadores', 'CAB-001', 'Cable HDMI 3 metros', 'Cable HDMI'
    UNION ALL SELECT 'Audio', 'AUD-001', 'Parlante JBL', 'Parlante bluetooth'
    UNION ALL SELECT 'Tabletas', 'TAB-001', 'Tableta Samsung Galaxy', 'Tableta de 10 pulgadas'
    UNION ALL SELECT 'Camaras', 'CAM-001', 'Camara web Logitech', 'Camara web HD'
    UNION ALL SELECT 'Almacenamiento', 'ALM-001', 'Disco externo 1 TB', 'Disco duro USB'
    UNION ALL SELECT 'Electronica', 'ELE-001', 'Kit Arduino Uno', 'Kit con sensores basicos'
    UNION ALL SELECT 'Perifericos', 'PER-001', 'Monitor Samsung 24 pulgadas', 'Monitor Full HD'
) AS d
INNER JOIN categorias c ON c.nombre = d.categoria;

-- Registrar profesores de prueba
INSERT INTO profesores (nombre, correo)
VALUES
    ('Bladimir Arroyo', 'bladimir.arroyo@utn.ac.cr'),
    ('Marcos Castro', 'marcos.castro@utn.ac.cr'),
    ('Rafael Zamora', 'rafael.zamora@utn.ac.cr'),
    ('Kattia Solis', 'kattia.solis@utn.ac.cr'),
    ('Daniel Soto', 'daniel.soto@utn.ac.cr');

-- Registrar prestamos de ejemplo (ya devueltos) para que el reporte tenga datos
INSERT INTO prestamos (profesor_id, operador_id, estado, fecha_inicio, fecha_fin)
SELECT p.id, u.id, 'devuelto', '2026-10-05 08:00:00', '2026-10-05 12:00:00'
FROM profesores p, usuarios u
WHERE p.correo = 'bladimir.arroyo@utn.ac.cr' AND u.correo = 'operador@sigel.local';

INSERT INTO prestamo_detalle (prestamo_id, equipo_id, fecha_devolucion)
SELECT LAST_INSERT_ID(), id, '2026-10-05 11:50:00'
FROM equipos
WHERE codigo IN ('PRO-001', 'LAP-001');

INSERT INTO prestamos (profesor_id, operador_id, estado, fecha_inicio, fecha_fin)
SELECT p.id, u.id, 'devuelto', '2026-10-12 13:00:00', '2026-10-12 17:00:00'
FROM profesores p, usuarios u
WHERE p.correo = 'marcos.castro@utn.ac.cr' AND u.correo = 'operador@sigel.local';

INSERT INTO prestamo_detalle (prestamo_id, equipo_id, fecha_devolucion)
SELECT LAST_INSERT_ID(), id, '2026-10-12 16:45:00'
FROM equipos
WHERE codigo IN ('PRO-001', 'RED-001', 'RED-002');

INSERT INTO prestamos (profesor_id, operador_id, estado, fecha_inicio, fecha_fin)
SELECT p.id, u.id, 'devuelto', '2026-10-20 08:00:00', '2026-10-20 10:00:00'
FROM profesores p, usuarios u
WHERE p.correo = 'rafael.zamora@utn.ac.cr' AND u.correo = 'operador@sigel.local';

INSERT INTO prestamo_detalle (prestamo_id, equipo_id, fecha_devolucion)
SELECT LAST_INSERT_ID(), id, '2026-10-20 09:55:00'
FROM equipos
WHERE codigo IN ('PRO-001', 'LAP-002');

-- Registrar un prestamo cancelado
INSERT INTO prestamos (profesor_id, operador_id, estado, fecha_inicio, fecha_fin)
SELECT p.id, u.id, 'cancelado', '2026-10-27 08:00:00', '2026-10-27 12:00:00'
FROM profesores p, usuarios u
WHERE p.correo = 'kattia.solis@utn.ac.cr' AND u.correo = 'operador@sigel.local';

INSERT INTO prestamo_detalle (prestamo_id, equipo_id)
SELECT LAST_INSERT_ID(), id
FROM equipos
WHERE codigo IN ('TAB-001');

-- Registrar una solicitud pendiente para probar aprobar y entregar
INSERT INTO prestamos (profesor_id, operador_id, estado, fecha_inicio, fecha_fin)
SELECT p.id, u.id, 'solicitado', '2026-12-01 08:00:00', '2026-12-01 12:00:00'
FROM profesores p, usuarios u
WHERE p.correo = 'daniel.soto@utn.ac.cr' AND u.correo = 'operador@sigel.local';

INSERT INTO prestamo_detalle (prestamo_id, equipo_id)
SELECT LAST_INSERT_ID(), id
FROM equipos
WHERE codigo IN ('LAP-001', 'CAM-001');

