<?php
// Iniciar la sesion si no esta iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

 // Recuperar la sesion con la cookie de recordarme
function recuperarSesion() {
    $cookie = $_COOKIE['sigel_recordarme'] ?? '';

    if (!is_string($cookie) ||
        !preg_match('/^[a-f0-9]{24}:[a-f0-9]{64}$/D', $cookie)) {
        return false;
    }

    // Separar los valores de la cookie
    [$selector, $validator] = explode(':', $cookie, 2);

    // Cargar la conexion
    require __DIR__ . '/../config/conexion.php';

    try {
        // Buscar el token y comprobar que el usuario este activo
        $sql = "SELECT t.id, t.validator_hash, t.expira_en,
                       u.id AS usuario_id, u.rol_id, u.nombre
                FROM auth_tokens t
                INNER JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.selector = ?
                AND u.activo = 1
                LIMIT 1";

        $consulta = $pdo->prepare($sql);
        $consulta->execute([$selector]);
        $registro = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$registro ||
            strtotime($registro['expira_en']) <= time() ||
            !hash_equals(
                $registro['validator_hash'],
                hash('sha256', $validator)
            )) {
            return false;
        }

        // Generar un token nuevo
        $nuevoValidator = bin2hex(random_bytes(32));
        $nuevoHash = hash('sha256', $nuevoValidator);

        // Cambiar el token anterior por el nuevo
        $consulta = $pdo->prepare(
            "UPDATE auth_tokens
             SET validator_hash = ?
             WHERE id = ? AND validator_hash = ?"
        );

        $consulta->execute([
            $nuevoHash,
            $registro['id'],
            $registro['validator_hash']
        ]);

        if ($consulta->rowCount() !== 1) {
            return false;
        }

        // Actualizar la cookie
        setcookie(
            'sigel_recordarme',
            $selector . ':' . $nuevoValidator,
            [
                'expires' => strtotime($registro['expira_en']),
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) &&
                            $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // Crear una nueva sesion
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $registro['usuario_id'];
        $_SESSION['rol_id'] = $registro['rol_id'];
        $_SESSION['nombre'] = $registro['nombre'];
        $_SESSION['ultima_actividad'] = time();

        return true;

    } catch (Throwable $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Verificar si el usuario inicio sesion
function verificarSesion() {
    // Intentar recuperar la sesion con recordarme
    if (!isset($_SESSION['usuario_id'])) {
        if (!recuperarSesion()) {
            header('Location: /login.php');
            exit;
        }
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
