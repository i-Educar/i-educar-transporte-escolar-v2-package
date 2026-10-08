<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
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

        return view('transporte-escolar::painel', compact('cnh', 'veiculos', 'lotacao'));
    }
}
