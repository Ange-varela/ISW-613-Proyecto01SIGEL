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
        <p>Estas son las opciones que tiene disponibles. Use el menu de arriba para moverse.</p>

        <!-- Opciones segun los permisos del usuario -->
        <div class="tarjetas">
            <?php if (tienePermiso('ver_usuarios')): ?>
                <div class="tarjeta">
                    <strong>Usuarios</strong>
                    <span>Registrar y administrar usuarios</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_categorias')): ?>
                <div class="tarjeta">
                    <strong>Categorias</strong>
                    <span>Clasificar los equipos</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_equipos')): ?>
                <div class="tarjeta">
                    <strong>Equipos</strong>
                    <span>Consultar el inventario</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_usuarios')): ?>
                <div class="tarjeta">
                    <strong>Profesores</strong>
                    <span>Profesores que reciben prestamos</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_prestamos')): ?>
                <div class="tarjeta">
                    <strong>Prestamos</strong>
                    <span>Gestionar las solicitudes</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('crear_prestamo')): ?>
                <div class="tarjeta">
                    <strong>Nuevo prestamo</strong>
                    <span>Registrar una solicitud</span>
                </div>
            <?php endif; ?>

            <?php if (tienePermiso('ver_reportes')): ?>
                <div class="tarjeta">
                    <strong>Reportes</strong>
                    <span>Equipos mas prestados</span>
                </div>
            <?php endif; ?>

        </div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
