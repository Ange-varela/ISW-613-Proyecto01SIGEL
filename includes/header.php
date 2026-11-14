<?php
// Pagina actual para marcar la opcion del menu
$paginaActual = basename($_SERVER['SCRIPT_NAME']);

// Marcar el enlace de la pagina actual
function claseMenu($pagina, $paginaActual) {
    return $pagina === $paginaActual ? 'activo' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($titulo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> - SIGEL</title>
        <link rel="stylesheet" href="/css/estilos.css">
    </head>
    <body>
        <!-- Menu principal -->
        <nav class="menu">
            <a class="marca" href="/pages/dashboard.php">SIGEL</a>

            <div class="opciones">
                <?php if (tienePermiso('ver_usuarios')): ?>
                    <a class="<?php echo claseMenu('usuarios.php', $paginaActual); ?>" href="/pages/usuarios.php">Usuarios</a>
                <?php endif; ?>

                <?php if (tienePermiso('ver_categorias')): ?>
                    <a class="<?php echo claseMenu('categorias.php', $paginaActual); ?>" href="/pages/categorias.php">Categorias</a>
                <?php endif; ?>

                <?php if (tienePermiso('ver_equipos')): ?>
                    <a class="<?php echo claseMenu('equipos.php', $paginaActual); ?>" href="/pages/equipos.php">Equipos</a>
                <?php endif; ?>

                <?php if (tienePermiso('ver_usuarios')): ?>
                    <a class="<?php echo claseMenu('profesores.php', $paginaActual); ?>" href="/pages/profesores.php">Profesores</a>
                <?php endif; ?>

                <?php if (tienePermiso('ver_prestamos')): ?>
                    <a class="<?php echo claseMenu('prestamos.php', $paginaActual); ?>" href="/pages/prestamos.php">Prestamos</a>
                <?php endif; ?>

                <?php if (tienePermiso('ver_reportes')): ?>
                    <a class="<?php echo claseMenu('reportes.php', $paginaActual); ?>" href="/pages/reportes.php">Reportes</a>
                <?php endif; ?>
            </div>

            <div class="usuario">
                <span><?php echo htmlspecialchars($_SESSION['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>

                <form action="/actions/logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(crearTokenCsrf(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <button type="submit" class="boton-salir">Cerrar sesion</button>
                </form>
            </div>
        </nav>

        <main class="contenido">
