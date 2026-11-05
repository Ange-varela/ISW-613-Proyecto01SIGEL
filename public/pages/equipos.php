
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_equipos');


// Obtener el texto de busqueda
$buscar = $_GET['buscar'] ?? '';

if (!is_string($buscar)) {
    $buscar = '';
}

$buscar = mb_substr(trim($buscar), 0, 100);

// Consultar los equipos activos
$sql = "SELECT e.id, e.codigo, e.nombre,
               e.estado, c.nombre AS categoria
        FROM equipos e
        INNER JOIN categorias c ON e.categoria_id = c.id
        WHERE e.activo = 1
        AND (e.nombre LIKE :nombre OR e.codigo LIKE :codigo)
        ORDER BY e.nombre ASC, e.id ASC";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    'nombre' => '%' . $buscar . '%',
    'codigo' => '%' . $buscar . '%'
]);

$equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Equipos - SIGEL</title>
    </head>
    <body>

        <h1>Gestion de equipos</h1>

        <h2>Lista de equipos</h2>

        <!-- Buscar equipos -->
        <form method="GET" action="equipos.php">

            <label for="buscar">Buscar equipo:</label>

            <input type="text" id="buscar" name="buscar"
                maxlength="100"
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <button type="submit">Buscar</button>

            <a href="equipos.php">Limpiar</a>

        </form>

        <br>

        <!-- Mostrar los equipos -->
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipos)): ?>
                    <tr>
                        <td colspan="5">No hay equipos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td><?php echo (int)$equipo['id']; ?></td>
                            <td><?php echo htmlspecialchars($equipo['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['categoria'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p><a href="dashboard.php">Volver al menu principal</a></p>

    </body>
</html>
