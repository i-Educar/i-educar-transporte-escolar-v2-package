<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

/**
 * A vaga publicada baixa quando o pedido ocupa lugar e também quando
 * o aluno já está na rota, mesmo sem matrícula ativa no ano.
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

        $uso = [];

        foreach ($ofertas as $oferta) {
            $uso[(int) $oferta->id] = (object) [
                'pedidos' => 0,
                'deferidos' => 0,
                'espera' => 0,
                'ocupadas' => 0,
            ];
        }

        $pedidos = DB::table('transporte_escolar.solicitacao')->get([
            'oferta_id',
            'processo_id',
            'rota_id',
            'vinculo_rota_id',
            'ref_cod_aluno',
            'situacao',
        ]);
        $alunosContados = [];

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

            $destino = self::destino($pedido, $ofertas);

            if ($destino === null || !isset($uso[$destino])) {
                continue;
            }

            $uso[$destino]->ocupadas++;
            $rota = self::rotaDoAssento($pedido);
            $aluno = (int) ($pedido->ref_cod_aluno ?? 0);

            if ($rota > 0 && $aluno > 0) {
                $alunosContados[$rota][$aluno] = true;
            }
        }

        $vinculos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->whereColumn('ar.ano', 'r.ano')
            ->get(['ar.rota_id', 'ar.ref_cod_aluno']);
        $ofertaPorProcessoRota = [];

        foreach ($ofertas as $oferta) {
            $chave = (int) $oferta->processo_id . ':' . (int) $oferta->rota_id;
            $ofertaPorProcessoRota[$chave] ??= (int) $oferta->id;
        }

        foreach ($vinculos as $vinculo) {
            $rotaId = (int) $vinculo->rota_id;
            $alunoId = (int) $vinculo->ref_cod_aluno;

            if ($alunoId < 1 || isset($alunosContados[$rotaId][$alunoId])) {
                continue;
            }

            $alunosContados[$rotaId][$alunoId] = true;

            foreach ($ofertaPorProcessoRota as $chave => $ofertaId) {
                $rotaDaOferta = (int) substr($chave, (int) strpos($chave, ':') + 1);

                if ($rotaDaOferta !== $rotaId || !isset($uso[$ofertaId])) {
                    continue;
                }

                $uso[$ofertaId]->ocupadas++;
            }
        }

        return $uso;
    }

    public static function ocupadas(int $ofertaId): int
    {
        $uso = self::porId();

        return (int) ($uso[$ofertaId]->ocupadas ?? 0);
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

    private static function rotaDoAssento(object $pedido): int
    {
        if ($pedido->situacao === SituacaoPedido::CONFERIDA) {
            return (int) ($pedido->vinculo_rota_id ?: $pedido->rota_id ?: 0);
        }

        return 0;
    }
}
