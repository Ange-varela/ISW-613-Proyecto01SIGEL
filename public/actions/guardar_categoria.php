
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('crear_categoria');

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Validar el token de seguridad
$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no valida');
}

// Obtener los datos del formulario
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

// Validar los datos
if (!is_string($nombre) || !is_string($descripcion)) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

$nombre = trim($nombre);
$descripcion = trim($descripcion);

if ($nombre === '' || mb_strlen($nombre) > 100 ||
    mb_strlen($descripcion) > 255) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

try {
    // Guardar la categoria
    $sql = "INSERT INTO categorias (nombre, descripcion)
            VALUES (:nombre, :descripcion)";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion
    ]);

    // Regresar al listado
    header('Location: ../pages/categorias.php?guardado=1');
    exit;

} catch (PDOException $e) {
    // Registrar el error
    error_log($e->getMessage());

    header('Location: ../pages/categorias.php?error=1');
    exit;
}
