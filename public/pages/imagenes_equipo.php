<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_equipo');

// Validar el ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: equipos.php');
    exit;
}

// Consultar el equipo activo
$sql = "SELECT id, codigo, nombre
        FROM equipos
        WHERE id = :id AND activo = 1";

$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);

$equipo = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    http_response_code(404);
    exit('Equipo no encontrado');
}

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Consultar las imagenes del equipo
$sqlImagenes = "SELECT id
                FROM equipo_imagenes
                WHERE equipo_id = :equipo_id
                ORDER BY id DESC";

$consultaImagenes = $pdo->prepare($sqlImagenes);
$consultaImagenes->execute(['equipo_id' => $id]);

$imagenes = $consultaImagenes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Imagenes de equipos - SIGEL</title>
    </head>
    <body>

        <h1>Imagenes del equipo</h1>

        <!-- Mostrar mensajes del sistema -->
        <?php if (($_GET['guardado'] ?? '') === '1'): ?>
            <p style="color: green;">
                Imagenes guardadas correctamente.
            </p>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <p style="color: red;">
                No se pudieron guardar las imagenes.
            </p>
        <?php endif; ?>

        <p>
            <strong>Codigo:</strong>
            <?php echo htmlspecialchars($equipo['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <p>
            <strong>Nombre:</strong>
            <?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </p>

        <h2>Subir imagenes</h2>

        <!-- Formulario para subir imagenes -->
        <form action="../actions/guardar_imagen.php"
            method="POST"
            enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

            <input type="hidden" name="equipo_id"
                value="<?php echo (int)$equipo['id']; ?>">

            <label for="imagenes">Seleccionar imagenes:</label>

            <input type="file"
                id="imagenes"
                name="imagenes[]"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                required>

            <p>Formatos permitidos: JPG, PNG y WEBP.</p>
            <p>Tamano maximo: 5 MB por imagen.</p>

            <button type="submit">Guardar imagenes</button>
        </form>

        <hr>

        <h2>Imagenes registradas</h2>

        <!-- Mostrar las fotografias del equipo -->
        <?php if (empty($imagenes)): ?>
            <p>Este equipo no tiene imagenes registradas.</p>
        <?php else: ?>
            <div>
                <?php foreach ($imagenes as $imagen): ?>
                    <img
                        src="../actions/ver_imagen.php?id=<?php echo (int)$imagen['id']; ?>"
                        alt="Fotografia del equipo"
                        width="200"
                        style="margin: 10px;"
                    >
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p><a href="equipos.php">Volver a equipos</a></p>
    </body>
</html>
