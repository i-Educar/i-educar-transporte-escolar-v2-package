<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CacsController
{
    public function index(): View
    {
        $ano = (int) date('Y');
        $rurais = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->where('ar.ano', $ano)
            ->where('r.tipo_rota', 'R')
            ->distinct()
            ->count('ar.ref_cod_aluno');
        $frota = DB::table('transporte_escolar.veiculo')->where('ativo', true)->count();
        $vencidos = DB::table('transporte_escolar.veiculo')
            ->whereNotNull('licenciamento_vencimento')
            ->where('licenciamento_vencimento', '<', now()->toDateString())
            ->count();
        $cnh = DB::table('transporte_escolar.motorista')
            ->whereNotNull('vencimento_cnh')
            ->where('vencimento_cnh', '<', now()->toDateString())
            ->count();

        return view('transporte-escolar::cacs', [
            'ano' => $ano,
            'rurais' => $rurais,
            'frota' => $frota,
            'vencidos' => $vencidos,
            'cnh' => $cnh,
            'custo' => CustoMensal::fechar($ano, (int) date('n')),
        ]);
    }
}
