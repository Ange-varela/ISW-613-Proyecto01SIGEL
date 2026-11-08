<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_reportes');

// Obtener los filtros
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Validar los tipos
if (!is_string($desde) || !is_string($hasta)) {
    http_response_code(400);
    exit('Fechas no validas');
}

// Validar una fecha
function fechaValida($fecha)
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $valor && $valor->format('Y-m-d') === $fecha;
}

// Revisar los filtros
if (
    ($desde !== '' && !fechaValida($desde)) ||
    ($hasta !== '' && !fechaValida($hasta)) ||
    ($desde !== '' && $hasta !== '' && $hasta < $desde)
) {
    http_response_code(400);
    exit('Rango de fechas no valido');
}

// Construir la consulta
$sql = "SELECT
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

// Aplicar la fecha inicial
if ($desde !== '') {
    $sql .= " AND p.fecha_inicio >= :desde";
    $parametros['desde'] = $desde . ' 00:00:00';
}

// Aplicar la fecha final
if ($hasta !== '') {
    $sql .= " AND p.fecha_inicio < :hasta";
    $parametros['hasta'] = (new DateTimeImmutable($hasta))
        ->modify('+1 day')
        ->format('Y-m-d 00:00:00');
}

// Ordenar los resultados
$sql .= " GROUP BY e.id, e.codigo, e.nombre
          ORDER BY total_prestamos DESC, e.nombre ASC";

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

// Evitar formulas al abrir el CSV
function protegerCsv($valor)
{
    $valor = (string)$valor;

    // Eliminar caracteres de control al inicio
    $valor = ltrim($valor, " \t\r\n");

    // Proteger posibles formulas
    if ($valor !== '' && preg_match('/^[=+\-@]/', $valor)) {
        $valor = "'" . $valor;
    }

    return $valor;
}

// Preparar la descarga
$nombreArchivo = 'reporte_prestamos_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: no-store');

// Abrir la salida
$salida = fopen('php://output', 'w');

// Escribir los encabezados
fputcsv($salida, [
    'Codigo',
    'Equipo',
    'Total de prestamos'
], ';');

// Escribir los resultados
while ($fila = $consulta->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($salida, [
        protegerCsv($fila['codigo']),
        protegerCsv($fila['nombre']),
        (int)$fila['total_prestamos']
    ], ';');
}

// Cerrar el archivo
fclose($salida);
exit;
