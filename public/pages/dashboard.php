<?php
// Cargar las funciones de autenticacion
require_once __DIR__ . '/../../includes/auth.php';

// Verificar que exista una sesion valida
verificarSesion();

// Crear el token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Preparar el nombre para mostrarlo
$nombre = htmlspecialchars(
    $_SESSION['nombre'],
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>

<?php
$titulo = 'Panel principal';
require __DIR__ . '/../../includes/header.php';
?>

        <h1>Bienvenido, <?php echo $nombre; ?></h1>
        <p>Seleccione una opcion para comenzar.</p>

        <!-- Opciones segun los permisos del usuario -->
        <div class="tarjetas">
            <?php if (tienePermiso('ver_usuarios')): ?>
                <a class="tarjeta" href="usuarios.php">
                    <strong>Usuarios</strong>
                    <span>Registrar y administrar usuarios</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('ver_categorias')): ?>
                <a class="tarjeta" href="categorias.php">
                    <strong>Categorias</strong>
                    <span>Clasificar los equipos</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('ver_equipos')): ?>
                <a class="tarjeta" href="equipos.php">
                    <strong>Equipos</strong>
                    <span>Consultar el inventario</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('ver_usuarios')): ?>
                <a class="tarjeta" href="profesores.php">
                    <strong>Profesores</strong>
                    <span>Profesores que reciben prestamos</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('ver_prestamos')): ?>
                <a class="tarjeta" href="prestamos.php">
                    <strong>Prestamos</strong>
                    <span>Gestionar las solicitudes</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('crear_prestamo')): ?>
                <a class="tarjeta" href="crear_prestamo.php">
                    <strong>Nuevo prestamo</strong>
                    <span>Registrar una solicitud</span>
                </a>
            <?php endif; ?>

            <?php if (tienePermiso('ver_reportes')): ?>
                <a class="tarjeta" href="reportes.php">
                    <strong>Reportes</strong>
                    <span>Equipos mas prestados</span>
                </a>
            <?php endif; ?>

        </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
