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

// Definir las opciones de paginacion
$opciones = [5, 10, 20];

// Obtener la cantidad por pagina
$porPagina = filter_var(
    $_GET['por_pagina'] ?? $_COOKIE['usuarios_por_pagina'] ?? 10,
    FILTER_VALIDATE_INT
);

// Usar 10 registros si no hay una preferencia valida
if (!in_array($porPagina, $opciones, true)) {
    $porPagina = 10;
}

// Guardar la preferencia
setcookie('usuarios_por_pagina', (string)$porPagina, [
    'expires' => time() + 60 * 60 * 24 * 30,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);

// Validar la pagina actual
$pagina = filter_var(
    $_GET['pagina'] ?? 1,
    FILTER_VALIDATE_INT
);

if ($pagina === false || $pagina < 1) {
    $pagina = 1;
}

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
$totalPaginas = max(1, (int) ceil($total / $porPagina));
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

<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Usuarios - SIGEL</title>
    </head>
    <body>

        <h1>Gestion de usuarios</h1>
        
        <!-- Mostrar mensajes del sistema -->
        <?php if (($_GET['guardado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Usuario registrado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['actualizado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Usuario actualizado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['eliminado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Usuario desactivado correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <p style="color: red;">
                No se pudo completar la operacion. Verifique los datos.
            </p>
        <?php endif; ?>
 
        
        <h2>Registrar usuario</h2>

        <!-- Formulario para registrar usuarios -->
        <?php if (tienePermiso('crear_usuario')): ?>
            <form action="../actions/guardar_usuario.php" method="POST">

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
                    minlength="8" maxlength="72" required>

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
        <?php endif; ?>
        <hr>
        <h2>Lista de usuarios</h2>

        <!-- Buscar usuarios -->
        <form method="GET" action="usuarios.php">
            <label for="buscar">Buscar usuario:</label>

            <input type="text" id="buscar" name="buscar"
                maxlength="150"
                value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
            <input type="hidden" name="por_pagina"
                value="<?php echo $porPagina; ?>">
            <button type="submit">Buscar</button>

            <a href="usuarios.php">Limpiar</a>
        </form>

        <br>

        <!-- Seleccionar registros por pagina -->
        <form method="GET" action="usuarios.php">
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

        <!-- Mostrar los usuarios -->
        <table border="1" cellpadding="8">
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
