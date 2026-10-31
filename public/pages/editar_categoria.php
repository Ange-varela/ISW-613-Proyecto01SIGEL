
<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_categoria');

// Validar el ID recibido
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: categorias.php');
    exit;
}

// Buscar la categoria activa
$sql = "SELECT id, nombre, descripcion
        FROM categorias
        WHERE id = :id AND activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);

$categoria = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    http_response_code(404);
    exit('Categoria no encontrada');
}

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
    <title>Editar categoria - SIGEL</title>
</head>
<body>

    <h1>Editar categoria</h1>

    <!-- Formulario de edicion -->
    <form action="../actions/actualizar_categoria.php" method="POST">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

        <input type="hidden" name="id"
               value="<?php echo (int)$categoria['id']; ?>">

        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre"
               maxlength="100" required
               value="<?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

        <br><br>

        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"
                  maxlength="255"><?php echo htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>

        <br><br>

        <button type="submit">Guardar cambios</button>

    </form>

    <p><a href="categorias.php">Cancelar y volver</a></p>

</body>
</html>
