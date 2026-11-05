
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('eliminar_equipo');

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

// Validar el ID
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Consultar y bloquear el equipo
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

    // Verificar prestamos activos
    $sqlPrestamos = "SELECT COUNT(*)
                     FROM prestamo_detalle pd
                     INNER JOIN prestamos p
                         ON pd.prestamo_id = p.id
                     WHERE pd.equipo_id = :id
                     AND p.estado IN (
                         'solicitado', 'aprobado', 'entregado'
                     )";

    $consultaPrestamos = $pdo->prepare($sqlPrestamos);
    $consultaPrestamos->execute(['id' => $id]);

    if ((int)$consultaPrestamos->fetchColumn() > 0) {
        $pdo->rollBack();
        header('Location: ../pages/equipos.php?error=1');
        exit;
    }

    // Desactivar el equipo
    $sql = "UPDATE equipos
            SET activo = 0
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $id]);

    // Registrar la eliminacion en bitacora
    $sqlBitacora = "INSERT INTO bitacora
                    (usuario_id, accion, entidad, entidad_id,
                     valores_antes, valores_despues, ip)
                    VALUES
                    (:usuario_id, :accion, :entidad, :entidad_id,
                     :valores_antes, :valores_despues, :ip)";

    $registro = $pdo->prepare($sqlBitacora);
    $registro->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'accion' => 'ELIMINAR',
        'entidad' => 'equipos',
        'entidad_id' => $id,
        'valores_antes' => json_encode($anterior, JSON_THROW_ON_ERROR),
        'valores_despues' => json_encode([
            'id' => $id,
            'codigo' => $anterior['codigo'],
            'nombre' => $anterior['nombre'],
            'descripcion' => $anterior['descripcion'],
            'categoria_id' => $anterior['categoria_id'],
            'estado' => $anterior['estado'],
            'activo' => 0
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/equipos.php?eliminado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/equipos.php?error=1');
    exit;
}
