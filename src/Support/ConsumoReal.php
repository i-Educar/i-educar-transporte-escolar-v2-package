<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ConsumoReal
{
    public static function kmPorLitro(float $km, float $litros): ?float
    {
        if ($km <= 0 || $litros <= 0) {
            return null;
        }

        return round($km / $litros, 2);
    }

    public static function piorQueOMedio(?float $realKmPorLitro, ?float $medioKmPorLitro, float $folga = 0.2): bool
    {
        if ($realKmPorLitro === null || $medioKmPorLitro === null || $medioKmPorLitro <= 0) {
            return false;
        }

        return $realKmPorLitro < ($medioKmPorLitro * (1 - $folga));
    }
}
