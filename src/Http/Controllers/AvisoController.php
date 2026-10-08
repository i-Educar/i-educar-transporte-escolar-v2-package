<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AvisoController
{
    public function index(): View
    {
        $ids = RedeConsulta::rotas()->pluck('r.id');
        $avisos = DB::table('transporte_escolar.aviso_familia as av')
            ->join('transporte_escolar.embarque as e', 'e.id', '=', 'av.embarque_id')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'av.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->orderByDesc('av.id')
            ->limit(40)
            ->get(['av.token', 'av.texto', 'av.lido_em', 'p.nome', 'e.fora_da_cerca', 'v.data']);

        return view('transporte-escolar::avisos', ['avisos' => $avisos]);
    }

    public function publico(string $token): View
    {
        if (!\App\Support\Tenancy\PackageTenant::allows(\iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider::PACKAGE)) {
            abort(404);
        }

        $aviso = DB::table('transporte_escolar.aviso_familia')->where('token', $token)->first();
        abort_if($aviso === null, 404);

        if ($aviso->lido_em === null) {
            DB::table('transporte_escolar.aviso_familia')->where('id', $aviso->id)->update(['lido_em' => now()]);
        }

        return view('transporte-escolar::aviso-publico', ['aviso' => $aviso]);
    }
}
