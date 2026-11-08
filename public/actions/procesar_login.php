<?php
// Iniciar la sesion y cargar la conexion
session_start();
require_once __DIR__ . '/../../config/conexion.php';

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Validar el token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no valida');
}

// Obtener los datos del formulario
$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

// Validar los datos
if (
    !is_string($correo) ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    !is_string($contrasena) ||
    $contrasena === ''
) {
    header('Location: ../login.php?error=1');
    exit;
}

try {
    // Buscar el usuario por correo
    $sql = "SELECT id, rol_id, nombre, password_hash, activo, intentos_fallidos
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['correo' => $correo]);
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    // Comprobar las credenciales
    if (
        !$usuario ||
        !$usuario['activo'] ||
        $usuario['intentos_fallidos'] >= 5 ||
        !password_verify($contrasena, $usuario['password_hash'])
    ) {
        // Registrar un intento fallido
        if ($usuario && $usuario['activo']) {
            $actualizar = $pdo->prepare(
                "UPDATE usuarios
                 SET intentos_fallidos = intentos_fallidos + 1
                 WHERE id = :id AND intentos_fallidos < 5"
            );
            $actualizar->execute(['id' => $usuario['id']]);
        }

        header('Location: ../login.php?error=1');
        exit;
    }

    // Reiniciar los intentos fallidos
    $actualizar = $pdo->prepare(
        "UPDATE usuarios SET intentos_fallidos = 0 WHERE id = :id"
    );
    $actualizar->execute(['id' => $usuario['id']]);

    // Proteger la sesion al iniciar
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['rol_id'] = $usuario['rol_id'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['ultima_actividad'] = time();

    // Redirigir al panel principal
    header('Location: ../pages/dashboard.php');
    exit;

} catch (PDOException $e) {
    // Registrar el error sin mostrar detalles internos
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrio un error al iniciar sesion');
}
