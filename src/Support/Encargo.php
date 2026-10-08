<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class Encargo
{
    public static function compor(float $salario, float $percentual): float
    {
        if ($salario <= 0) {
            return 0.0;
        }

        if ($percentual < 0) {
            $percentual = 0.0;
        }

        return round($salario * (1 + ($percentual / 100)), 2);
    }
}
