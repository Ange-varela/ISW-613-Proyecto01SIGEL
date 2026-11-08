<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_equipos');

// Consultar las categorias activas
$sqlCategorias = "SELECT id, nombre
                  FROM categorias
                  WHERE activo = 1
                  ORDER BY nombre ASC";

$consultaCategorias = $pdo->prepare($sqlCategorias);
$consultaCategorias->execute();

$categorias = $consultaCategorias->fetchAll(PDO::FETCH_ASSOC);

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        <!-- Mostrar mensajes del sistema -->
        <?php if (($_GET['guardado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Equipo registrado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['actualizado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Equipo actualizado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <p style="color: red;">
                No se pudo completar la operacion. Verifique los datos.
            </p>
        <?php endif; ?>

        <?php if (($_GET['eliminado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Equipo eliminado correctamente.
            </p>
        <?php endif; ?>

        
        
        <h2>Registrar equipo</h2>

        <!-- Formulario para registrar equipos -->
        <?php if (tienePermiso('crear_equipo')): ?>
            <form action="../actions/guardar_equipo.php" method="POST">

                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                <label for="codigo">Codigo:</label>
                <input type="text" id="codigo" name="codigo"
                    maxlength="50" required>

                <br><br>

                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre"
                    maxlength="100" required>

                <br><br>

                <label for="descripcion">Descripcion:</label>
                <textarea id="descripcion" name="descripcion"></textarea>

                <br><br>

                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Seleccione una categoria</option>

                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo (int)$categoria['id']; ?>">
                            <?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <br><br>

                <button type="submit">Guardar equipo</button>

            </form>
        <?php endif; ?>
        <hr>
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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipos)): ?>
                    <tr>
                        <td colspan="6">No hay equipos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td><?php echo (int)$equipo['id']; ?></td>
                            <td><?php echo htmlspecialchars($equipo['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['categoria'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($equipo['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td>
                                <?php if (tienePermiso('editar_equipo')): ?>
                                    <a href="editar_equipo.php?id=<?php echo (int)$equipo['id']; ?>">
                                        Editar
                                    </a>
                                <?php endif; ?>

                                <?php if (tienePermiso('editar_equipo')): ?>
                                    <a href="imagenes_equipo.php?id=<?php echo (int)$equipo['id']; ?>">
                                        Imagenes
                                    </a>
                                <?php endif; ?>

                                <?php if (tienePermiso('eliminar_equipo')): ?>
                                    <!-- Formulario para desactivar equipos -->
                                    <form action="../actions/eliminar_equipo.php"
                                        method="POST" style="display:inline;">

                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                                        <input type="hidden" name="id"
                                            value="<?php echo (int)$equipo['id']; ?>">

                                        <button type="submit"
                                                onclick="return confirm('Desea eliminar este equipo?');">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p><a href="dashboard.php">Volver al menu principal</a></p>
    </body>
</html>
