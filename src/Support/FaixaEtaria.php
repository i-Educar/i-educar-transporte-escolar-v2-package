<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Faixa etária só sai quando o grupo tem alunos suficientes.
 * Com menos gente, a idade identificaria a criança.
 */
final class FaixaEtaria
{
    public const MINIMO = 3;

    /**
     * @param list<int> $idades
     * @return array<string, int>|null
     */
    public static function resumir(array $idades, int $minimo = self::MINIMO): ?array
    {
        if (count($idades) < $minimo) {
            return null;
        }

        $faixas = [
            '0 a 5 anos' => 0,
            '6 a 10 anos' => 0,
            '11 a 14 anos' => 0,
            '15 anos ou mais' => 0,
        ];

        foreach ($idades as $idade) {
            $faixas[self::rotulo((int) $idade)]++;
        }

        return array_filter($faixas, static fn (int $quantidade): bool => $quantidade > 0);
    }

    public static function rotulo(int $idade): string
    {
        if ($idade <= 5) {
            return '0 a 5 anos';
        }

        if ($idade <= 10) {
            return '6 a 10 anos';
        }

        if ($idade <= 14) {
            return '11 a 14 anos';
        }

        return '15 anos ou mais';
    }
}
