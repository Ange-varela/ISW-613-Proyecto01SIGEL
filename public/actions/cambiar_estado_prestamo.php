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

    
    // Generar el codigo al aprobar el prestamo
    $codigoRetiro = null;
    $codigoHash = null;

    if ($estadoActual === 'solicitado' && $nuevoEstado === 'aprobado') {
        // Generar un codigo aleatorio de ocho digitos
        $codigoRetiro = (string)random_int(10000000, 99999999);

        // Guardar solo el hash del codigo
        $codigoHash = password_hash($codigoRetiro, PASSWORD_DEFAULT);

        $consulta = $pdo->prepare(
            "UPDATE prestamos
            SET estado = ?, codigo_hash = ?
            WHERE id = ?"
        );

        $consulta->execute([
            $nuevoEstado,
            $codigoHash,
            $prestamoId
        ]);
    } else {
        // Actualizar los otros estados
        $consulta = $pdo->prepare(
            "UPDATE prestamos
            SET estado = ?
            WHERE id = ?"
        );

        $consulta->execute([$nuevoEstado, $prestamoId]);
    }

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

    // Mostrar el codigo una sola vez al aprobar
    if ($codigoRetiro !== null) {
        $_SESSION['codigo_retiro_temporal'] = [
            'prestamo_id' => $prestamoId,
            'codigo' => $codigoRetiro
        ];
    }
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
