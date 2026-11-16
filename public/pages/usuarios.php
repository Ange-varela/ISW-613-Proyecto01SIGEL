<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('ver_usuarios');

// Consultar los roles disponibles
$sqlRoles = "SELECT id, nombre FROM roles ORDER BY nombre ASC";

$consultaRoles = $pdo->prepare($sqlRoles);
$consultaRoles->execute();

$roles = $consultaRoles->fetchAll(PDO::FETCH_ASSOC);

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener el texto de busqueda
$buscar = $_GET['buscar'] ?? '';

if (!is_string($buscar)) {
    $buscar = '';
}
$buscar = mb_substr(trim($buscar), 0, 150);

// Obtener la cantidad por pagina y la pagina actual
$porPagina = obtenerPorPagina('usuarios_por_pagina');
$pagina = obtenerPagina();

// Contar los usuarios activos
$sqlConteo = "SELECT COUNT(*)
              FROM usuarios u
              WHERE u.activo = 1
              AND (u.nombre LIKE :nombre OR u.correo LIKE :correo)";

$conteo = $pdo->prepare($sqlConteo);

$conteo->execute([
    'nombre' => '%' . $buscar . '%',
    'correo' => '%' . $buscar . '%'
]);

$total = (int) $conteo->fetchColumn();

// Calcular las paginas
$totalPaginas = contarPaginas($total, $porPagina);
$pagina = min($pagina, $totalPaginas);
$inicio = ($pagina - 1) * $porPagina;

// Consultar los usuarios de la pagina
$sql = "SELECT u.id, u.nombre, u.correo, r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.activo = 1
        AND (u.nombre LIKE :nombre OR u.correo LIKE :correo)
        ORDER BY u.nombre ASC, u.id ASC
        LIMIT :limite OFFSET :inicio";

$consulta = $pdo->prepare($sql);

$consulta->bindValue(':nombre', '%' . $buscar . '%', PDO::PARAM_STR);
$consulta->bindValue(':correo', '%' . $buscar . '%', PDO::PARAM_STR);
$consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);

$consulta->execute();

$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$titulo = 'Usuarios';
require __DIR__ . '/../../includes/header.php';
?>
        <h1>Usuarios</h1>
        <!-- Formulario para registrar usuarios (se abre con el boton) -->
        <?php if (tienePermiso('crear_usuario')): ?>
            <details class="agregar">
                <summary class="boton">
                <span class="abrir">+ Agregar usuario</span>
                <span class="cerrar">Cancelar y volver a la lista</span>
            </summary>

            <form class="panel" action="../actions/guardar_usuario.php" method="POST">

                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre"
                    maxlength="100" required>

                <br><br>

                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo"
                    maxlength="150" required>

                <br><br>

                <label for="contrasena">Contrasena:</label>
                <input type="password" id="contrasena" name="contrasena"
                    minlength="<?php echo CONTRASENA_MIN; ?>" maxlength="<?php echo CONTRASENA_MAX; ?>" required>

                <br><br>

                <label for="rol_id">Rol:</label>
                <select id="rol_id" name="rol_id" required>
                    <option value="">Seleccione un rol</option>

                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo (int)$rol['id']; ?>">
                            <?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <br><br>

                <button type="submit">Guardar usuario</button>

            </form>
            </details>
        <?php endif; ?>

        <!-- Buscar y cantidad por pagina -->
        <form class="barra" method="GET" action="usuarios.php">
            <input type="text" id="buscar" name="buscar"
                maxlength="150" placeholder="Buscar por nombre o correo..."
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <button type="submit">Buscar</button>
            <a class="boton boton-secundario" href="usuarios.php">Limpiar</a>

            <span class="separar"></span>

            <label for="por_pagina">Por pagina:</label>
            <select name="por_pagina" id="por_pagina" onchange="this.form.submit()">
                <?php foreach (OPCIONES_POR_PAGINA as $cantidad): ?>
                    <option value="<?php echo $cantidad; ?>"
                        <?php echo $porPagina === $cantidad ? 'selected' : ''; ?>>
                        <?php echo $cantidad; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Mostrar los usuarios -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="5">No se encontraron usuarios.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo (int) $usuario['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($usuario['correo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($usuario['rol'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            
                            <td>
                                <?php if (tienePermiso('editar_usuario')): ?>
                                    <a href="editar_usuario.php?id=<?php echo (int)$usuario['id']; ?>">
                                        Editar
                                    </a>
                                <?php endif; ?>

                                <?php if (
                                    tienePermiso('eliminar_usuario') &&
                                    (int)$usuario['id'] !== (int)$_SESSION['usuario_id']
                                ): ?>
                                    <!-- Formulario para desactivar usuarios -->
                                    <form action="../actions/eliminar_usuario.php"
                                        method="POST" style="display:inline;">

                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                                        <input type="hidden" name="id"
                                            value="<?php echo (int)$usuario['id']; ?>">

                                        <button type="submit"
                                                onclick="return confirm('Desea desactivar este usuario?');">
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

            <span>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $total; ?> usuarios)</span>
            <?php if ($pagina < $totalPaginas): ?>
                <a class="boton boton-secundario" href="?<?php echo htmlspecialchars(http_build_query([
                    'buscar' => $buscar,
                    'por_pagina' => $porPagina,
                    'pagina' => $pagina + 1
                ]), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
