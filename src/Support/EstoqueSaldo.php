<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Entrada soma, baixa tira e contagem troca o saldo pelo número contado.
 * Saldo negativo não grava.
 */
final class EstoqueSaldo
{
    public const TIPOS = [
        'pneu' => 'Pneu',
        'filtro' => 'Filtro',
        'oleo' => 'Óleo',
        'pastilha' => 'Pastilha',
    ];

    public static function aplicar(float $saldo, string $movimento, float $quantidade): ?float
    {
        if ($quantidade < 0 || !in_array($movimento, ['entrada', 'baixa', 'contagem'], true)) {
            return null;
        }

        if ($movimento === 'entrada') {
            if ($quantidade <= 0) {
                return null;
            }

            return round($saldo + $quantidade, 3);
        }

        if ($movimento === 'baixa') {
            if ($quantidade <= 0) {
                return null;
            }

            $novo = round($saldo - $quantidade, 3);

            return $novo < 0 ? null : $novo;
        }

        return round($quantidade, 3);
    }

    public static function diferenca(float $contado, float $sistema): float
    {
        return round($contado - $sistema, 3);
    }
}
