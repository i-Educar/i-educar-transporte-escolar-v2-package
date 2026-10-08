<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A edição não apaga pedido da família nem pedido já aceito.
 * Sem pedido, os dados da vaga podem mudar. Com pedido, a identidade fica.
 */
final class ProcessoEdicao
{
    public static function processo(int $anoAtual, int $anoNovo, int $pedidos): ?string
    {
        if ($pedidos > 0 && $anoAtual !== $anoNovo) {
            return 'O ano não muda porque já existe pedido da família neste processo.';
        }

        return null;
    }

    /**
     * @param array{processo_id: int, rota_id: int, escola: int, turno: string, vagas: int, idade_min: ?int, idade_max: ?int, series: list<int>, turmas: list<int>} $atual
     * @param array{processo_id: int, rota_id: int, escola: int, turno: string, vagas: int, idade_min: ?int, idade_max: ?int, series: list<int>, turmas: list<int>} $novo
     */
    public static function oferta(array $atual, array $novo, int $pedidos, int $deferidos, int $ocupadas): ?string
    {
        if ($novo['vagas'] < $ocupadas) {
            return 'As vagas não podem ficar abaixo dos pedidos que ainda ocupam lugar.';
        }

        $identidade = $atual['processo_id'] !== $novo['processo_id']
            || $atual['rota_id'] !== $novo['rota_id']
            || $atual['escola'] !== $novo['escola']
            || $atual['turno'] !== $novo['turno'];

        if ($pedidos > 0 && $identidade) {
            return 'Escola, rota e turno não mudam porque já existe pedido nesta vaga.';
        }

        $regra = $atual['idade_min'] !== $novo['idade_min']
            || $atual['idade_max'] !== $novo['idade_max']
            || self::lista($atual['series']) !== self::lista($novo['series'])
            || self::lista($atual['turmas']) !== self::lista($novo['turmas']);

        if ($deferidos > 0 && $regra) {
            return 'Idade, série e turma não mudam porque já existe pedido aceito nesta vaga.';
        }

        return null;
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private static function lista(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        return $ids;
    }
}
