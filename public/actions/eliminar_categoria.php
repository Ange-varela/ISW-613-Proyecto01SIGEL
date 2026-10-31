
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('eliminar_categoria');

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

if (!$id || $id < 1) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

try {
    // Realizar la eliminacion logica
    $sql = "UPDATE categorias
            SET activo = 0
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $id]);

    // Regresar al listado
    header('Location: ../pages/categorias.php?eliminado=1');
    exit;

} catch (PDOException $e) {
    // Registrar el error
    error_log($e->getMessage());

    header('Location: ../pages/categorias.php?error=1');
    exit;
}
