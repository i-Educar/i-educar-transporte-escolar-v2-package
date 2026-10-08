<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class KmRealizado
{
    public static function deOdometro(?float $inicial, ?float $final): ?float
    {
        if ($inicial === null || $final === null || $final < $inicial) {
            return null;
        }

        return round($final - $inicial, 1);
    }
}
