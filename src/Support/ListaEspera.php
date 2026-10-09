<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pedido analisado sem vaga na rota publicada de um processo aberto.
 * Enquanto essa fila existir, a rota não recebe aluno incluído à mão.
 */
final class ListaEspera
{
    public static function pendentes(int $rotaId): int
    {
        if ($rotaId < 1) {
            return 0;
        }

        return (int) self::consulta()->where('s.rota_id', $rotaId)->count();
    }

    public static function recado(int $rotaId): ?string
    {
        $quantidade = self::pendentes($rotaId);

        if ($quantidade < 1) {
            return null;
        }

        $texto = $quantidade === 1 ? '1 aluno na lista de espera' : $quantidade . ' alunos na lista de espera';

        return 'Esta rota tem ' . $texto . ' em processo aberto. Chame ou retire essa lista antes de incluir alguém à mão.';
    }

    /**
     * @param list<int> $rotas
     * @return array<int, int>
     */
    public static function porRota(array $rotas): array
    {
        $rotas = array_values(array_unique(array_filter(array_map('intval', $rotas))));

        if ($rotas === []) {
            return [];
        }

        return self::consulta()
            ->whereIn('s.rota_id', $rotas)
            ->selectRaw('s.rota_id, count(*) as total')
            ->groupBy('s.rota_id')
            ->pluck('total', 'rota_id')
            ->map(static fn ($total): int => (int) $total)
            ->all();
    }

    private static function consulta()
    {
        return DB::table('transporte_escolar.solicitacao as s')
            ->leftJoin('transporte_escolar.processo_vaga as p', 'p.id', '=', 's.processo_id')
            ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
            ->leftJoin('transporte_escolar.processo_vaga_oferta as o', 'o.id', '=', 's.oferta_id')
            ->where('s.situacao', SituacaoPedido::ESPERA)
            ->where(function ($q) {
                $q->whereNull('p.situacao')->orWhere('p.situacao', ProcessoVaga::ABERTO);
            })
            ->where(function ($q) {
                $q->whereNull('g.situacao')->orWhere('g.situacao', ProcessoVaga::ABERTO);
            })
            ->where(function ($q) {
                $q->whereNull('o.situacao')->orWhere('o.situacao', ProcessoVaga::ABERTO);
            });
    }
}
