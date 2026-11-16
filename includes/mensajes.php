<?php
// Mensajes de exito, se muestran cuando la URL trae ese dato con valor 1
const MENSAJES_EXITO = [
    'guardado' => 'El registro se guardo correctamente.',
    'actualizado' => 'Los cambios se guardaron correctamente.',
    'eliminado' => 'El registro se elimino correctamente.',
    'entregado' => 'La entrega se registro correctamente.'
];

// Mensajes de error, se muestran segun el valor de error en la URL
const MENSAJES_ERROR = [
    '1' => 'No se pudo completar la operacion. Verifique los datos.',
    'datos' => 'Seleccione un profesor y al menos un equipo.',
    'fechas' => 'Revise las fechas: la fecha de fin debe ser posterior a la fecha de inicio.',
    'registro' => 'No se pudo registrar el prestamo. Intente de nuevo.',
    'entrega' => 'No se pudo registrar la entrega. Verifique el codigo de retiro.'
];

// Guardar un mensaje de error especifico para mostrarlo en la siguiente pagina
function guardarMensaje($texto) {
    $_SESSION['mensaje_error'] = $texto;
}

// Saber si un error fue porque el dato ya existe (dato repetido)
function esDatoRepetido($error) {
    return $error instanceof PDOException &&
           ($error->errorInfo[1] ?? 0) === 1062;
}

// Mostrar los mensajes que correspondan segun la URL
function mostrarMensajes() {
    // Si hay un mensaje especifico guardado, mostrarlo y borrarlo
    if (!empty($_SESSION['mensaje_error'])) {
        echo '<p class="mensaje error">' . htmlspecialchars($_SESSION['mensaje_error'], ENT_QUOTES, 'UTF-8') . '</p>';
        unset($_SESSION['mensaje_error']);
        return;
    }
    foreach (MENSAJES_EXITO as $clave => $texto) {
        if (($_GET[$clave] ?? '') === '1') {
            echo '<p class="mensaje exito">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    $error = $_GET['error'] ?? '';

    if (is_string($error) && isset(MENSAJES_ERROR[$error])) {
        echo '<p class="mensaje error">' . htmlspecialchars(MENSAJES_ERROR[$error], ENT_QUOTES, 'UTF-8') . '</p>';
    }

    if (($_GET['error_entrega'] ?? '') === '1') {
        echo '<p class="mensaje error">' . htmlspecialchars(MENSAJES_ERROR['entrega'], ENT_QUOTES, 'UTF-8') . '</p>';
    }
}