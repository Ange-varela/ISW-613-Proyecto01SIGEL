<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('gestionar_prestamos');

// Obtener el ID del prestamo
$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(400);
    exit('Prestamo no valido');
}

// Consultar los datos del prestamo
$sql = "SELECT
            p.id,
            p.estado,
            p.fecha_inicio,
            p.fecha_fin,
            pr.nombre AS profesor
        FROM prestamos p
        INNER JOIN profesores pr ON p.profesor_id = pr.id
        WHERE p.id = ?";

$consulta = $pdo->prepare($sql);
$consulta->execute([$id]);
$prestamo = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$prestamo) {
    http_response_code(404);
    exit('Prestamo no encontrado');
}

// Consultar los equipos del prestamo
$sql = "SELECT e.codigo, e.nombre
        FROM prestamo_detalle pd
        INNER JOIN equipos e ON pd.equipo_id = e.id
        WHERE pd.prestamo_id = ?
        ORDER BY e.nombre ASC";

$consulta = $pdo->prepare($sql);
$consulta->execute([$id]);
$equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Recuperar el codigo temporal
$codigoMostrar = null;

if (isset($_SESSION['codigo_retiro_temporal'])) {
    $temporal = $_SESSION['codigo_retiro_temporal'];

    if (
        is_array($temporal) &&
        ($temporal['prestamo_id'] ?? null) === (int)$prestamo['id']
    ) {
        $codigoMostrar = $temporal['codigo'] ?? null;

        // Eliminarlo de la sesion despues de recuperarlo
        unset($_SESSION['codigo_retiro_temporal']);
    }
}

// Determinar las acciones permitidas
$acciones = [];

switch ($prestamo['estado']) {
    case 'solicitado':
        $acciones = [
            'aprobado' => 'Aprobar prestamo',
            'cancelado' => 'Cancelar prestamo'
        ];
        break;

    case 'aprobado':
        // La entrega validara el codigo de retiro al implementar la Regla 18
        $acciones = [
            'cancelado' => 'Cancelar prestamo'
        ];
        break;

    case 'entregado':
        $acciones = [
            'devuelto' => 'Registrar devolucion'
        ];
        break;
}
?>

<?php
$titulo = 'Gestionar prestamo';
require __DIR__ . '/../../includes/header.php';
?>
        <?php if (($_GET['actualizado'] ?? '') === '1'): ?>
            <p class="mensaje exito">Estado actualizado correctamente.</p>
        <?php endif; ?>

        <!-- Mensajes de entrega -->
        <?php if (($_GET['entregado'] ?? '') === '1'): ?>
            <p class="mensaje exito">
                Entrega registrada correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error_entrega'] ?? '') === '1'): ?>
            <p class="mensaje error">
                No se pudo registrar la entrega. Verifique el codigo de retiro.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <p class="mensaje error">No se pudo cambiar el estado del prestamo.</p>
        <?php endif; ?>

        <h1>Gestionar prestamo #<?php echo (int)$prestamo['id']; ?></h1>

        <p>
            <strong>Profesor:</strong>
            <?php echo htmlspecialchars($prestamo['profesor'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <p>
            <strong>Estado:</strong>
            <?php echo htmlspecialchars($prestamo['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <p>
            <strong>Inicio:</strong>
            <?php echo htmlspecialchars($prestamo['fecha_inicio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <p>
            <strong>Fin:</strong>
            <?php echo htmlspecialchars($prestamo['fecha_fin'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <!-- Mostrar el codigo generado -->
        <?php if ($codigoMostrar !== null): ?>
            <h2>Codigo de retiro</h2>

            <p>
                <strong>
                    <?php echo htmlspecialchars($codigoMostrar, ENT_QUOTES, 'UTF-8'); ?>
                </strong>
            </p>

            <p>
                Guarde este codigo. Solo se muestra una vez
                y sera necesario para entregar los equipos.
            </p>
        <?php endif; ?>

        <h2>Equipos solicitados</h2>

        <ul>
            <?php foreach ($equipos as $equipo): ?>
                <li>
                    <?php
                    echo htmlspecialchars(
                        $equipo['codigo'] . ' - ' . $equipo['nombre'],
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Registrar entrega con codigo de retiro -->
        <?php if ($prestamo['estado'] === 'aprobado'): ?>

            <h2>Registrar entrega</h2>

            <form action="../actions/entregar_prestamo.php" method="POST">

                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <input type="hidden" name="prestamo_id"
                    value="<?php echo (int)$prestamo['id']; ?>">

                <label for="codigo_retiro">Codigo de retiro:</label>

                <input type="text"
                    id="codigo_retiro"
                    name="codigo_retiro"
                    inputmode="numeric"
                    pattern="[0-9]{8}"
                    maxlength="8"
                    autocomplete="off"
                    required>

                <button type="submit">Confirmar entrega</button>

            </form>

        <?php endif; ?> 

        <h2>Acciones disponibles</h2>

        <?php if (empty($acciones)): ?>
            <p>Este prestamo no tiene acciones pendientes.</p>
        <?php else: ?>
            <?php foreach ($acciones as $nuevoEstado => $texto): ?>
                <form action="../actions/cambiar_estado_prestamo.php"
                    method="POST">

                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                    <input type="hidden" name="prestamo_id"
                        value="<?php echo (int)$prestamo['id']; ?>">

                    <input type="hidden" name="nuevo_estado"
                        value="<?php echo htmlspecialchars($nuevoEstado, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                    <button type="submit">
                        <?php echo htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </button>
                </form>
                <br>
            <?php endforeach; ?>
        <?php endif; ?>
        <p><a class="boton boton-secundario" href="prestamos.php">Volver a prestamos</a></p>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
