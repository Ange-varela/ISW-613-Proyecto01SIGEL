<?php
// Cargar la conexion y autenticacion
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar el permiso
verificarPermiso('editar_equipo');

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo no permitido');
}

// Validar el token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no valida');
}

// Validar el ID del equipo
$equipoId = filter_var(
    $_POST['equipo_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$equipoId || $equipoId < 1) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

// Validar los archivos recibidos
$imagenes = $_FILES['imagenes'] ?? null;

if (
    !is_array($imagenes) ||
    !isset($imagenes['name'], $imagenes['tmp_name'],
           $imagenes['error'], $imagenes['size']) ||
    !is_array($imagenes['name']) ||
    !is_array($imagenes['tmp_name']) ||
    !is_array($imagenes['error']) ||
    !is_array($imagenes['size'])
) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

// Permitir entre 1 y 5 imagenes por solicitud
$cantidad = count($imagenes['name']);

if (
    $cantidad < 1 ||
    $cantidad > 5 ||
    count($imagenes['tmp_name']) !== $cantidad ||
    count($imagenes['error']) !== $cantidad ||
    count($imagenes['size']) !== $cantidad
) {
    header('Location: ../pages/equipos.php?error=1');
    exit;
}

// Definir el almacenamiento privado
$carpeta = __DIR__ . '/../../storage/equipos/';

// Definir los formatos permitidos
$formatos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

$archivosGuardados = [];

try {
    // Iniciar la transaccion
    $pdo->beginTransaction();

    // Verificar que el equipo exista
    $consultaEquipo = $pdo->prepare(
        "SELECT id FROM equipos
         WHERE id = :id AND activo = 1
         FOR UPDATE"
    );

    $consultaEquipo->execute(['id' => $equipoId]);

    if (!$consultaEquipo->fetchColumn()) {
        throw new RuntimeException('Equipo no encontrado');
    }

    // Comprobar la carpeta privada
    if (!is_dir($carpeta) || !is_writable($carpeta)) {
        throw new RuntimeException('Carpeta no disponible');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Procesar cada imagen
    for ($i = 0; $i < $cantidad; $i++) {

        if (
            !is_int($imagenes['error'][$i]) ||
            $imagenes['error'][$i] !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException('Error al subir imagen');
        }

        $temporal = $imagenes['tmp_name'][$i];
        $tamano = $imagenes['size'][$i];

        // Validar el tamano y archivo temporal
        if (
            !is_string($temporal) ||
            !is_int($tamano) ||
            $tamano < 1 ||
            $tamano > 5 * 1024 * 1024 ||
            !is_uploaded_file($temporal)
        ) {
            throw new RuntimeException('Archivo no valido');
        }

        // Comprobar el contenido de la imagen
        $tipo = $finfo->file($temporal);
        $datosImagen = @getimagesize($temporal);

        if (
            !isset($formatos[$tipo]) ||
            $datosImagen === false ||
            $datosImagen[0] < 1 ||
            $datosImagen[1] < 1 ||
            $datosImagen[0] > 8000 ||
            $datosImagen[1] > 8000 ||
            $datosImagen['mime'] !== $tipo
        ) {
            throw new RuntimeException('Formato no permitido');
        }

        // Crear un nombre aleatorio
        $nombreArchivo = bin2hex(random_bytes(16));
        $nombreArchivo .= '.' . $formatos[$tipo];

        $destino = $carpeta . $nombreArchivo;

        // Guardar el archivo
        if (!move_uploaded_file($temporal, $destino)) {
            throw new RuntimeException('No se pudo guardar imagen');
        }

        $archivosGuardados[] = $destino;

        // Registrar la imagen en MySQL
        $sql = "INSERT INTO equipo_imagenes
                (equipo_id, nombre_archivo, tipo_mime)
                VALUES (:equipo_id, :nombre_archivo, :tipo_mime)";

        $consulta = $pdo->prepare($sql);

        $consulta->execute([
            'equipo_id' => $equipoId,
            'nombre_archivo' => $nombreArchivo,
            'tipo_mime' => $tipo
        ]);
    }

    // Confirmar los registros
    $pdo->commit();

    header(
        'Location: ../pages/imagenes_equipo.php?id=' .
        $equipoId . '&guardado=1'
    );
    exit;

} catch (Throwable $e) {
    // Deshacer los registros
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Eliminar archivos si ocurre un error
    foreach ($archivosGuardados as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }

    error_log($e->getMessage());

    header(
        'Location: ../pages/imagenes_equipo.php?id=' .
        $equipoId . '&error=1'
    );
    exit;
}
