<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('crear_categoria');

// Validar que sea POST y que el token CSRF sea correcto
validarFormulario();

// Obtener los datos del formulario
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

// Validar los datos
if (!is_string($nombre) || !is_string($descripcion)) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

$nombre = trim($nombre);
$descripcion = trim($descripcion);

if ($nombre === '' || mb_strlen($nombre) > 100 ||
    mb_strlen($descripcion) > 255) {
    header('Location: ../pages/categorias.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Guardar la categoria
    $sql = "INSERT INTO categorias (nombre, descripcion)
            VALUES (:nombre, :descripcion)";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion
    ]);

    // Obtener el ID de la categoria creada
    $categoriaId = (int) $pdo->lastInsertId();

    // Registrar la creacion en la bitacora
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
        'entidad' => 'categorias',
        'entidad_id' => $categoriaId,
        'valores_despues' => json_encode([
            'nombre' => $nombre,
            'descripcion' => $descripcion
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/categorias.php?guardado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios si ocurre un error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/categorias.php?error=1');
    exit;
}

