
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_equipo');

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
$categoriaId = filter_var(
    $_POST['categoria_id'] ?? null,
    FILTER_VALIDATE_INT
);

$codigo = $_POST['codigo'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

// Validar los tipos
if (
    !$id || $id < 1 ||
    !$categoriaId || $categoriaId < 1 ||
    !is_string($codigo) ||
    !is_string($nombre) ||
    !is_string($descripcion)
) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

// Limpiar los datos
$codigo = trim($codigo);
$nombre = trim($nombre);
$descripcion = trim($descripcion);

// Validar los campos
if (
    $codigo === '' ||
    mb_strlen($codigo) > 50 ||
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    mb_strlen($descripcion) > 5000
) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Consultar los datos anteriores
    $sqlAnterior = "SELECT id, codigo, nombre, descripcion,
                           categoria_id, estado, activo
                    FROM equipos
                    WHERE id = :id AND activo = 1
                    FOR UPDATE";

    $consultaAnterior = $pdo->prepare($sqlAnterior);
    $consultaAnterior->execute(['id' => $id]);

    $anterior = $consultaAnterior->fetch(PDO::FETCH_ASSOC);

    if (!$anterior) {
        $pdo->rollBack();
        header('Location: ../pages/equipos.php?error=1');
        exit;
    }

    // Verificar que la categoria este activa
    $consultaCategoria = $pdo->prepare(
        "SELECT id FROM categorias
         WHERE id = :id AND activo = 1
         FOR UPDATE"
    );

    $consultaCategoria->execute(['id' => $categoriaId]);

    if (!$consultaCategoria->fetchColumn()) {
        $pdo->rollBack();
        header('Location: ../pages/equipos.php?error=1');
        exit;
    }

    // Actualizar el equipo
    $sql = "UPDATE equipos
            SET codigo = :codigo,
                nombre = :nombre,
                descripcion = :descripcion,
                categoria_id = :categoria_id
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'categoria_id' => $categoriaId,
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
        'entidad' => 'equipos',
        'entidad_id' => $id,
        'valores_antes' => json_encode([
            'codigo' => $anterior['codigo'],
            'nombre' => $anterior['nombre'],
            'descripcion' => $anterior['descripcion'],
            'categoria_id' => $anterior['categoria_id']
        ], JSON_THROW_ON_ERROR),
        'valores_despues' => json_encode([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'categoria_id' => $categoriaId
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/equipos.php?actualizado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios si ocurre un error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/equipos.php?error=1');
    exit;
}
