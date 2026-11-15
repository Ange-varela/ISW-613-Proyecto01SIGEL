<?php
// Reglas del sistema en un solo lugar
const CONTRASENA_MIN = 8;
const CONTRASENA_MAX = 72;
const CORREO_MAX = 150;
const MAX_INTENTOS = 3;

// Validar un texto obligatorio con un largo maximo
function textoValido($texto, $maximo) {
    return trim($texto) !== '' &&
           mb_strlen(trim($texto)) <= $maximo;
}

// Validar el formato y el largo del correo
function correoValido($correo) {
    return strlen($correo) <= CORREO_MAX &&
           filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar el largo de la contrasena
function contrasenaValida($contrasena) {
    return strlen($contrasena) >= CONTRASENA_MIN &&
           strlen($contrasena) <= CONTRASENA_MAX;
}

// Validar una fecha con formato AAAA-MM-DD
function fechaValida($fecha) {
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $valor && $valor->format('Y-m-d') === $fecha;
}