
<?php
// Iniciar la sesion
session_start();

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
