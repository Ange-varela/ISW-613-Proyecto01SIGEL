
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_usuario');

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

// Obtener los datos
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$rolId = filter_var($_POST['rol_id'] ?? null, FILTER_VALIDATE_INT);
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';

// Validar los datos
if (
    !$id || $id < 1 ||
    !$rolId || $rolId < 1 ||
    !is_string($nombre) ||
    !is_string($correo)
) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

$nombre = trim($nombre);
$correo = trim($correo);

if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150
) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Consultar los datos anteriores
    $sqlAnterior = "SELECT id, nombre, correo, rol_id, activo
                    FROM usuarios
                    WHERE id = :id AND activo = 1
                    FOR UPDATE";

    $consultaAnterior = $pdo->prepare($sqlAnterior);
    $consultaAnterior->execute(['id' => $id]);

    $anterior = $consultaAnterior->fetch(PDO::FETCH_ASSOC);

    if (!$anterior) {
        $pdo->rollBack();
        header('Location: ../pages/usuarios.php?error=1');
        exit;
    }

    // Bloquear el rol administrador
    $consultaAdminRol = $pdo->prepare(
        "SELECT id FROM roles
        WHERE nombre = 'administrador'
        FOR UPDATE"
    );

    $consultaAdminRol->execute();
    $adminRolId = $consultaAdminRol->fetchColumn();

    if ($adminRolId === false) {
        throw new RuntimeException('Rol administrador no encontrado');
    }

    // Verificar que el rol exista
    $consultaRol = $pdo->prepare(
        "SELECT nombre FROM roles WHERE id = :id"
    );
    $consultaRol->execute(['id' => $rolId]);

    $nombreRol = $consultaRol->fetchColumn();

    if ($nombreRol === false) {
        $pdo->rollBack();
        header('Location: ../pages/usuarios.php?error=1');
        exit;
    }

    // Evitar cambiar el propio rol
    if (
        (int)$id === (int)$_SESSION['usuario_id'] &&
        (int)$rolId !== (int)$anterior['rol_id']
    ) {
        $pdo->rollBack();
        header('Location: ../pages/usuarios.php?error=1');
        exit;
    }

    // Proteger al ultimo administrador
    if (
        (int)$anterior['rol_id'] === (int)$adminRolId &&
        (int)$rolId !== (int)$adminRolId
    ) {
        $consultaAdmin = $pdo->prepare(
            "SELECT COUNT(*) FROM usuarios
            WHERE rol_id = :rol_id AND activo = 1"
        );

        $consultaAdmin->execute([
            'rol_id' => $adminRolId
        ]);

        if ((int)$consultaAdmin->fetchColumn() <= 1) {
            $pdo->rollBack();

            header('Location: ../pages/usuarios.php?error=1');
            exit;
        }
    }

    // Actualizar los datos del usuario
    $sql = "UPDATE usuarios
            SET nombre = :nombre,
                correo = :correo,
                rol_id = :rol_id
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        'nombre' => $nombre,
        'correo' => $correo,
        'rol_id' => $rolId,
        'id' => $id
    ]);

    // Registrar la edicion en bitacora
    $sqlBitacora = "INSERT INTO bitacora
                    (usuario_id, accion, entidad, entidad_id,
                     valores_antes, valores_despues, ip)
                    VALUES
                    (:usuario_id, :accion, :entidad, :entidad_id,
                     :valores_antes, :valores_despues, :ip)";

    $registro = $pdo->prepare($sqlBitacora);
    $registro->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'accion' => 'EDITAR',
        'entidad' => 'usuarios',
        'entidad_id' => $id,
        'valores_antes' => json_encode([
            'nombre' => $anterior['nombre'],
            'correo' => $anterior['correo'],
            'rol_id' => $anterior['rol_id']
        ], JSON_THROW_ON_ERROR),
        'valores_despues' => json_encode([
            'nombre' => $nombre,
            'correo' => $correo,
            'rol_id' => $rolId
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    // Actualizar el nombre de la sesion
    if ((int)$id === (int)$_SESSION['usuario_id']) {
        $_SESSION['nombre'] = $nombre;
    }

    header('Location: ../pages/usuarios.php?actualizado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios si ocurre un error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/usuarios.php?error=1');
    exit;
}
