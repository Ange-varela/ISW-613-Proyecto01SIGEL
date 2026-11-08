<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('gestionar_prestamos');

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Validar el token de seguridad
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
$prestamoId = filter_var(
    $_POST['prestamo_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$nuevoEstado = $_POST['nuevo_estado'] ?? '';

if (!$prestamoId || !is_string($nuevoEstado)) {
    http_response_code(400);
    exit('Datos no validos');
}

// Definir los cambios permitidos
$transiciones = [
    'solicitado' => ['aprobado', 'cancelado'],
    'aprobado' => ['cancelado'],
    'entregado' => ['devuelto']
];

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Bloquear el prestamo durante el cambio
    $consulta = $pdo->prepare(
        "SELECT estado
         FROM prestamos
         WHERE id = ?
         FOR UPDATE"
    );

    $consulta->execute([$prestamoId]);
    $estadoActual = $consulta->fetchColumn();

    if ($estadoActual === false) {
        throw new RuntimeException('Prestamo no encontrado');
    }

    // Comprobar que el cambio sea permitido
    if (
        !isset($transiciones[$estadoActual]) ||
        !in_array(
            $nuevoEstado,
            $transiciones[$estadoActual],
            true
        )
    ) {
        throw new RuntimeException('Cambio de estado no permitido');
    }

    // Actualizar el estado
    $consulta = $pdo->prepare(
        "UPDATE prestamos
         SET estado = ?
         WHERE id = ?"
    );

    $consulta->execute([$nuevoEstado, $prestamoId]);

    // Registrar el cambio en bitacora
    $consulta = $pdo->prepare(
        "INSERT INTO bitacora
         (usuario_id, accion, entidad, entidad_id,
          valores_antes, valores_despues, ip)
         VALUES (?, 'CAMBIAR_ESTADO', 'prestamos', ?, ?, ?, ?)"
    );

    $consulta->execute([
        $_SESSION['usuario_id'],
        $prestamoId,
        json_encode(
            ['estado' => $estadoActual],
            JSON_THROW_ON_ERROR
        ),
        json_encode(
            ['estado' => $nuevoEstado],
            JSON_THROW_ON_ERROR
        ),
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar el cambio
    $pdo->commit();

    header(
        'Location: ../pages/gestionar_prestamo.php?id=' .
        $prestamoId . '&actualizado=1'
    );
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header(
        'Location: ../pages/gestionar_prestamo.php?id=' .
        $prestamoId . '&error=1'
    );
    exit;
}
