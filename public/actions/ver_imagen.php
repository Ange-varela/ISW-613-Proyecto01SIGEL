<?php
// Iniciar el buffer de salida
ob_start();

// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_equipos');

// Validar el ID de la imagen
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Solicitud no valida');
}

// Consultar la imagen del equipo
$sql = "SELECT i.nombre_archivo, i.tipo_mime
        FROM equipo_imagenes i
        INNER JOIN equipos e ON i.equipo_id = e.id
        WHERE i.id = :id AND e.activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);

$imagen = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$imagen) {
    http_response_code(404);
    exit('Imagen no encontrada');
}

// Validar el nombre y formato
$formatos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

$tipo = $imagen['tipo_mime'];
$nombre = $imagen['nombre_archivo'];

if (
    !isset($formatos[$tipo]) ||
    !preg_match('/^[a-f0-9]{32}\.' . $formatos[$tipo] . '$/', $nombre)
) {
    http_response_code(404);
    exit('Imagen no encontrada');
}

// Buscar el archivo privado
$ruta = __DIR__ . '/../../storage/equipos/' . $nombre;

if (!is_file($ruta)) {
    http_response_code(404);
    exit('Imagen no encontrada');
}


// Limpiar la salida anterior
ob_end_clean();

// Mostrar la imagen
header('Content-Type: ' . $tipo);
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($ruta));

// Enviar el archivo
readfile($ruta);
exit;
