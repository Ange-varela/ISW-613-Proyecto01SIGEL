<?php
// Leer la cookie de equipos consultados y devolver solo ids validos
function obtenerRecientes() {
    $recientes = [];

    if (isset($_COOKIE['equipos_recientes'])) {
        $datos = json_decode($_COOKIE['equipos_recientes'], true);

        if (is_array($datos)) {
            foreach ($datos as $id) {
                if (is_int($id) && $id > 0) {
                    $recientes[] = $id;
                }
            }
        }
    }

    // Conservar solo cinco identificadores
    return array_slice(array_unique($recientes), 0, 5);
}