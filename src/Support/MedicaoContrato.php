<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class MedicaoContrato
{
    public static function valor(float $mensal, int $diasComViagem, int $diasUteis): float
    {
        if ($mensal <= 0 || $diasComViagem <= 0 || $diasUteis <= 0) {
            return 0.0;
        }

        $fator = min(1, $diasComViagem / $diasUteis);

        return round($mensal * $fator, 2);
    }
}
