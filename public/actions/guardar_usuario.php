<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('crear_usuario');

// Validar que sea POST y que el token CSRF sea correcto
validarFormulario();

// Obtener los datos del formulario
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$rolId = filter_var($_POST['rol_id'] ?? null, FILTER_VALIDATE_INT);

// Validar los tipos de datos
if (
    !is_string($nombre) ||
    !is_string($correo) ||
    !is_string($contrasena)
) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

// Limpiar los datos
$nombre = trim($nombre);
$correo = trim($correo);

// Validar los campos
if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    strlen($contrasena) < 8 ||
    strlen($contrasena) > 72 ||
    !$rolId ||
    $rolId < 1
) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

try {
    // Verificar que el rol exista
    $consultaRol = $pdo->prepare(
        "SELECT COUNT(*) FROM roles WHERE id = :id"
    );
    $consultaRol->execute(['id' => $rolId]);

    if ((int)$consultaRol->fetchColumn() === 0) {
        header('Location: ../pages/usuarios.php?error=1');
        exit;
    }

    // Proteger la contrasena
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Registrar el usuario
    $sql = "INSERT INTO usuarios
            (rol_id, nombre, correo, password_hash)
            VALUES (:rol_id, :nombre, :correo, :password_hash)";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        'rol_id' => $rolId,
        'nombre' => $nombre,
        'correo' => $correo,
        'password_hash' => $hash
    ]);

    // Obtener el ID del usuario creado
    $usuarioId = (int)$pdo->lastInsertId();

    // Registrar la creacion en bitacora
    $sqlBitacora = "INSERT INTO bitacora
                    (usuario_id, accion, entidad, entidad_id,
                     valores_despues, ip)
                    VALUES
                    (:usuario_id, :accion, :entidad, :entidad_id,
                     :valores_despues, :ip)";

    $registro = $pdo->prepare($sqlBitacora);
    $registro->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'accion' => 'CREAR',
        'entidad' => 'usuarios',
        'entidad_id' => $usuarioId,
        'valores_despues' => json_encode([
            'nombre' => $nombre,
            'correo' => $correo,
            'rol_id' => $rolId,
            'activo' => 1
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/usuarios.php?guardado=1');
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
