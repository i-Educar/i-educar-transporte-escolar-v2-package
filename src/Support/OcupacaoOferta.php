<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

/**
 * Uma pessoa ocupa um assento por turno. Outra matrícula no mesmo turno não soma outro lugar.
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
            'id',
            'oferta_id',
            'processo_id',
            'rota_id',
            'vinculo_rota_id',
            'ref_cod_aluno',
            'situacao',
            'turno',
            'nome_aluno',
            'nascimento',
        ]);
        $vinculos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->whereColumn('ar.ano', 'r.ano')
            ->get(['ar.rota_id', 'ar.ref_cod_aluno', 'ar.turno', 'r.periodo']);

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
        $ofertaPorId = [];
        $periodoPorRota = [];

        foreach ($ofertas as $oferta) {
            $id = (int) $oferta->id;
            $uso[$id] = (object) [
                'pedidos' => 0,
                'deferidos' => 0,
                'espera' => 0,
                'ocupadas' => 0,
            ];
            $ofertaPorId[$id] = $oferta;
            $periodoPorRota[(int) $oferta->rota_id] = (string) ($oferta->periodo ?? '');
        }

        $pedidoDaPessoa = [];

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

            $pessoa = self::pessoa($pedido);
            $turno = self::turnoPedido($pedido, $ofertaPorId, $periodoPorRota);
            $rota = (int) ($pedido->vinculo_rota_id ?: $pedido->rota_id ?: 0);

            if ($pessoa !== null && $rota > 0) {
                $pedidoDaPessoa[$pessoa][$turno][$rota] = self::destino($pedido, $ofertas) ?? $ofertaId;
            }
        }

        $assento = [];

        foreach ($vinculos as $vinculo) {
            $alunoId = (int) $vinculo->ref_cod_aluno;
            $rotaId = (int) $vinculo->rota_id;

            if ($alunoId < 1 || $rotaId < 1) {
                continue;
            }

            $pessoa = 'a:' . $alunoId;
            $turno = trim((string) ($vinculo->turno ?? ''));

            if ($turno === '') {
                $turno = (string) ($vinculo->periodo ?? $periodoPorRota[$rotaId] ?? '');
            }

            if (isset($assento[$pessoa][$turno])) {
                continue;
            }

            $destino = $pedidoDaPessoa[$pessoa][$turno][$rotaId] ?? self::ofertaDaRota($rotaId, $turno, $ofertas);

            if ($destino === null || !isset($uso[$destino])) {
                continue;
            }

            $uso[$destino]->ocupadas++;
            $assento[$pessoa][$turno] = true;
        }

        foreach ($pedidos as $pedido) {
            if (!ProcessoVaga::ocupa((string) $pedido->situacao)) {
                continue;
            }

            $pessoa = self::pessoa($pedido) ?? ('u:' . spl_object_id($pedido));
            $turno = self::turnoPedido($pedido, $ofertaPorId, $periodoPorRota);

            if (isset($assento[$pessoa][$turno])) {
                continue;
            }

            $destino = self::destino($pedido, $ofertas);

            if ($destino === null || !isset($uso[$destino])) {
                continue;
            }

            $uso[$destino]->ocupadas++;
            $assento[$pessoa][$turno] = true;
        }

        return $uso;
    }

    /**
     * @param array<int, object> $ofertaPorId
     * @param array<int, string> $periodoPorRota
     */
    private static function turnoPedido(object $pedido, array $ofertaPorId, array $periodoPorRota): string
    {
        $ofertaId = (int) ($pedido->oferta_id ?? 0);

        if ($ofertaId > 0 && isset($ofertaPorId[$ofertaId])) {
            return (string) $ofertaPorId[$ofertaId]->turno;
        }

        $turno = trim((string) ($pedido->turno ?? ''));

        if ($turno !== '') {
            return $turno;
        }

        $rota = (int) ($pedido->vinculo_rota_id ?: $pedido->rota_id ?: 0);

        return (string) ($periodoPorRota[$rota] ?? '');
    }

    private static function pessoa(object $pedido): ?string
    {
        $aluno = (int) ($pedido->ref_cod_aluno ?? 0);

        if ($aluno > 0) {
            return 'a:' . $aluno;
        }

        $nome = mb_strtolower(trim((string) ($pedido->nome_aluno ?? '')));

        if ($nome !== '') {
            return 'n:' . $nome . '|' . (string) ($pedido->nascimento ?? '');
        }

        $id = (int) ($pedido->id ?? 0);

        return $id > 0 ? 'p:' . $id : null;
    }

    /**
     * @param iterable<int, object> $ofertas
     */
    private static function ofertaDaRota(int $rotaId, string $turno, iterable $ofertas): ?int
    {
        $qualquer = null;

        foreach ($ofertas as $oferta) {
            if ((int) $oferta->rota_id !== $rotaId) {
                continue;
            }

            $id = (int) $oferta->id;
            $qualquer ??= $id;

            if ((string) $oferta->turno === $turno) {
                return $id;
            }
        }

        return $qualquer;
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
