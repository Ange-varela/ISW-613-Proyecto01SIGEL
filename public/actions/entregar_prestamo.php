<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('gestionar_prestamos');

// Validar que sea POST y que el token CSRF sea correcto
validarFormulario();

// Obtener los datos
$prestamoId = filter_var(
    $_POST['prestamo_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$codigo = $_POST['codigo_retiro'] ?? '';

if (
    !$prestamoId ||
    !is_string($codigo) ||
    !preg_match('/^[0-9]{8}$/D', $codigo)
) {
    http_response_code(400);
    exit('Datos no validos');
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Bloquear y consultar el prestamo
    $consulta = $pdo->prepare(
        "SELECT estado, codigo_hash
         FROM prestamos
         WHERE id = ?
         FOR UPDATE"
    );

    $consulta->execute([$prestamoId]);
    $prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo || $prestamo['estado'] !== 'aprobado') {
        throw new RuntimeException('Prestamo no disponible');
    }

    // Verificar el codigo usando su hash
    if (
        empty($prestamo['codigo_hash']) ||
        !password_verify($codigo, $prestamo['codigo_hash'])
    ) {
        throw new RuntimeException('Codigo incorrecto');
    }

    // Registrar la entrega
    $consulta = $pdo->prepare(
        "UPDATE prestamos
         SET estado = 'entregado',
             codigo_hash = NULL
         WHERE id = ?"
    );

    $consulta->execute([$prestamoId]);

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
        json_encode(['estado' => 'aprobado'], JSON_THROW_ON_ERROR),
        json_encode(['estado' => 'entregado'], JSON_THROW_ON_ERROR),
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header(
        'Location: ../pages/gestionar_prestamo.php?id=' .
        $prestamoId . '&entregado=1'
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
        $prestamoId . '&error_entrega=1'
    );
    exit;
}
