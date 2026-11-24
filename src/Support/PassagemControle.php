<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Controle de recebimento/distribuição de passagens (TR 5.4.10 / 5.4.18 / 5.4.19).
 * Regras puras — persistência entra na UI de estoque/gastos na PoC.
 */
final class PassagemControle
{
    /**
     * @param list<array{tipo: string, quantidade: int, data?: string, rede?: string, sentido?: string}> $movimentos
     * @return array{saldo_por_tipo: array<string, int>, total_recebido: int, total_entregue: int, por_rede: array<string, int>}
     */
    public static function consolidar(array $movimentos): array
    {
        $saldo = [];
        $recebido = 0;
        $entregue = 0;
        $porRede = [];

        foreach ($movimentos as $m) {
            $tipo = $m['tipo'];
            $q = (int) $m['quantidade'];
            $sentido = $m['sentido'] ?? 'recebido';
            if (! isset($saldo[$tipo])) {
                $saldo[$tipo] = 0;
            }
            if ($sentido === 'recebido') {
                $saldo[$tipo] += $q;
                $recebido += $q;
            } elseif ($sentido === 'entregue') {
                $saldo[$tipo] -= $q;
                $entregue += $q;
                $rede = $m['rede'] ?? 'municipal';
                $porRede[$rede] = ($porRede[$rede] ?? 0) + $q;
            }
        }

        return [
            'saldo_por_tipo' => $saldo,
            'total_recebido' => $recebido,
            'total_entregue' => $entregue,
            'por_rede' => $porRede,
        ];
    }

    public static function saldoNegativo(array $saldoPorTipo): bool
    {
        foreach ($saldoPorTipo as $v) {
            if ($v < 0) {
                return true;
            }
        }

        return false;
    }
}
