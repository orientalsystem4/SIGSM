<?php
// Cédula uruguaya: admite 7 u 8 dígitos, con o sin puntos/guion.
function normalizarCedula(string $texto): ?string
{
    $texto = trim($texto);
    if (!preg_match('/^(?:[0-9]{7,8}|[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9])$/', $texto)) {
        return null;
    }
    $digitos = preg_replace('/\D/', '', $texto);
    $digitos = str_pad($digitos, 8, '0', STR_PAD_LEFT);
    $pesos = [2, 9, 8, 7, 6, 3, 4];
    $suma = 0;
    for ($i = 0; $i < 7; $i++) {
        $suma += (int)$digitos[$i] * $pesos[$i];
    }
    return ((10 - ($suma % 10)) % 10 === (int)$digitos[7]) ? $digitos : null;
}
function nombreCompletoValido(string $nombre): bool
{
    return mb_strlen($nombre, 'UTF-8') >= 3
        && mb_strlen($nombre, 'UTF-8') <= 150
        && (bool)preg_match("/^[\p{L}\p{M} .'-]+$/u", $nombre);
}
