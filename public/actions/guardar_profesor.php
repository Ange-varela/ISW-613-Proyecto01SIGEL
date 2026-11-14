<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar la sesion y permiso
verificarPermiso('crear_usuario');

// Validar que sea POST y que el token CSRF sea correcto
validarFormulario();

// Obtener los datos
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';

// Validar los tipos
if (!is_string($nombre) || !is_string($correo)) {
    header('Location: ../pages/profesores.php?error=1');
    exit;
}

$nombre = trim($nombre);
$correo = trim($correo);

// Validar los campos
if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150
) {
    header('Location: ../pages/profesores.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Registrar el profesor
    $sql = "INSERT INTO profesores (nombre, correo)
            VALUES (:nombre, :correo)";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        'nombre' => $nombre,
        'correo' => $correo
    ]);

    $profesorId = (int)$pdo->lastInsertId();

    // Registrar la creacion en bitacora
    $sqlBitacora = "INSERT INTO bitacora
                    (usuario_id, accion, entidad, entidad_id,
                     valores_despues, ip)
                    VALUES
                    (:usuario_id, 'CREAR', 'profesores', :entidad_id,
                     :valores_despues, :ip)";

    $registro = $pdo->prepare($sqlBitacora);
    $registro->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'entidad_id' => $profesorId,
        'valores_despues' => json_encode([
            'nombre' => $nombre,
            'correo' => $correo,
            'activo' => 1
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/profesores.php?guardado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/profesores.php?error=1');
    exit;
}
