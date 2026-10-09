<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

/**
 * Uma pessoa na rota ocupa uma vaga. O pedido dela não soma de novo.
 */
final class OcupacaoOferta
{
    /**
     * @return array<int, object{pedidos: int, deferidos: int, espera: int, ocupadas: int}>
     */
    public static function porId(): array
    {
        $ofertas = DB::table('transporte_escolar.processo_vaga_oferta as o')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'o.rota_id')
            ->get(['o.id', 'o.processo_id', 'o.rota_id', 'o.turno', 'r.periodo', 'r.ano']);
        $pedidos = DB::table('transporte_escolar.solicitacao')->get([
            'oferta_id',
            'processo_id',
            'rota_id',
            'vinculo_rota_id',
            'ref_cod_aluno',
            'situacao',
        ]);
        $vinculos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->whereColumn('ar.ano', 'r.ano')
            ->get(['ar.rota_id', 'ar.ref_cod_aluno']);

        return self::montar($ofertas, $pedidos, $vinculos);
    }

    public static function ocupadas(int $ofertaId): int
    {
        $uso = self::porId();

        return (int) ($uso[$ofertaId]->ocupadas ?? 0);
    }

    /**
     * @param iterable<int, object> $ofertas
     * @param iterable<int, object> $pedidos
     * @param iterable<int, object> $vinculos
     * @return array<int, object{pedidos: int, deferidos: int, espera: int, ocupadas: int}>
     */
    public static function montar(iterable $ofertas, iterable $pedidos, iterable $vinculos): array
    {
        $uso = [];
        $ofertaDaRota = [];

        foreach ($ofertas as $oferta) {
            $id = (int) $oferta->id;
            $uso[$id] = (object) [
                'pedidos' => 0,
                'deferidos' => 0,
                'espera' => 0,
                'ocupadas' => 0,
            ];
            $chave = (int) $oferta->processo_id . ':' . (int) $oferta->rota_id;
            $combina = (string) $oferta->turno === (string) $oferta->periodo;
            $atual = $ofertaDaRota[$chave] ?? null;

            if ($atual === null || ($combina && !$atual['combina'])) {
                $ofertaDaRota[$chave] = ['id' => $id, 'combina' => $combina];
            }
        }

        $naRota = [];

        foreach ($vinculos as $vinculo) {
            $rotaId = (int) $vinculo->rota_id;
            $alunoId = (int) $vinculo->ref_cod_aluno;

            if ($alunoId > 0) {
                $naRota[$rotaId][$alunoId] = true;
            }
        }

        $assento = [];

        foreach ($pedidos as $pedido) {
            $ofertaId = (int) ($pedido->oferta_id ?? 0);

            if (isset($uso[$ofertaId])) {
                $uso[$ofertaId]->pedidos++;

                if ($pedido->situacao === SituacaoPedido::ESPERA) {
                    $uso[$ofertaId]->espera++;
                }

                if ($pedido->situacao === SituacaoPedido::DEFERIDA) {
                    $uso[$ofertaId]->deferidos++;
                }
            }

            if (!ProcessoVaga::ocupa((string) $pedido->situacao)) {
                continue;
            }

            $rota = (int) ($pedido->vinculo_rota_id ?: $pedido->rota_id ?: 0);
            $aluno = (int) ($pedido->ref_cod_aluno ?? 0);

            if ($pedido->situacao !== SituacaoPedido::CONFERIDA && $aluno > 0 && $rota > 0 && isset($naRota[$rota][$aluno])) {
                continue;
            }

            if ($aluno > 0 && $rota > 0 && isset($assento[$rota][$aluno])) {
                continue;
            }

            $destino = self::destino($pedido, $ofertas);

            if ($destino === null || !isset($uso[$destino])) {
                continue;
            }

            $uso[$destino]->ocupadas++;

            if ($aluno > 0 && $rota > 0) {
                $assento[$rota][$aluno] = true;
            }
        }

        foreach ($naRota as $rotaId => $alunos) {
            foreach (array_keys($alunos) as $alunoId) {
                if (isset($assento[$rotaId][$alunoId])) {
                    continue;
                }

                $assento[$rotaId][$alunoId] = true;

                foreach ($ofertaDaRota as $chave => $balde) {
                    $rotaDaOferta = (int) substr($chave, (int) strpos($chave, ':') + 1);

                    if ($rotaDaOferta === (int) $rotaId && isset($uso[$balde['id']])) {
                        $uso[$balde['id']]->ocupadas++;
                    }
                }
            }
        }

        return $uso;
    }

    /**
     * @param iterable<int, object> $ofertas
     */
    public static function destino(object $pedido, iterable $ofertas): ?int
    {
        if ($pedido->situacao === SituacaoPedido::CONFERIDA) {
            $rotaId = (int) ($pedido->vinculo_rota_id ?: $pedido->rota_id ?: 0);
            $processoId = (int) ($pedido->processo_id ?? 0);
            $mesmoTurno = null;
            $mesmoProcesso = null;

            foreach ($ofertas as $oferta) {
                if ((int) $oferta->rota_id !== $rotaId || (int) $oferta->processo_id !== $processoId) {
                    continue;
                }

                $mesmoProcesso ??= (int) $oferta->id;

                if ((string) $oferta->turno === (string) $oferta->periodo) {
                    $mesmoTurno ??= (int) $oferta->id;
                }
            }

            if ($mesmoTurno !== null) {
                return $mesmoTurno;
            }

            if ($mesmoProcesso !== null) {
                return $mesmoProcesso;
            }
        }

        $ofertaId = (int) ($pedido->oferta_id ?? 0);

        return $ofertaId > 0 ? $ofertaId : null;
    }
}
