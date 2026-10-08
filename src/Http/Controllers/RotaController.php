<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Gpx;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmCalculado;
use iEducar\Packages\BuritiTransporteEscolar\Support\OrdemPontos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RotaController
{
    public function index(): View
    {
        $ano = (int) request('ano', date('Y'));

        return view('transporte-escolar::rotas.index', [
            'ano' => $ano,
            'rotas' => RedeConsulta::rotas($ano)->get(),
            'escolas' => RedeConsulta::escolas()->get(),
            'podeGravar' => Acesso::permite(Processos::ROTAS, 'gravar'),
            'codigos' => Codigos::class,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'descricao' => ['required', 'string', 'max:80'],
            'tipo_rota' => ['required', 'in:U,R'],
            'periodo' => ['required', 'in:manha,tarde,noite,integral'],
            'sentido' => ['required', 'in:I,V,C'],
            'km_pav' => ['nullable', 'numeric', 'min:0'],
            'km_npav' => ['nullable', 'numeric', 'min:0'],
            'velocidade_media_kmh' => ['nullable', 'numeric', 'min:1'],
            'terceirizada' => ['nullable', 'boolean'],
            'escolas' => ['array'],
            'escolas.*' => ['integer'],
        ]);

        $id = DB::table('transporte_escolar.rota')->insertGetId([
            'ano' => $dados['ano'],
            'descricao' => $dados['descricao'],
            'tipo_rota' => $dados['tipo_rota'],
            'periodo' => $dados['periodo'],
            'sentido' => $dados['sentido'],
            'km_pav' => $dados['km_pav'] ?? null,
            'km_npav' => $dados['km_npav'] ?? null,
            'velocidade_media_kmh' => $dados['velocidade_media_kmh'] ?? 40,
            'terceirizada' => $request->boolean('terceirizada'),
            'origem' => 'ieducar',
            'origem_chave' => $dados['descricao'] . '|' . $dados['ano'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($dados['escolas'] ?? [] as $escola) {
            DB::table('transporte_escolar.rota_escola')->insert([
                'rota_id' => $id,
                'ref_cod_escola' => $escola,
            ]);
        }

        return redirect('/transporte-escolar/rotas/' . $id);
    }

    public function show(int $rota): View
    {
        $registro = $this->visivel($rota);
        $escolas = DB::table('transporte_escolar.rota_escola as re')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 're.ref_cod_escola')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
            ->where('re.rota_id', $rota)
            ->get(['e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude']);

        $itinerario = DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'i.ponto_id')
            ->leftJoin('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->where('i.rota_id', $rota)
            ->orderBy('i.seq')
            ->get(['i.seq', 'i.hora', 'i.tipo', 'p.id as ponto_id', 'p.descricao', 'p.latitude', 'p.longitude', 'v.placa', 'v.passageiros', 'v.id as veiculo_id']);

        $alunos = (int) DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota)->count();
        $tracado = DB::table('transporte_escolar.tracado')->where('rota_id', $rota)->first();
        $coordenadas = [];

        foreach ($itinerario as $parada) {
            if ($parada->latitude !== null && $parada->longitude !== null) {
                $coordenadas[] = ['latitude' => (float) $parada->latitude, 'longitude' => (float) $parada->longitude];
            }
        }

        $km = $registro->km_calculado !== null
            ? (float) $registro->km_calculado
            : KmCalculado::quilometros($coordenadas);

        return view('transporte-escolar::rotas.show', [
            'rota' => $registro,
            'escolas' => $escolas,
            'itinerario' => $itinerario,
            'alunos' => $alunos,
            'kmCalculado' => $km,
            'minutos' => KmCalculado::minutos($km, (float) $registro->velocidade_media_kmh),
            'kmInformado' => (float) $registro->km_pav + (float) $registro->km_npav,
            'pontos' => DB::table('transporte_escolar.ponto')->orderBy('descricao')->get(),
            'veiculos' => DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(),
            'podeGravar' => Acesso::permite(Processos::ROTAS, 'gravar'),
            'podeMapa' => Acesso::permite(Processos::PONTOS, 'gravar'),
            'temTracado' => $tracado !== null,
            'codigos' => Codigos::class,
        ]);
    }

    public function gpx(Request $request, int $rota): RedirectResponse
    {
        $this->visivel($rota);
        $arquivo = $request->file('gpx');

        if ($arquivo === null) {
            return back()->with('erro', 'Escolha um arquivo GPX.');
        }

        $xml = (string) file_get_contents($arquivo->getRealPath());
        $coordenadas = Gpx::coordenadas($xml);

        if ($coordenadas === []) {
            return back()->with('erro', 'O GPX não tem pontos de trilha.');
        }

        $km = KmCalculado::quilometros($coordenadas);
        DB::table('transporte_escolar.tracado')->updateOrInsert(
            ['rota_id' => $rota],
            [
                'formato' => 'gpx',
                'conteudo' => $xml,
                'km_calculado' => $km,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        DB::table('transporte_escolar.rota')->where('id', $rota)->update([
            'km_calculado' => $km,
            'tempo_estimado_min' => KmCalculado::minutos($km, (float) DB::table('transporte_escolar.rota')->where('id', $rota)->value('velocidade_media_kmh')),
        ]);

        return back()->with('ok', 'Traçado importado. Km calculado: ' . number_format($km, 2, ',', '.'));
    }

    public function ordem(int $rota): RedirectResponse
    {
        $this->visivel($rota);
        $tracado = DB::table('transporte_escolar.tracado')->where('rota_id', $rota)->value('conteudo');

        if ($tracado === null) {
            return back()->with('erro', 'Importe o GPX antes de sugerir a ordem.');
        }

        $trilha = Gpx::coordenadas((string) $tracado);
        $linhas = DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'i.ponto_id')
            ->where('i.rota_id', $rota)
            ->whereNotNull('p.latitude')
            ->get(['i.id', 'p.id as ponto_id', 'p.latitude', 'p.longitude']);

        $ordem = array_flip(OrdemPontos::sugerir($trilha, $linhas->map(static fn ($ponto): array => [
            'id' => (int) $ponto->ponto_id,
            'latitude' => (float) $ponto->latitude,
            'longitude' => (float) $ponto->longitude,
        ])->unique('id')->values()->all()));

        $linhas = $linhas->sortBy(static fn ($linha): int => $ordem[(int) $linha->ponto_id] ?? 9999)->values();

        foreach ($linhas as $indice => $linha) {
            DB::table('transporte_escolar.itinerario')->where('id', $linha->id)->update(['seq' => 1000 + $indice]);
        }

        foreach ($linhas as $indice => $linha) {
            DB::table('transporte_escolar.itinerario')->where('id', $linha->id)->update(['seq' => $indice + 1]);
        }

        return back()->with('ok', 'Ordem dos pontos ajustada pela trilha.');
    }

    public function itinerario(Request $request, int $rota): RedirectResponse
    {
        $this->visivel($rota);
        $dados = $request->validate([
            'ponto_id' => ['required', 'integer'],
            'veiculo_id' => ['nullable', 'integer'],
            'hora' => ['nullable', 'date_format:H:i'],
            'tipo' => ['required', 'in:I,V'],
        ]);
        $seq = (int) DB::table('transporte_escolar.itinerario')->where('rota_id', $rota)->max('seq') + 1;

        DB::table('transporte_escolar.itinerario')->insert([
            'rota_id' => $rota,
            'seq' => $seq,
            'ponto_id' => $dados['ponto_id'],
            'veiculo_id' => $dados['veiculo_id'] ?? null,
            'hora' => $dados['hora'] ?? null,
            'tipo' => $dados['tipo'],
        ]);

        return back();
    }

    public function escolas(Request $request, int $rota): RedirectResponse
    {
        $this->visivel($rota);
        $ids = array_map('intval', $request->input('escolas', []));
        DB::table('transporte_escolar.rota_escola')->where('rota_id', $rota)->delete();

        foreach ($ids as $escola) {
            DB::table('transporte_escolar.rota_escola')->insert([
                'rota_id' => $rota,
                'ref_cod_escola' => $escola,
            ]);
        }

        return back();
    }

    public function ponto(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:70'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        DB::table('transporte_escolar.ponto')->insert([
            'descricao' => $dados['descricao'],
            'latitude' => $dados['latitude'] ?? null,
            'longitude' => $dados['longitude'] ?? null,
            'origem' => 'ieducar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function coordenada(Request $request, int $ponto): RedirectResponse
    {
        $dados = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        DB::table('transporte_escolar.ponto')->where('id', $ponto)->update([
            'latitude' => $dados['latitude'],
            'longitude' => $dados['longitude'],
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Coordenada do ponto gravada.');
    }

    public function escolaCoordenada(Request $request, int $escola): RedirectResponse
    {
        $dados = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        DB::table('pmieducar.escola')->where('cod_escola', $escola)->update([
            'latitude' => (string) $dados['latitude'],
            'longitude' => (string) $dados['longitude'],
        ]);

        return back()->with('ok', 'Coordenada da escola atualizada no cadastro do i-Educar.');
    }

    private function visivel(int $rota): object
    {
        $registro = RedeConsulta::rotas()->where('r.id', $rota)->first();
        abort_if($registro === null, 404);

        return $registro;
    }
}
