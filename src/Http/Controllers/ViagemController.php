<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Services\RegistrarAviso;
use iEducar\Packages\BuritiTransporteEscolar\Services\TrilhaKm;
use iEducar\Packages\BuritiTransporteEscolar\Support\QrCodigo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ViagemController
{
    public function index(): View
    {
        $ids = RedeConsulta::rotas()->pluck('r.id');

        $viagens = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->leftJoin('transporte_escolar.veiculo as ve', 've.id', '=', 'v.veiculo_id')
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->orderByDesc('v.data')
            ->limit(40)
            ->get(['v.id', 'v.data', 'v.sentido', 'v.km_inicial', 'v.km_final', 'r.descricao', 've.placa']);

        return view('transporte-escolar::viagens.index', [
            'viagens' => $viagens,
            'rotas' => RedeConsulta::rotas()->get(),
            'veiculos' => DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(),
            'motoristas' => DB::table('transporte_escolar.motorista as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['m.id', 'p.nome']),
            'monitores' => DB::table('transporte_escolar.monitor as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['m.id', 'p.nome']),
            'sentidos' => Codigos::SENTIDO,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'rota_id' => ['required', 'integer'],
            'veiculo_id' => ['nullable', 'integer'],
            'motorista_id' => ['nullable', 'integer'],
            'monitor_id' => ['nullable', 'integer'],
            'sentido' => ['required', 'in:I,V,C'],
            'km_inicial' => ['nullable', 'numeric'],
            'km_final' => ['nullable', 'numeric'],
        ]);

        abort_if(RedeConsulta::rotas()->where('r.id', $dados['rota_id'])->doesntExist(), 404);

        if (KmRealizado::deOdometro(
            isset($dados['km_inicial']) ? (float) $dados['km_inicial'] : null,
            isset($dados['km_final']) ? (float) $dados['km_final'] : null,
        ) === null && isset($dados['km_inicial'], $dados['km_final'])) {
            return back()->with('erro', 'O km final não pode ser menor que o inicial.');
        }

        $id = DB::table('transporte_escolar.viagem')->insertGetId([
            'data' => $dados['data'],
            'rota_id' => $dados['rota_id'],
            'veiculo_id' => $dados['veiculo_id'] ?? null,
            'motorista_id' => $dados['motorista_id'] ?? null,
            'monitor_id' => $dados['monitor_id'] ?? null,
            'sentido' => $dados['sentido'],
            'km_inicial' => $dados['km_inicial'] ?? null,
            'km_final' => $dados['km_final'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/viagens/' . $id);
    }

    public function show(int $viagem): View
    {
        $registro = $this->visivel($viagem);
        $alunos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->where('ar.rota_id', $registro->rota_id)
            ->orderBy('p.nome')
            ->get(['ar.ref_cod_aluno', 'p.nome']);
        $eventos = DB::table('transporte_escolar.embarque')->where('viagem_id', $viagem)->get()->keyBy(fn ($item) => $item->ref_cod_aluno . ':' . $item->tipo);

        return view('transporte-escolar::viagens.show', [
            'viagem' => $registro,
            'alunos' => $alunos,
            'eventos' => $eventos,
            'km' => KmRealizado::deOdometro(
                $registro->km_inicial === null ? null : (float) $registro->km_inicial,
                $registro->km_final === null ? null : (float) $registro->km_final,
            ),
            'qrs' => $alunos->mapWithKeys(fn ($aluno) => [$aluno->ref_cod_aluno => QrCodigo::svg('TE-' . $aluno->ref_cod_aluno)]),
        ]);
    }

    public function evento(Request $request, int $viagem): RedirectResponse
    {
        $this->visivel($viagem);
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'tipo' => ['required', 'in:embarque,desembarque,falta'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $pertence = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.viagem as v', 'v.rota_id', '=', 'ar.rota_id')
            ->where('v.id', $viagem)
            ->where('ar.ref_cod_aluno', $dados['aluno_id'])
            ->exists();

        if (!$pertence) {
            return back()->with('erro', 'Este aluno não está na rota da viagem.');
        }

        DB::table('transporte_escolar.embarque')->updateOrInsert(
            ['viagem_id' => $viagem, 'ref_cod_aluno' => $dados['aluno_id'], 'tipo' => $dados['tipo']],
            [
                'ocorrido_em' => now(),
                'latitude' => $dados['latitude'] ?? null,
                'longitude' => $dados['longitude'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        RegistrarAviso::aposEvento(
            $viagem,
            (int) $dados['aluno_id'],
            $dados['tipo'],
            isset($dados['latitude']) ? (float) $dados['latitude'] : null,
            isset($dados['longitude']) ? (float) $dados['longitude'] : null,
        );
        TrilhaKm::atualizar($viagem);

        return back()->with('ok', 'Registro do transporte gravado. A falta na aula continua no diário da escola.');
    }

    private function visivel(int $viagem): object
    {
        $ids = RedeConsulta::rotas()->pluck('r.id');
        $registro = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('v.id', $viagem)
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->first(['v.*', 'r.descricao']);
        abort_if($registro === null, 404);

        return $registro;
    }
}
