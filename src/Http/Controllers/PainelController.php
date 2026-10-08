<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PainelController
{
    public function index(): View
    {
        $limite = now()->addDays(30)->toDateString();
        $rotas = RedeConsulta::rotas()->pluck('r.id');

        $cnh = DB::table('transporte_escolar.motorista as m')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->whereNotNull('m.vencimento_cnh')
            ->where('m.vencimento_cnh', '<=', $limite)
            ->orderBy('m.vencimento_cnh')
            ->get(['p.nome', 'm.vencimento_cnh']);

        $veiculos = DB::table('transporte_escolar.veiculo')
            ->where(function ($q) use ($limite) {
                foreach (['licenciamento_vencimento', 'seguro_vencimento', 'ipva_vencimento', 'vistoria_vencimento'] as $coluna) {
                    $q->orWhere(function ($inner) use ($coluna, $limite) {
                        $inner->whereNotNull($coluna)->where($coluna, '<=', $limite);
                    });
                }
            })
            ->orderBy('placa')
            ->get();

        $lotacao = DB::table('transporte_escolar.rota as r')
            ->join('transporte_escolar.itinerario as i', 'i.rota_id', '=', 'r.id')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->leftJoin('transporte_escolar.aluno_rota as ar', 'ar.rota_id', '=', 'r.id')
            ->when($rotas->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('r.id', $rotas))
            ->groupBy('r.id', 'r.descricao', 'r.ano', 'v.id', 'v.placa', 'v.passageiros')
            ->havingRaw('count(distinct ar.ref_cod_aluno) > v.passageiros')
            ->get([
                'r.descricao',
                'r.ano',
                'v.placa',
                'v.passageiros',
                DB::raw('count(distinct ar.ref_cod_aluno) as alunos'),
            ]);

        $avisos = $this->avisos($cnh, $veiculos, $lotacao);

        return view('transporte-escolar::painel', compact('avisos'));
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $cnh
     * @param \Illuminate\Support\Collection<int, object> $veiculos
     * @param \Illuminate\Support\Collection<int, object> $lotacao
     * @return list<array{icone: string, texto: string, url: string, chave: string, tom: string}>
     */
    private function avisos($cnh, $veiculos, $lotacao): array
    {
        $avisos = [];

        foreach ($cnh as $item) {
            $avisos[] = [
                'icone' => 'fa-id-card',
                'texto' => 'CNH de ' . $item->nome . ' vence em ' . Carbon::parse($item->vencimento_cnh)->format('d/m/Y') . '.',
                'url' => '/transporte-escolar/motoristas',
                'chave' => 'frota',
                'tom' => 'prazo',
            ];
        }

        foreach ($veiculos as $veiculo) {
            $avisos[] = [
                'icone' => 'fa-bus',
                'texto' => 'Veículo ' . ($veiculo->placa ?: $veiculo->descricao) . ' com documento a vencer em 30 dias.',
                'url' => '/transporte-escolar/veiculos',
                'chave' => 'frota',
                'tom' => 'prazo',
            ];
        }

        foreach ($lotacao as $item) {
            $avisos[] = [
                'icone' => 'fa-users',
                'texto' => $item->descricao . ' (' . $item->ano . '): ' . $item->alunos . ' alunos para ' . $item->passageiros . ' lugares.',
                'url' => '/transporte-escolar/alunos?ano=' . $item->ano,
                'chave' => 'alunos',
                'tom' => 'lotacao',
            ];
        }

        $escolas = VisaoRede::escolas();
        $pedidos = DB::table('transporte_escolar.solicitacao')->whereIn('situacao', ['recebida', 'em_analise']);

        if ($escolas === []) {
            $pedidos->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $pedidos->whereIn('ref_cod_escola', $escolas);
        }

        $abertos = (int) $pedidos->count();

        if ($abertos > 0) {
            $avisos[] = [
                'icone' => 'fa-inbox',
                'texto' => $abertos === 1 ? '1 pedido da família aguarda análise.' : $abertos . ' pedidos da família aguardam análise.',
                'url' => '/transporte-escolar/solicitacoes',
                'chave' => 'solicitacoes',
                'tom' => 'pedido',
            ];
        }

        $ordens = (int) DB::table('transporte_escolar.ordem_servico')
            ->whereIn('situacao', ['solicitada', 'autorizada', 'em_execucao'])
            ->count();

        if ($ordens > 0) {
            $avisos[] = [
                'icone' => 'fa-wrench',
                'texto' => $ordens === 1 ? '1 ordem de serviço ainda não foi concluída.' : $ordens . ' ordens de serviço ainda não foram concluídas.',
                'url' => '/transporte-escolar/ordens',
                'chave' => 'gastos',
                'tom' => 'servico',
            ];
        }

        $bilhetes = (int) DB::table('transporte_escolar.abastecimento')
            ->whereIn('situacao', ['solicitado', 'autorizado'])
            ->count();

        if ($bilhetes > 0) {
            $avisos[] = [
                'icone' => 'fa-tint',
                'texto' => $bilhetes === 1 ? '1 bilhete de abastecimento aguarda confirmação.' : $bilhetes . ' bilhetes de abastecimento aguardam confirmação.',
                'url' => '/transporte-escolar/bilhetes',
                'chave' => 'gastos',
                'tom' => 'servico',
            ];
        }

        return $avisos;
    }
}
