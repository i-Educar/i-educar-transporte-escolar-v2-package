<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Contas dos indicadores. Lista vazia ou denominador zero não inventa percentual.
 */
final class IndicadorRede
{
    public static function percentual(int $parte, int $todo): ?int
    {
        if ($todo <= 0 || $parte < 0) {
            return null;
        }

        return (int) round(min($parte, $todo) / $todo * 100);
    }

    public static function litrosPorCem(float $litros, float $km): ?float
    {
        if ($litros <= 0 || $km <= 0) {
            return null;
        }

        return round($litros / ($km / 100), 2);
    }

    public static function ocorrenciasPorCemMil(int $ocorrencias, float $km): ?float
    {
        if ($ocorrencias < 0 || $km <= 0) {
            return null;
        }

        return round($ocorrencias * 100000 / $km, 2);
    }

    public static function custoPorAluno(float $custo, int $alunos): ?float
    {
        if ($custo < 0 || $alunos <= 0) {
            return null;
        }

        return round($custo / $alunos, 2);
    }

    /**
     * Largura de 0 a 100 para a barra. O teto é o maior valor da série.
     */
    public static function faixa(float $valor, float $teto): int
    {
        if ($valor <= 0 || $teto <= 0) {
            return 0;
        }

        return (int) max(0, min(100, round($valor / $teto * 100)));
    }

    /**
     * O plano está em dia quando o km atual não passou do intervalo
     * e a data de hoje não passou dos dias combinados. Sem os dois limites, não há plano.
     */
    public static function planoEmDia(?float $ultimoKm, ?int $aCadaKm, ?string $ultimaData, ?int $aCadaDias, float $kmAtual, string $hoje): ?bool
    {
        $temKm = $aCadaKm !== null && $aCadaKm > 0 && $ultimoKm !== null;
        $temData = $aCadaDias !== null && $aCadaDias > 0 && $ultimaData !== null && $ultimaData !== '';

        if (!$temKm && !$temData) {
            return null;
        }

        if ($temKm && $kmAtual > $ultimoKm + $aCadaKm) {
            return false;
        }

        if ($temData) {
            $limite = \DateTimeImmutable::createFromFormat('!Y-m-d', $ultimaData);

            if ($limite === false) {
                return false;
            }

            $limite = $limite->modify('+' . $aCadaDias . ' days');
            $dia = \DateTimeImmutable::createFromFormat('!Y-m-d', $hoje);

            if ($dia === false || $dia > $limite) {
                return false;
            }
        }

        return true;
    }
}
