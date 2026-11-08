
<?php
// Cargar las funciones de autenticacion
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que exista una sesion valida
verificarSesion();

// Preparar el nombre para mostrarlo
$nombre = htmlspecialchars(
    $_SESSION['nombre'],
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel principal - SIGEL</title>
    </head>
    <body>

        <h1>Panel principal de SIGEL</h1>
        <p>Bienvenido, <?php echo $nombre; ?></p>

        <h2>Menu principal</h2>

        <!-- Opciones del administrador -->
        <?php if (tienePermiso('ver_usuarios')): ?>
            <p><a href="usuarios.php">Gestionar usuarios</a></p>
        <?php endif; ?>

        <?php if (tienePermiso('ver_categorias')): ?>
            <p><a href="categorias.php">Gestionar categorias</a></p>
        <?php endif; ?>

        <!-- Consulta de equipos -->
        <?php if (tienePermiso('ver_equipos')): ?>
            <p><a href="equipos.php">Consultar equipos</a></p>
        <?php endif; ?>

        <!-- Gestion de prestamos -->
        <?php if (tienePermiso('ver_prestamos')): ?>
            <p><a href="prestamos.php">Gestionar prestamos</a></p>
        <?php endif; ?>

        <?php if (tienePermiso('crear_prestamo')): ?>
            <p>
                <a href="crear_prestamo.php">Registrar prestamo</a>
            </p>
        <?php endif; ?>

        <!-- Reportes del sistema -->
        <?php if (tienePermiso('ver_reportes')): ?>
            <p><a href="reportes.php">Consultar reportes</a></p>
        <?php endif; ?>

        <?php if (tienePermiso('ver_usuarios')): ?>
            <p>
                <a href="profesores.php">Gestion de profesores</a>
            </p>
        <?php endif; ?>

        <!-- Cerrar sesion -->
        <form action="../actions/logout.php" method="POST">
            <button type="submit">Cerrar sesion</button>
        </form>

    </body>
</html>
