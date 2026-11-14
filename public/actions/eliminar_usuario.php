<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('eliminar_usuario');

// Validar que sea POST y que el token CSRF sea correcto
validarFormulario();

// Validar el ID
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

// Evitar desactivar la propia cuenta
if ($id === (int)$_SESSION['usuario_id']) {
    header('Location: ../pages/usuarios.php?error=1');
    exit;
}

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Bloquear el rol administrador
    $consultaRol = $pdo->prepare(
        "SELECT id FROM roles
         WHERE nombre = 'administrador'
         FOR UPDATE"
    );
    $consultaRol->execute();
    $adminRolId = $consultaRol->fetchColumn();

    if ($adminRolId === false) {
        throw new RuntimeException('Rol administrador no encontrado');
    }

    // Obtener los datos anteriores
    $sqlAnterior = "SELECT id, nombre, correo, rol_id, activo
                    FROM usuarios
                    WHERE id = :id AND activo = 1
                    FOR UPDATE";

    $consultaAnterior = $pdo->prepare($sqlAnterior);
    $consultaAnterior->execute(['id' => $id]);

    $anterior = $consultaAnterior->fetch(PDO::FETCH_ASSOC);

    if (!$anterior) {
        $pdo->rollBack();
        header('Location: ../pages/usuarios.php?error=1');
        exit;
    }

    // Proteger al ultimo administrador
    if ((int)$anterior['rol_id'] === (int)$adminRolId) {
        $consultaAdmin = $pdo->prepare(
            "SELECT COUNT(*) FROM usuarios
             WHERE rol_id = :rol_id AND activo = 1"
        );
        $consultaAdmin->execute(['rol_id' => $adminRolId]);

        if ((int)$consultaAdmin->fetchColumn() <= 1) {
            $pdo->rollBack();
            header('Location: ../pages/usuarios.php?error=1');
            exit;
        }
    }

    // Desactivar el usuario
    $sql = "UPDATE usuarios
            SET activo = 0
            WHERE id = :id AND activo = 1";

    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $id]);

    // Registrar la desactivacion en bitacora
    $sqlBitacora = "INSERT INTO bitacora
                    (usuario_id, accion, entidad, entidad_id,
                     valores_antes, valores_despues, ip)
                    VALUES
                    (:usuario_id, :accion, :entidad, :entidad_id,
                     :valores_antes, :valores_despues, :ip)";

    $registro = $pdo->prepare($sqlBitacora);
    $registro->execute([
        'usuario_id' => $_SESSION['usuario_id'],
        'accion' => 'ELIMINAR',
        'entidad' => 'usuarios',
        'entidad_id' => $id,
        'valores_antes' => json_encode($anterior, JSON_THROW_ON_ERROR),
        'valores_despues' => json_encode([
            'id' => $id,
            'nombre' => $anterior['nombre'],
            'correo' => $anterior['correo'],
            'rol_id' => $anterior['rol_id'],
            'activo' => 0
        ], JSON_THROW_ON_ERROR),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Confirmar los cambios
    $pdo->commit();

    header('Location: ../pages/usuarios.php?eliminado=1');
    exit;

} catch (Throwable $e) {
    // Deshacer los cambios si ocurre un error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: ../pages/usuarios.php?error=1');
    exit;
}
