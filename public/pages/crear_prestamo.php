
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('crear_prestamo');

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Consultar los profesores activos
$sql = "SELECT id, nombre
        FROM profesores
        WHERE activo = 1
        ORDER BY nombre ASC";

$consulta = $pdo->prepare($sql);
$consulta->execute();
$profesores = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Consultar los equipos activos
$sql = "SELECT id, codigo, nombre
        FROM equipos
        WHERE activo = 1
        AND estado = 'disponible'
        ORDER BY nombre ASC, codigo ASC";

$consulta = $pdo->prepare($sql);
$consulta->execute();
$equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Crear prestamo - SIGEL</title>
    </head>
    <body>
        <h1>Registrar solicitud de prestamo</h1>
        <form action="../actions/guardar_prestamo.php" method="POST">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <label for="profesor_id">Profesor:</label>

            <select name="profesor_id" id="profesor_id" required>
                <option value="">Seleccione un profesor</option>

                <?php foreach ($profesores as $profesor): ?>
                    <option value="<?php echo (int)$profesor['id']; ?>">
                        <?php echo htmlspecialchars($profesor['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <label for="fecha_inicio">Fecha de inicio:</label>

            <input type="datetime-local" id="fecha_inicio"
                name="fecha_inicio" required>

            <br><br>

            <label for="fecha_fin">Fecha de fin:</label>

            <input type="datetime-local" id="fecha_fin"
                name="fecha_fin" required>

            <br><br>

            <h2>Seleccionar equipos</h2>

            <p>Puede seleccionar uno o varios equipos.</p>

            <?php if (empty($equipos)): ?>
                <p>No hay equipos disponibles.</p>
            <?php else: ?>
                <?php foreach ($equipos as $equipo): ?>
                    <p>
                        <label>
                            <input type="checkbox" name="equipos[]"
                                value="<?php echo (int)$equipo['id']; ?>">

                            <?php
                            echo htmlspecialchars(
                                $equipo['codigo'] . ' - ' . $equipo['nombre'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            );
                            ?>
                        </label>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>

            <button type="submit" <?php echo empty($equipos) ? 'disabled' : ''; ?>>
                Registrar solicitud
            </button>

        </form>

        <p>
            <a href="prestamos.php">Volver a prestamos</a>
        </p>

    </body>
</html>
