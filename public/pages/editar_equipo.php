<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_equipo');

// Validar el ID del equipo
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: equipos.php');
    exit;
}

// Consultar el equipo activo
$sql = "SELECT id, codigo, nombre, descripcion, categoria_id, estado
        FROM equipos
        WHERE id = :id AND activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);

$equipo = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    http_response_code(404);
    exit('Equipo no encontrado');
}

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
?>

<?php
$titulo = 'Editar equipo';
require __DIR__ . '/../../includes/header.php';
?>

        <h1>Editar equipo</h1>

        <!-- Formulario de edicion -->
        <form action="../actions/actualizar_equipo.php" method="POST">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <input type="hidden" name="id"
                value="<?php echo (int)$equipo['id']; ?>">

            <label for="codigo">Codigo:</label>
            <input type="text" id="codigo" name="codigo"
                maxlength="50" required
                value="<?php echo htmlspecialchars($equipo['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <br><br>

            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre"
                maxlength="100" required
                value="<?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <br><br>

            <label for="descripcion">Descripcion:</label>
            <textarea id="descripcion" name="descripcion"
                    maxlength="5000"><?php echo htmlspecialchars($equipo['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>

            <br><br>

            <label for="categoria_id">Categoria:</label>
            <select id="categoria_id" name="categoria_id" required>

                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo (int)$categoria['id']; ?>"
                        <?php echo (int)$equipo['categoria_id'] === (int)$categoria['id'] ? 'selected' : ''; ?>>

                        <?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>

                    </option>
                <?php endforeach; ?>

            </select>

            <br><br>

            <p>
                Estado actual:
                <strong><?php echo htmlspecialchars($equipo['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
            </p>
            <button type="submit">Guardar cambios</button>
        </form>
        <p><a href="equipos.php">Cancelar y volver</a></p>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
