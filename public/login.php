<?php
// Iniciar la sesion
require_once __DIR__ . '/../includes/auth.php';

// Si ya tiene sesion o una cookie de recordarme valida, ir al menu
if (isset($_SESSION['usuario_id']) || recuperarSesion()) {
    header('Location: /pages/dashboard.php');
    exit;
}

// Crear un token de seguridad
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar sesion - SIGEL</title>
    </head>
    <body>

        <h1>SIGEL</h1>
        <h2>Iniciar sesion</h2>

        <?php if (isset($_GET['error'])): ?>
            <p>Credenciales invalidas o acceso no permitido.</p>
        <?php endif; ?>

        <!-- Formulario de inicio de sesion -->
        <form action="actions/procesar_login.php" method="POST">

            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="correo">Correo electronico:</label>
            <input type="email" id="correo" name="correo" required>
            <br><br>
            <label for="contrasena">Contrasena:</label>
            <input type="password" id="contrasena" name="contrasena" required>
            <br><br>
            <!-- Mantener la sesion iniciada -->
            <p>
                <label>
                    <input type="checkbox" name="recordarme" value="1">
                    Recordarme
                </label>
            </p>

            <button type="submit">Ingresar</button>
        </form>
    </body>
</html>
