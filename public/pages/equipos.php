
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_equipos');

// Consultar los equipos activos
$sql = "SELECT e.id, e.codigo, e.nombre,
               e.estado, c.nombre AS categoria
        FROM equipos e
        INNER JOIN categorias c ON e.categoria_id = c.id
        WHERE e.activo = 1
        ORDER BY e.nombre ASC";

$consulta = $pdo->prepare($sql);
$consulta->execute();

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
