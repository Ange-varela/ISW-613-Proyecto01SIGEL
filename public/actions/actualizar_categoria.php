
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_categoria');

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Validar el token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no valida');
}

// Validar el ID
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

// Obtener los datos
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

// Validar los datos
if (!is_string($nombre) || !is_string($descripcion)) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

$nombre = trim($nombre);
$descripcion = trim($descripcion);

if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    mb_strlen($descripcion) > 255
) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

try {
    // Actualizar la categoria activa
    $sql = "UPDATE categorias
            SET nombre = :nombre,
                descripcion = :descripcion
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'id' => $id
    ]);

    // Regresar al listado
    header('Location: ../pages/categorias.php?actualizado=1');
    exit;

} catch (PDOException $e) {
    // Registrar el error
    error_log($e->getMessage());

    header('Location: ../pages/categorias.php?error=1');
    exit;
}
