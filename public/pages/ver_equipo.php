<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_equipos');

// Obtener el ID del equipo
$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(400);
    exit('Equipo no valido');
}

// Consultar el equipo
$sql = "SELECT e.id, e.codigo, e.nombre, e.descripcion,
               e.estado, c.nombre AS categoria
        FROM equipos e
        INNER JOIN categorias c ON e.categoria_id = c.id
        WHERE e.id = ? AND e.activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute([$id]);
$equipo = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    http_response_code(404);
    exit('Equipo no encontrado');
}

// Obtener el historial guardado
$historial = [];

if (isset($_COOKIE['equipos_recientes'])) {
    $datos = json_decode($_COOKIE['equipos_recientes'], true);

    if (is_array($datos)) {
        foreach ($datos as $valor) {
            if (is_int($valor) && $valor > 0) {
                $historial[] = $valor;
            }
        }
    }
}

// Agregar el equipo actual al inicio
$historial = array_values(
    array_diff($historial, [$id])
);

array_unshift($historial, $id);

// Conservar solo cinco equipos
$historial = array_slice($historial, 0, 5);

// Buscar los equipos del historial
$equiposRecientes = [];

if (!empty($historial)) {
    $marcadores = implode(',', array_fill(0, count($historial), '?'));

    $sql = "SELECT id, codigo, nombre
            FROM equipos
            WHERE id IN ($marcadores)
            AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($historial);

    $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);

    // Mantener el orden de las visitas
    $porId = [];

    foreach ($resultados as $resultado) {
        $porId[(int)$resultado['id']] = $resultado;
    }

    foreach ($historial as $equipoId) {
        if (isset($porId[$equipoId])) {
            $equiposRecientes[] = $porId[$equipoId];
        }
    }
}

// Guardar la cookie
setcookie('equipos_recientes', json_encode($historial), [
    'expires' => time() + 30 * 24 * 60 * 60,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalle del equipo - SIGEL</title>
    </head>
    <body>

        <h1>Detalle del equipo</h1>

        <p><strong>Codigo:</strong>
            <?php echo htmlspecialchars($equipo['codigo'], ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <p><strong>Nombre:</strong>
            <?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <p><strong>Categoria:</strong>
            <?php echo htmlspecialchars($equipo['categoria'], ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <p><strong>Descripcion:</strong>
            <?php echo htmlspecialchars($equipo['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <p><strong>Estado:</strong>
            <?php echo htmlspecialchars($equipo['estado'], ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <!-- Mostrar los ultimos cinco equipos consultados -->
        <h2>Ultimos equipos consultados</h2>

        <?php if (empty($equiposRecientes)): ?>
            <p>No hay equipos recientes.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($equiposRecientes as $reciente): ?>
                    <li>
                        <a href="ver_equipo.php?id=<?php echo (int)$reciente['id']; ?>">
                            <?php
                            echo htmlspecialchars(
                                $reciente['codigo'] . ' - ' . $reciente['nombre'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            );
                            ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><a href="equipos.php">Volver a equipos</a></p>
    </body>
</html>
