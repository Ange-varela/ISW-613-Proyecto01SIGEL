<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('crear_prestamo');

// Permitir solo POST
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
$profesorId = filter_var(
    $_POST['profesor_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$inicioTexto = $_POST['fecha_inicio'] ?? '';
$finTexto = $_POST['fecha_fin'] ?? '';
$equipos = $_POST['equipos'] ?? [];

// Validar datos basicos
if (
    !$profesorId ||
    !is_string($inicioTexto) ||
    !is_string($finTexto) ||
    !is_array($equipos) ||
    count($equipos) < 1 ||
    count($equipos) > 100
) {
    header('Location: ../pages/prestamos.php?error=datos');
    exit;
}

// Validar las fechas
$inicio = DateTimeImmutable::createFromFormat(
    '!Y-m-d\TH:i',
    $inicioTexto
);

$fin = DateTimeImmutable::createFromFormat(
    '!Y-m-d\TH:i',
    $finTexto
);

if (
    !$inicio ||
    !$fin ||
    $inicio->format('Y-m-d\TH:i') !== $inicioTexto ||
    $fin->format('Y-m-d\TH:i') !== $finTexto ||
    $fin <= $inicio
) {
    header('Location: ../pages/prestamos.php?error=fechas');
    exit;
}

$fechaInicio = $inicio->format('Y-m-d H:i:s');
$fechaFin = $fin->format('Y-m-d H:i:s');

// Validar los equipos seleccionados
$ids = [];

foreach ($equipos as $equipoId) {
    $id = filter_var(
        $equipoId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$id) {
        header('Location: ../pages/prestamos.php?error=datos');
        exit;
    }

    $ids[] = (int)$id;
}

// Evitar equipos repetidos
$ids = array_values(array_unique($ids));
sort($ids, SORT_NUMERIC);

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Comprobar que el profesor este activo
    $consulta = $pdo->prepare(
        "SELECT id FROM profesores
         WHERE id = ? AND activo = 1"
    );

    $consulta->execute([$profesorId]);

    if (!$consulta->fetchColumn()) {
        throw new RuntimeException('Profesor no disponible');
    }

    // Bloquear los equipos mientras se registra
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT id
            FROM equipos
            WHERE id IN ($marcadores)
            AND activo = 1
            AND estado = 'disponible'
            ORDER BY id
            FOR UPDATE";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($ids);

    $equiposValidos = $consulta->fetchAll(PDO::FETCH_COLUMN);

    if (count($equiposValidos) !== count($ids)) {
        throw new RuntimeException('Hay equipos no disponibles');
    }

    // Comprobar prestamos que coincidan en fechas
    $sql = "SELECT pd.equipo_id
            FROM prestamo_detalle pd
            INNER JOIN prestamos p
                ON p.id = pd.prestamo_id
            WHERE pd.equipo_id IN ($marcadores)
            AND p.estado IN (
                'solicitado',
                'aprobado',
                'entregado'
            )
            AND p.fecha_inicio < ?
            AND p.fecha_fin > ?
            LIMIT 1";

    $consulta = $pdo->prepare($sql);

    $parametros = array_merge(
        $ids,
        [$fechaFin, $fechaInicio]
    );

    $consulta->execute($parametros);

    if ($consulta->fetchColumn() !== false) {
        throw new RuntimeException('Los equipos ya estan reservados');
    }

    // Registrar el prestamo
    $sql = "INSERT INTO prestamos
            (profesor_id, operador_id, estado,
             fecha_inicio, fecha_fin)
            VALUES (?, ?, 'solicitado', ?, ?)";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        $profesorId,
        $_SESSION['usuario_id'],
        $fechaInicio,
        $fechaFin
    ]);

    $prestamoId = (int)$pdo->lastInsertId();

    // Guardar los equipos del prestamo
    $sql = "INSERT INTO prestamo_detalle
            (prestamo_id, equipo_id)
            VALUES (?, ?)";

    $consulta = $pdo->prepare($sql);

    foreach ($ids as $equipoId) {
        $consulta->execute([$prestamoId, $equipoId]);
    }

    // Registrar la operacion en bitacora
    $sql = "INSERT INTO bitacora
            (usuario_id, accion, entidad, entidad_id,
             valores_despues, ip)
            VALUES (?, 'CREAR', 'prestamos', ?, ?, ?)";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        $_SESSION['usuario_id'],
        $prestamoId,
        json_encode([
            'profesor_id' => $profesorId,
            'estado' => 'solicitado',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'equipos' => $ids
        ], JSON_THROW_ON_ERROR),
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar todos los cambios
    $pdo->commit();

    header('Location: ../pages/prestamos.php?guardado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios si algo falla
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/prestamos.php?error=registro');
    exit;
}
