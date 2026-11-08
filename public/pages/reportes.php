
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_reportes');

// Obtener las fechas del filtro
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Validar los tipos
if (!is_string($desde)) {
    $desde = '';
}

if (!is_string($hasta)) {
    $hasta = '';
}

// Validar las fechas
function fechaValida($fecha)
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $valor && $valor->format('Y-m-d') === $fecha;
}

$error = '';

if ($desde !== '' && !fechaValida($desde)) {
    $error = 'La fecha inicial no es valida.';
}

if ($hasta !== '' && !fechaValida($hasta)) {
    $error = 'La fecha final no es valida.';
}

if ($error === '' && $desde !== '' && $hasta !== '' && $hasta < $desde) {
    $error = 'La fecha final debe ser igual o posterior a la inicial.';
}

// Consultar los equipos mas prestados
$reportes = [];

if ($error === '') {
    $sql = "SELECT
                e.id,
                e.codigo,
                e.nombre,
                COUNT(DISTINCT p.id) AS total_prestamos
            FROM equipos e
            INNER JOIN prestamo_detalle pd
                ON pd.equipo_id = e.id
            INNER JOIN prestamos p
                ON p.id = pd.prestamo_id
            WHERE p.estado IN ('aprobado', 'entregado', 'devuelto')";

    $parametros = [];

    if ($desde !== '') {
        $sql .= " AND p.fecha_inicio >= :desde";
        $parametros['desde'] = $desde . ' 00:00:00';
    }

    if ($hasta !== '') {
        $sql .= " AND p.fecha_inicio < :hasta";
        $parametros['hasta'] = (new DateTimeImmutable($hasta))
            ->modify('+1 day')
            ->format('Y-m-d 00:00:00');
    }

    $sql .= " GROUP BY e.id, e.codigo, e.nombre
              ORDER BY total_prestamos DESC, e.nombre ASC";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);

    $reportes = $consulta->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reportes - SIGEL</title>
    </head>
    <body>

        <h1>Reporte de equipos mas prestados</h1>

        <!-- Filtrar por fechas -->
        <form method="GET" action="reportes.php">

            <label for="desde">Desde:</label>
            <input type="date" id="desde" name="desde"
                value="<?php echo htmlspecialchars($desde, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="hasta">Hasta:</label>
            <input type="date" id="hasta" name="hasta"
                value="<?php echo htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8'); ?>">

            <button type="submit">Consultar</button>

            <a href="reportes.php">Limpiar</a>
        </form>
        
        <!-- Descargar el reporte en CSV -->
        <?php if ($error === ''): ?>
            <form action="../actions/exportar_reporte.php" method="GET">

                <input type="hidden" name="desde"
                    value="<?php echo htmlspecialchars($desde, ENT_QUOTES, 'UTF-8'); ?>">

                <input type="hidden" name="hasta"
                    value="<?php echo htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8'); ?>">

                <button type="submit">Exportar a CSV</button>
            </form>
        <?php endif; ?>


        <?php if ($error !== ''): ?>
            <p style="color: red;">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php else: ?>

            <h2>Resultados</h2>

            <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Equipo</th>
                        <th>Total de prestamos</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($reportes)): ?>
                        <tr>
                            <td colspan="3">No hay prestamos para mostrar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportes as $reporte): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($reporte['codigo'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($reporte['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <?php echo (int)$reporte['total_prestamos']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="dashboard.php">Volver al menu principal</a></p>
    </body>
</html>
