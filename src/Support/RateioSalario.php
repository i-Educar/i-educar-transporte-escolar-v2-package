<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class RateioSalario
{
    public static function porKm(float $salario, float $kmParte, float $kmTotal): float
    {
        if ($salario <= 0 || $kmParte <= 0 || $kmTotal <= 0) {
            return 0.0;
        }

        return round($salario * ($kmParte / $kmTotal), 2);
    }

    public static function porAlunos(float $salario, int $alunosParte, int $alunosTotal): float
    {
        if ($salario <= 0 || $alunosParte <= 0 || $alunosTotal <= 0) {
            return 0.0;
        }

        return round($salario * ($alunosParte / $alunosTotal), 2);
    }
}
