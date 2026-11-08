<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_usuario');

// Validar el ID recibido
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: usuarios.php');
    exit;
}

// Consultar el usuario activo
$sql = "SELECT id, nombre, correo, rol_id
        FROM usuarios
        WHERE id = :id AND activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404);
    exit('Usuario no encontrado');
}

// Consultar los roles
$sqlRoles = "SELECT id, nombre
             FROM roles
             ORDER BY nombre ASC";

$consultaRoles = $pdo->prepare($sqlRoles);
$consultaRoles->execute();

$roles = $consultaRoles->fetchAll(PDO::FETCH_ASSOC);

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Editar usuario - SIGEL</title>
    </head>
    <body>

        <h1>Editar usuario</h1>

        <!-- Formulario de edicion -->
        <form action="../actions/actualizar_usuario.php" method="POST">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <input type="hidden" name="id"
                value="<?php echo (int)$usuario['id']; ?>">

            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre"
                maxlength="100" required
                value="<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <br><br>

            <label for="correo">Correo:</label>
            <input type="email" id="correo" name="correo"
                maxlength="150" required
                value="<?php echo htmlspecialchars($usuario['correo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <br><br>

            <label for="rol_id">Rol:</label>
            <select id="rol_id" name="rol_id" required>

                <?php foreach ($roles as $rol): ?>
                    <option value="<?php echo (int)$rol['id']; ?>"
                        <?php echo (int)$usuario['rol_id'] === (int)$rol['id'] ? 'selected' : ''; ?>>

                        <?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>

                    </option>
                <?php endforeach; ?>

            </select>
            <br><br>
            <button type="submit">Guardar cambios</button>
        </form>
        <p><a href="usuarios.php">Cancelar y volver</a></p>
    </body>
</html>
