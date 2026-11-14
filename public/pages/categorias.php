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

// Usar 10 registros si no hay una preferencia valida
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

<?php
$titulo = 'Categorias';
require __DIR__ . '/../../includes/header.php';
?>

        <!-- Titulo y boton para agregar -->
        <div class="encabezado">
            <h1>Categorias</h1>
        </div>

        <!-- Formulario para registrar categorias (se abre con el boton) -->
        <?php if (tienePermiso('crear_categoria')): ?>
            <details class="agregar">
                <summary class="boton">
                    <span class="abrir">+ Agregar categoria</span>
                    <span class="cerrar">Cancelar y volver a la lista</span>
                </summary>

                <form class="panel" action="../actions/guardar_categoria.php" method="POST">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                    <p>
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre"
                            maxlength="100" required>
                    </p>

                    <p>
                        <label for="descripcion">Descripcion:</label>
                        <textarea id="descripcion" name="descripcion"
                            maxlength="255"></textarea>
                    </p>

                    <button type="submit">Guardar categoria</button>
                </form>
            </details>
        <?php endif; ?>

        <!-- Buscar y cantidad por pagina -->
        <form class="barra" method="GET" action="categorias.php">
            <input type="text" id="buscar" name="buscar"
                maxlength="100" placeholder="Buscar categoria..."
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <button type="submit">Buscar</button>
            <a class="boton boton-secundario" href="categorias.php">Limpiar</a>

            <span class="separar"></span>

            <label for="por_pagina">Por pagina:</label>
            <select name="por_pagina" id="por_pagina" onchange="this.form.submit()">
                <?php foreach ($opciones as $cantidad): ?>
                    <option value="<?php echo $cantidad; ?>"
                        <?php echo $porPagina === $cantidad ? 'selected' : ''; ?>>
                        <?php echo $cantidad; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Mostrar las categorias -->
        <table>
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
                                    <a class="boton boton-editar" href="editar_categoria.php?id=<?php echo (int)$categoria['id']; ?>">
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
        <div class="paginacion">
            <?php if ($pagina > 1): ?>
                <a class="boton boton-secundario" href="?<?php echo htmlspecialchars(http_build_query([
                    'buscar' => $buscar,
                    'por_pagina' => $porPagina,
                    'pagina' => $pagina - 1
                ]), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
            <?php endif; ?>

            <span>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></span>

            <?php if ($pagina < $totalPaginas): ?>
                <a class="boton boton-secundario" href="?<?php echo htmlspecialchars(http_build_query([
                    'buscar' => $buscar,
                    'por_pagina' => $porPagina,
                    'pagina' => $pagina + 1
                ]), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            <?php endif; ?>
        </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
