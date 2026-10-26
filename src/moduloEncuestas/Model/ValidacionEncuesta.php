<?php

class ValidacionEncuesta
{
    public static function validar(array $datos): array
    {
        $categoria = filter_var($datos['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        if (!$categoria || $categoria < 1 || $categoria > 7) {
            throw new DomainException('Seleccione una categoria valida.');
        }
        $respuestas = [];
        for ($id = 1; $id <= 8; $id++) {
            $valor = $datos['preg_' . $id] ?? null;
            if (!is_string($valor) || !preg_match('/^[1-5]$/D', $valor)) {
                throw new DomainException('Responda las ocho preguntas con valores del 1 al 5.');
            }
            $respuestas[$id] = $valor;
        }
        $comentario = $datos['comentario_libre'] ?? '';
        if (!is_string($comentario) || !preg_match('//u', $comentario)) {
            throw new DomainException('El comentario no es valido.');
        }
        $comentario = trim($comentario);
        if (preg_match_all('/./us', $comentario) > 500) {
            throw new DomainException('El comentario no puede superar los 500 caracteres.');
        }
        return ['categoria' => $categoria, 'respuestas' => $respuestas, 'comentario' => $comentario];
    }
}
