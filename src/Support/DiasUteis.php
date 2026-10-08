<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class DiasUteis
{
    public static function contar(int $ano, int $mes): int
    {
        $dias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $ano, $mes)));
        $uteis = 0;

        for ($dia = 1; $dia <= $dias; $dia++) {
            $semana = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $ano, $mes, $dia)));

            if ($semana < 6) {
                $uteis++;
            }
        }

        return $uteis;
    }
}
