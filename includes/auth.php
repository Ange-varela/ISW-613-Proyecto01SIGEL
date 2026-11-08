<?php
// Iniciar la sesion si no esta iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario inicio sesion
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }

    // Cerrar la sesion despues de 30 minutos de inactividad
    if (
        isset($_SESSION['ultima_actividad']) &&
        time() - $_SESSION['ultima_actividad'] > 1800
    ) {
        $_SESSION = [];
        session_destroy();

        header('Location: /login.php');
        exit;
    }

    // Actualizar la ultima actividad
    $_SESSION['ultima_actividad'] = time();
}


// Comprobar si el usuario tiene un permiso
function tienePermiso($permiso) {
    require __DIR__ . '/../config/conexion.php';

    if (!isset($_SESSION['rol_id'])) {
        return false;
    }

    $sql = "SELECT COUNT(*)
            FROM rol_permiso rp
            INNER JOIN permisos p ON rp.permiso_id = p.id
            WHERE rp.rol_id = :rol_id
            AND p.clave = :permiso";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        'rol_id' => $_SESSION['rol_id'],
        'permiso' => $permiso
    ]);

    return $consulta->fetchColumn() > 0;
}


// Bloquear el acceso si no tiene el permiso
function verificarPermiso($permiso) {
    verificarSesion();

    if (!tienePermiso($permiso)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}
