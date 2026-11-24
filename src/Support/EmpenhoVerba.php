<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Agrupa gastos por empenho/verba (TR 5.4.1 / 5.4.12).
 */
final class EmpenhoVerba
{
    /**
     * @param list<array{empenho: string, verba: string, fornecedor: string, valor: float|int, veiculo?: string}> $gastos
     * @return array{por_verba: array<string, array{total: float, fornecedores: list<string>, empenhos: list<string>}>, total_geral: float}
     */
    public static function agrupar(array $gastos): array
    {
        $por = [];
        $total = 0.0;
        foreach ($gastos as $g) {
            $verba = $g['verba'];
            if (! isset($por[$verba])) {
                $por[$verba] = ['total' => 0.0, 'fornecedores' => [], 'empenhos' => []];
            }
            $valor = (float) $g['valor'];
            $por[$verba]['total'] += $valor;
            $total += $valor;
            $por[$verba]['fornecedores'][] = $g['fornecedor'];
            $por[$verba]['empenhos'][] = $g['empenho'];
        }
        foreach ($por as $k => $v) {
            $por[$k]['fornecedores'] = array_values(array_unique($v['fornecedores']));
            $por[$k]['empenhos'] = array_values(array_unique($v['empenhos']));
            $por[$k]['total'] = round($v['total'], 2);
        }

        return ['por_verba' => $por, 'total_geral' => round($total, 2)];
    }
}
