
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_categorias');

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

// Cantidad de registros permitidos
$opciones = [5, 10, 20];

// Obtener la cantidad por pagina
$porPagina = filter_var(
    $_GET['por_pagina'] ?? $_COOKIE['categorias_por_pagina'] ?? 10,
    FILTER_VALIDATE_INT
);

if (!in_array($porPagina, $opciones, true)) {
    $porPagina = 10;
}

// Guardar la preferencia en una cookie
setcookie('categorias_por_pagina', (string)$porPagina, [
    'expires' => time() + 60 * 60 * 24 * 30,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);

// Obtener la pagina actual
$pagina = filter_var(
    $_GET['pagina'] ?? 1,
    FILTER_VALIDATE_INT
);

if ($pagina === false || $pagina < 1) {
    $pagina = 1;
}

// Contar las categorias encontradas
$conteo = $pdo->prepare(
    "SELECT COUNT(*) FROM categorias
     WHERE activo = 1 AND nombre LIKE :buscar"
);

$conteo->execute([
    'buscar' => '%' . $buscar . '%'
]);

$total = (int) $conteo->fetchColumn();

// Calcular las paginas
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$pagina = min($pagina, $totalPaginas);
$inicio = ($pagina - 1) * $porPagina;

// Consultar las categorias de la pagina
$sql = "SELECT id, nombre, descripcion
        FROM categorias
        WHERE activo = 1 AND nombre LIKE :buscar
        ORDER BY nombre ASC, id ASC
        LIMIT :limite OFFSET :inicio";

$consulta = $pdo->prepare($sql);
$consulta->bindValue(':buscar', '%' . $buscar . '%', PDO::PARAM_STR);
$consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$consulta->execute();

$categorias = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Categorias - SIGEL</title>
    </head>
    <body>

        <h1>Gestion de categorias</h1>

        <h2>Registrar categoria</h2>

        <!-- Formulario para registrar categorias -->
        <form action="../actions/guardar_categoria.php" method="POST">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre"
                maxlength="100" required>

            <br><br>

            <label for="descripcion">Descripcion:</label>
            <textarea id="descripcion" name="descripcion"
                    maxlength="255"></textarea>

            <br><br>

            <button type="submit">Guardar categoria</button>
        </form>

        <hr>

        <!-- Buscar categorias -->
        <form method="GET" action="categorias.php">

            <label for="buscar">Buscar categoria:</label>

            <input type="text" id="buscar" name="buscar"
                maxlength="100"
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <input type="hidden" name="por_pagina"
                value="<?php echo $porPagina; ?>">

            <button type="submit">Buscar</button>
            <a href="categorias.php">Limpiar</a>
        </form>

        <h2>Lista de categorias</h2>

        <!-- Seleccionar registros por pagina -->
        <form method="GET" action="categorias.php">

            <input type="hidden" name="buscar"
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <label for="por_pagina">Registros por pagina:</label>

            <select name="por_pagina" id="por_pagina">
                <?php foreach ($opciones as $cantidad): ?>
                    <option value="<?php echo $cantidad; ?>"
                        <?php echo $porPagina === $cantidad ? 'selected' : ''; ?>>
                        <?php echo $cantidad; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Mostrar</button>
        </form>

        <br>

        <!-- Mostrar las categorias -->
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="4">No se encontraron categorias.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td><?php echo (int) $categoria['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            
                            
                            <td>
                                <?php if (tienePermiso('editar_categoria')): ?>
                                    <a href="editar_categoria.php?id=<?php echo (int)$categoria['id']; ?>">
                                        Editar
                                    </a>
                                <?php endif; ?>

                                <?php if (tienePermiso('eliminar_categoria')): ?>
                                    <!-- Formulario para eliminar categorias -->
                                    <form action="../actions/eliminar_categoria.php"
                                        method="POST" style="display:inline;">

                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                                        <input type="hidden" name="id"
                                            value="<?php echo (int)$categoria['id']; ?>">

                                        <button type="submit"
                                                onclick="return confirm('Desea eliminar esta categoria?');">
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

        <!-- Navegacion entre paginas -->
        <p>
            Pagina <?php echo $pagina; ?>
            de <?php echo $totalPaginas; ?>
        </p>

        <?php if ($pagina > 1): ?>
            <a href="?<?php echo htmlspecialchars(http_build_query([
                'buscar' => $buscar,
                'por_pagina' => $porPagina,
                'pagina' => $pagina - 1
            ]), ENT_QUOTES, 'UTF-8'); ?>">
                Anterior
            </a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="?<?php echo htmlspecialchars(http_build_query([
                'buscar' => $buscar,
                'por_pagina' => $porPagina,
                'pagina' => $pagina + 1
            ]), ENT_QUOTES, 'UTF-8'); ?>">
                Siguiente
            </a>
        <?php endif; ?>

        <hr>

        <p><a href="dashboard.php">Volver al menu principal</a></p>

    </body>
</html>
