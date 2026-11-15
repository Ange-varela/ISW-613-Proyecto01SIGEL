<?php
// Cantidades de registros que se pueden elegir por pagina
const OPCIONES_POR_PAGINA = [5, 10, 20];
const POR_PAGINA_DEFECTO = 10;

// Obtener cuantos registros mostrar y guardarlo en una cookie
function obtenerPorPagina($nombreCookie) {
    // Usar lo que eligio en la lista o, si no, lo que tenia guardado
    $porPagina = filter_var(
        $_GET['por_pagina'] ?? $_COOKIE[$nombreCookie] ?? POR_PAGINA_DEFECTO,
        FILTER_VALIDATE_INT
    );

    // Si no es una de las opciones permitidas, usar 10
    if (!in_array($porPagina, OPCIONES_POR_PAGINA, true)) {
        $porPagina = POR_PAGINA_DEFECTO;
    }

    // Guardar la preferencia por 30 dias
    setcookie($nombreCookie, (string)$porPagina, [
        'expires' => time() + 30 * 24 * 60 * 60,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    return $porPagina;
}

// Obtener el numero de pagina que se quiere ver
function obtenerPagina() {
    $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);

    if ($pagina === false || $pagina < 1) {
        $pagina = 1;
    }

    return $pagina;
}

// Calcular cuantas paginas hay en total
function contarPaginas($total, $porPagina) {
    return max(1, (int)ceil($total / $porPagina));
}