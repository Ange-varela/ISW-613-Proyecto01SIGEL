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


 // Obtener la cantidad por pagina
$opciones = [5, 10, 20];

$porPagina = filter_input(INPUT_GET, 'por_pagina', FILTER_VALIDATE_INT);

if (!in_array($porPagina, $opciones, true)) {
    $porPagina = filter_var(
        $_COOKIE['equipos_por_pagina'] ?? null,
        FILTER_VALIDATE_INT
    );
}

// Usar 10 registros si no hay una preferencia valida
if (!in_array($porPagina, $opciones, true)) {
    $porPagina = 10;
}

// Guardar la preferencia
setcookie('equipos_por_pagina', (string)$porPagina, [
    'expires' => time() + 30 * 24 * 60 * 60,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Obtener la pagina actual
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);

if (!$pagina || $pagina < 1) {
    $pagina = 1;
}

// Preparar el filtro de busqueda
$filtro = '%' . $buscar . '%';

// Contar los equipos activos
$sqlTotal = "SELECT COUNT(*)
             FROM equipos e
             WHERE e.activo = 1
             AND (e.nombre LIKE :nombre OR e.codigo LIKE :codigo)";

$consultaTotal = $pdo->prepare($sqlTotal);

$consultaTotal->execute([
    'nombre' => $filtro,
    'codigo' => $filtro
]);

$totalEquipos = (int)$consultaTotal->fetchColumn();

// Calcular las paginas
$totalPaginas = max(1, (int)ceil($totalEquipos / $porPagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

// Consultar los equipos por pagina
$sql = "SELECT e.id, e.codigo, e.nombre,
               e.estado, c.nombre AS categoria
        FROM equipos e
        INNER JOIN categorias c ON e.categoria_id = c.id
        WHERE e.activo = 1
        AND (e.nombre LIKE :nombre OR e.codigo LIKE :codigo)
        ORDER BY e.nombre ASC, e.id ASC
        LIMIT :limite OFFSET :inicio";

$consulta = $pdo->prepare($sql);

$consulta->bindValue(':nombre', $filtro, PDO::PARAM_STR);
$consulta->bindValue(':codigo', $filtro, PDO::PARAM_STR);
$consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);

$consulta->execute();

$equipos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$titulo = 'Equipos';
require __DIR__ . '/../../includes/header.php';
?>

        <h1>Equipos</h1>
        <!-- Mostrar mensajes del sistema -->
        <?php if (($_GET['guardado'] ?? '') === '1'): ?>
            <p class="mensaje exito">
                Equipo registrado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['actualizado'] ?? '') === '1'): ?>
            <p class="mensaje exito">
                Equipo actualizado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <p class="mensaje error">
                No se pudo completar la operacion. Verifique los datos.
            </p>
        <?php endif; ?>

        <?php if (($_GET['eliminado'] ?? '') === '1'): ?>
            <p class="mensaje exito">
                Equipo eliminado correctamente.
            </p>
        <?php endif; ?>

        
        
        <!-- Formulario para registrar equipos (se abre con el boton) -->
        <?php if (tienePermiso('crear_equipo')): ?>
            <details class="agregar">
                <summary class="boton">
                    <span class="abrir">+ Agregar equipo</span>
                    <span class="cerrar">Cancelar y volver a la lista</span>
                </summary>

            <form class="panel" action="../actions/guardar_equipo.php" method="POST">

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
            </details>
        <?php endif; ?>
        
        <?php
        // Obtener los equipos recientes de la cookie
        $recientes = [];

        if (isset($_COOKIE['equipos_recientes'])) {
            $datos = json_decode($_COOKIE['equipos_recientes'], true);

            if (is_array($datos)) {
                foreach ($datos as $id) {
                    if (is_int($id) && $id > 0) {
                        $recientes[] = $id;
                    }
                }
            }
        }

        // Conservar solo cinco identificadores
        $recientes = array_slice(array_unique($recientes), 0, 5);

        $equiposRecientes = [];

        if (!empty($recientes)) {
            // Consultar solamente los equipos activos
            $marcadores = implode(',', array_fill(0, count($recientes), '?'));

            $sqlRecientes = "SELECT id, codigo, nombre
                            FROM equipos
                            WHERE activo = 1
                            AND id IN ($marcadores)";

            $consultaRecientes = $pdo->prepare($sqlRecientes);
            $consultaRecientes->execute($recientes);

            $resultados = $consultaRecientes->fetchAll(PDO::FETCH_ASSOC);

            // Mantener el orden de consulta
            $porId = [];

            foreach ($resultados as $equipo) {
                $porId[(int)$equipo['id']] = $equipo;
            }

            foreach ($recientes as $id) {
                if (isset($porId[$id])) {
                    $equiposRecientes[] = $porId[$id];
                }
            }
        }
        ?>

        <!-- Mostrar los ultimos equipos consultados -->
        <h2>Consultados recientemente</h2>

        <?php if (empty($equiposRecientes)): ?>
            <p>No hay equipos consultados recientemente.</p>
        <?php else: ?>
            <ul class="recientes">
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

                
        <!-- Buscar y cantidad por pagina -->
        <form class="barra" method="GET" action="equipos.php">
            <input type="text" id="buscar" name="buscar"
                maxlength="150" placeholder="Buscar por codigo o nombre..."
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <button type="submit">Buscar</button>
            <a class="boton boton-secundario" href="equipos.php">Limpiar</a>

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

        <!-- Mostrar los equipos -->
        <table>
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
                                <!-- Ver informacion del equipo -->
                                <a href="ver_equipo.php?id=<?php echo (int)$equipo['id']; ?>">
                                    Ver detalle
                                </a>
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
        
        <!-- Navegacion entre paginas -->
        <div class="paginacion">
            <?php if ($pagina > 1): ?>
                <a class="boton boton-secundario" href="equipos.php?<?php echo htmlspecialchars(http_build_query([
                    'buscar' => $buscar,
                    'por_pagina' => $porPagina,
                    'pagina' => $pagina - 1
                ]), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
            <?php endif; ?>

            <span>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $totalEquipos; ?> equipos)</span>

            <?php if ($pagina < $totalPaginas): ?>
                <a class="boton boton-secundario" href="equipos.php?<?php echo htmlspecialchars(http_build_query([
                    'buscar' => $buscar,
                    'por_pagina' => $porPagina,
                    'pagina' => $pagina + 1
                ]), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
