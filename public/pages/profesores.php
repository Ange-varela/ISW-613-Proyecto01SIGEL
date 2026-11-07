
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar la sesion
verificarSesion();

// Permitir solo al administrador
if (!tienePermiso('ver_usuarios')) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Obtener el texto de busqueda
$buscar = $_GET['buscar'] ?? '';

if (!is_string($buscar)) {
    $buscar = '';
}

$buscar = mb_substr(trim($buscar), 0, 150);

// Consultar los profesores activos
$sql = "SELECT id, nombre, correo
        FROM profesores
        WHERE activo = 1
        AND (nombre LIKE :nombre OR correo LIKE :correo)
        ORDER BY nombre ASC, id ASC";

$consulta = $pdo->prepare($sql);

$consulta->execute([
    'nombre' => '%' . $buscar . '%',
    'correo' => '%' . $buscar . '%'
]);

$profesores = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profesores - SIGEL</title>
    </head>
    <body>

        <h1>Gestion de profesores</h1>

        <h2>Lista de profesores</h2>

        <!-- Buscar profesores -->
        <form method="GET" action="profesores.php">

            <label for="buscar">Buscar profesor:</label>

            <input type="text" id="buscar" name="buscar"
                maxlength="150"
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <button type="submit">Buscar</button>
            <a href="profesores.php">Limpiar</a>

        </form>

        <br>

        <!-- Mostrar los profesores -->
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($profesores)): ?>
                    <tr>
                        <td colspan="3">No se encontraron profesores.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($profesores as $profesor): ?>
                        <tr>
                            <td><?php echo (int)$profesor['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($profesor['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($profesor['correo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p><a href="dashboard.php">Volver al menu principal</a></p>

    </body>
</html>
