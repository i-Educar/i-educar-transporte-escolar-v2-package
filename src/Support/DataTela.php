<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class DataTela
{
    public static function dia(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '—';
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}:\d{2}))?/', $texto, $partes)) {
            return $texto;
        }

        $dia = $partes[3] . '/' . $partes[2] . '/' . $partes[1];

        return isset($partes[4]) ? $dia . ' ' . $partes[4] : $dia;
    }
}
