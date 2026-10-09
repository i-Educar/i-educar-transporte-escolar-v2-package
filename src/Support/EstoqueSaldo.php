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

    /** Pneu, filtro e pastilha saem por unidade. Óleo sai em litro. */
    public const INTEIROS = ['pneu', 'filtro', 'pastilha'];

    public const FAIXAS = [
        'regular' => 'Regular',
        'abaixo' => 'Abaixo do mínimo',
        'zerado' => 'Sem saldo',
        'inativo' => 'Inativo',
    ];

    public static function unidadePadrao(string $tipo): string
    {
        return $tipo === 'oleo' ? 'L' : 'un';
    }

    public static function inteiro(string $tipo, float $quantidade): bool
    {
        if (!in_array($tipo, self::INTEIROS, true)) {
            return true;
        }

        return abs($quantidade - round($quantidade)) < 0.0005;
    }

    public static function texto(float $quantidade): string
    {
        $texto = number_format($quantidade, 3, ',', '.');

        if (!str_contains($texto, ',')) {
            return $texto;
        }

        return rtrim(rtrim($texto, '0'), ',');
    }

    public static function faixa(float $saldo, float $minimo, bool $ativo): string
    {
        if (!$ativo) {
            return 'inativo';
        }

        if ($saldo <= 0) {
            return 'zerado';
        }

        if ($minimo > 0 && $saldo < $minimo) {
            return 'abaixo';
        }

        return 'regular';
    }

    /**
     * O lote com validade mais próxima sai primeiro. Lote sem validade não entra na fila.
     *
     * @param list<array{quantidade: float, validade: ?string}> $lotes
     */
    public static function primeiraValidade(array $lotes): ?string
    {
        $datas = [];

        foreach ($lotes as $lote) {
            if (($lote['quantidade'] ?? 0) > 0 && !empty($lote['validade'])) {
                $datas[] = (string) $lote['validade'];
            }
        }

        if ($datas === []) {
            return null;
        }

        sort($datas);

        return $datas[0];
    }

    public static function recusaBaixa(?string $validade, float $saldo, ?string $primeiraValidade, string $hoje): ?string
    {
        if ($saldo <= 0) {
            return 'sem_saldo';
        }

        if ($validade !== null && $validade !== '' && $validade < $hoje) {
            return 'vencido';
        }

        if ($validade !== null && $validade !== '' && $primeiraValidade !== null && $validade > $primeiraValidade) {
            return 'fefo';
        }

        return null;
    }

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
