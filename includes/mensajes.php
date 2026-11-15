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
    'datos' => 'Revise los datos del prestamo y seleccione al menos un equipo.',
    'fechas' => 'Las fechas ingresadas no son validas.',
    'registro' => 'No se pudo registrar el prestamo. Verifique que los equipos esten disponibles para esas fechas.',
    'entrega' => 'No se pudo registrar la entrega. Verifique el codigo de retiro.'
];

// Mostrar los mensajes que correspondan segun la URL
function mostrarMensajes() {
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