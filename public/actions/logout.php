<?php
// Iniciar la sesion
session_start();

// Cargar la conexion
require_once __DIR__ . '/../../config/conexion.php';

// Permitir solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Obtener la cookie de recordarme
$cookie = $_COOKIE['sigel_recordarme'] ?? '';

if (is_string($cookie) && $cookie !== '') {
    $partes = explode(':', $cookie, 2);
    $selector = $partes[0] ?? '';

    if (preg_match('/^[a-f0-9]{24}$/D', $selector)) {
        try {
            // Eliminar el token de MySQL
            $consulta = $pdo->prepare(
                "DELETE FROM auth_tokens WHERE selector = ?"
            );

            $consulta->execute([$selector]);

        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            exit('No se pudo cerrar la sesion de forma segura');
        }
    }
}

// Eliminar la cookie de recordarme
setcookie('sigel_recordarme', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Eliminar las variables de sesion
$_SESSION = [];

// Eliminar la cookie de sesion
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

// Destruir la sesion
session_destroy();

// Regresar al login
header('Location: /login.php');
exit;
