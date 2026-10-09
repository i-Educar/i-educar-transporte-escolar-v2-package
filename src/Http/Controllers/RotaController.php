<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Coordenada;
use iEducar\Packages\BuritiTransporteEscolar\Support\Gpx;
use iEducar\Packages\BuritiTransporteEscolar\Support\FaixaEtaria;
use iEducar\Packages\BuritiTransporteEscolar\Support\GradeCalor;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmCalculado;
use iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta;
use iEducar\Packages\BuritiTransporteEscolar\Support\OcupacaoRota;
use iEducar\Packages\BuritiTransporteEscolar\Support\OrdemPontos;
use iEducar\Packages\BuritiTransporteEscolar\Support\PercursoTransito;
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
            ->get(['i.id', 'i.seq', 'i.hora', 'i.tipo', 'p.id as ponto_id', 'p.descricao', 'p.latitude', 'p.longitude', 'p.raio_metros', 'v.placa', 'v.passageiros', 'v.id as veiculo_id']);

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

        $trilha = [];

        if ($tracado !== null && $tracado->formato === 'gpx') {
            foreach (Gpx::coordenadas((string) $tracado->conteudo) as $ponto) {
                $trilha[] = [(float) $ponto['latitude'], (float) $ponto['longitude']];
            }
        }

        return view('transporte-escolar::rotas.show', [
            'rota' => $registro,
            'escolas' => $escolas,
            'itinerario' => $itinerario,
            'alunos' => $alunos,
            'kmCalculado' => $km,
            'minutos' => $registro->tempo_estimado_min !== null
                ? (int) $registro->tempo_estimado_min
                : KmCalculado::minutos($km, (float) $registro->velocidade_media_kmh),
            'kmInformado' => (float) $registro->km_pav + (float) $registro->km_npav,
            'pontos' => DB::table('transporte_escolar.ponto')->orderBy('descricao')->get(),
            'veiculos' => DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(),
            'podeGravar' => Acesso::permite(Processos::ROTAS, 'gravar'),
            'podeMapa' => Acesso::permite(Processos::PONTOS, 'gravar'),
            'temTracado' => $tracado !== null,
            'trilha' => $trilha,
            'codigos' => Codigos::class,
            'cobertura' => $this->cobertura($rota, (int) $registro->ano, $itinerario),
            'movimento' => OcupacaoRota::linhas($itinerario, $this->alunosPorPonto($rota)),
            'raioEscola' => 500,
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

    public function percurso(int $rota): RedirectResponse
    {
        $registro = $this->visivel($rota);
        $paradas = [];

        foreach (DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'i.ponto_id')
            ->where('i.rota_id', $rota)
            ->whereNotNull('p.latitude')
            ->whereNotNull('p.longitude')
            ->orderBy('i.seq')
            ->get(['p.latitude', 'p.longitude']) as $parada) {
            $paradas[] = ['latitude' => (float) $parada->latitude, 'longitude' => (float) $parada->longitude];
        }

        if (count($paradas) < 2) {
            return back()->with('erro', 'Cadastre ao menos dois pontos com coordenada no itinerário antes de calcular o percurso.');
        }

        $ruas = $this->ruas($paradas);
        $percurso = $ruas ?? PercursoTransito::direto($paradas, (float) $registro->velocidade_media_kmh);
        DB::table('transporte_escolar.tracado')->updateOrInsert(
            ['rota_id' => $rota],
            [
                'formato' => 'gpx',
                'conteudo' => PercursoTransito::gpx($percurso['pontos']),
                'km_calculado' => $percurso['km'],
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        DB::table('transporte_escolar.rota')->where('id', $rota)->update([
            'km_calculado' => $percurso['km'],
            'tempo_estimado_min' => $percurso['minutos'],
            'updated_at' => now(),
        ]);

        $origem = $ruas === null ? 'A ligação direta entre os pontos' : 'O percurso pelas ruas';

        return back()->with('ok', $origem . ' ficou em ' . number_format($percurso['km'], 1, ',', '.') . ' km e cerca de ' . $percurso['minutos'] . ' min.');
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

        if ($request->input('hora') === '') {
            $request->merge(['hora' => null]);
        }

        if ($request->input('veiculo_id') === '') {
            $request->merge(['veiculo_id' => null]);
        }

        $dados = $request->validate([
            'ponto_id' => ['required', 'integer'],
            'veiculo_id' => ['nullable', 'integer'],
            'hora' => ['nullable', 'date_format:H:i'],
            'tipo' => ['required', 'in:I,V'],
        ]);
        $ponto = DB::table('transporte_escolar.ponto')->where('id', $dados['ponto_id'])->first();

        if ($ponto === null) {
            return back()->with('erro', 'Escolha um ponto que já esteja cadastrado.');
        }

        $seq = (int) DB::table('transporte_escolar.itinerario')->where('rota_id', $rota)->max('seq') + 1;

        DB::table('transporte_escolar.itinerario')->insert([
            'rota_id' => $rota,
            'seq' => $seq,
            'ponto_id' => $ponto->id,
            'veiculo_id' => $dados['veiculo_id'] ?? null,
            'hora' => $dados['hora'] ?? null,
            'tipo' => $dados['tipo'],
        ]);

        $aviso = $ponto->latitude === null
            ? ' Parada incluída, mas este ponto ainda não tem coordenada e não aparece no mapa.'
            : '';

        return back()->with('ok', 'Parada ' . $seq . ' incluída no itinerário.' . $aviso);
    }

    public function removerParada(int $rota, int $parada): RedirectResponse
    {
        $this->visivel($rota);
        $apagou = DB::table('transporte_escolar.itinerario')->where('rota_id', $rota)->where('id', $parada)->delete();

        if ($apagou === 0) {
            abort(404);
        }

        $ids = DB::table('transporte_escolar.itinerario')->where('rota_id', $rota)->orderBy('seq')->pluck('id');

        foreach ($ids as $indice => $id) {
            DB::table('transporte_escolar.itinerario')->where('id', $id)->update(['seq' => $indice + 1]);
        }

        return back()->with('ok', 'Parada retirada. A ordem das demais foi refeita.');
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

    /**
     * @param list<array{latitude: float, longitude: float}> $paradas
     * @return array{pontos: list<array{latitude: float, longitude: float}>, km: float, minutos: int}|null
     */
    private function ruas(array $paradas): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $caminho = implode(';', array_map(
            static fn (array $parada): string => $parada['longitude'] . ',' . $parada['latitude'],
            $paradas,
        ));
        $curl = curl_init('https://router.project-osrm.org/route/v1/driving/' . $caminho . '?overview=full&geometries=geojson');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['User-Agent: i-educar-transporte'],
        ]);
        $corpo = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($corpo) || $codigo !== 200) {
            return null;
        }

        $json = json_decode($corpo, true);

        return PercursoTransito::dasRuas(is_array($json) ? $json : null);
    }

    public function ponto(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:70'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);
        $motivo = Coordenada::noTerritorio($dados['latitude'] ?? null, $dados['longitude'] ?? null);

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        $pontoId = DB::table('transporte_escolar.ponto')->insertGetId([
            'descricao' => $dados['descricao'],
            'latitude' => Coordenada::valor($dados['latitude'] ?? null),
            'longitude' => Coordenada::valor($dados['longitude'] ?? null),
            'origem' => 'ieducar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rotaId = (int) $request->input('rota_id');

        if ($rotaId > 0) {
            if (!Acesso::permite(Processos::ROTAS, 'gravar')) {
                abort(403);
            }

            $this->visivel($rotaId);
            $seq = (int) DB::table('transporte_escolar.itinerario')->where('rota_id', $rotaId)->max('seq') + 1;
            DB::table('transporte_escolar.itinerario')->insert([
                'rota_id' => $rotaId,
                'seq' => $seq,
                'ponto_id' => $pontoId,
                'veiculo_id' => $request->input('veiculo_id') ?: null,
                'hora' => $request->input('hora') ?: null,
                'tipo' => in_array($request->input('tipo'), ['I', 'V'], true) ? $request->input('tipo') : 'I',
            ]);

            return back()->with('ok', 'Ponto criado e incluído como parada ' . $seq . ' desta rota.');
        }

        return back()->with('ok', 'Ponto criado. Inclua-o no itinerário da rota para ele entrar na viagem.');
    }

    public function coordenada(Request $request, int $ponto): RedirectResponse
    {
        $dados = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);
        $motivo = Coordenada::noTerritorio($dados['latitude'], $dados['longitude']);

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

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

        $motivo = Coordenada::noTerritorio($dados['latitude'], $dados['longitude']);

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        DB::table('pmieducar.escola')->where('cod_escola', $escola)->update([
            'latitude' => (string) $dados['latitude'],
            'longitude' => (string) $dados['longitude'],
        ]);

        return back()->with('ok', 'Coordenada da escola atualizada no cadastro do i-Educar.');
    }

    /**
     * @return array<int, int>
     */
    private function alunosPorPonto(int $rota): array
    {
        $contagem = [];

        foreach (DB::table('transporte_escolar.aluno_rota')
            ->where('rota_id', $rota)
            ->whereNotNull('ponto_id')
            ->groupBy('ponto_id')
            ->selectRaw('ponto_id, count(*) as n')
            ->get() as $linha) {
            $contagem[(int) $linha->ponto_id] = (int) $linha->n;
        }

        return $contagem;
    }

    private function visivel(int $rota): object
    {
        $registro = RedeConsulta::rotas()->where('r.id', $rota)->first();
        abort_if($registro === null, 404);

        return $registro;
    }

    /**
     * @return array{pontos: list<array<string, mixed>>, calor: list<array{latitude: float, longitude: float, alunos: int}>}
     */
    private function cobertura(int $rota, int $ano, $itinerario): array
    {
        $vinculos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->where('ar.rota_id', $rota)
            ->get(['ar.ponto_id', 'ar.residencia_latitude', 'ar.residencia_longitude', 'f.data_nasc']);

        $movimentos = DB::table('transporte_escolar.embarque as e')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->join('transporte_escolar.aluno_rota as ar', function ($join) {
                $join->on('ar.ref_cod_aluno', '=', 'e.ref_cod_aluno')->on('ar.rota_id', '=', 'v.rota_id');
            })
            ->where('v.rota_id', $rota)
            ->whereIn('e.tipo', ['embarque', 'desembarque'])
            ->whereNotNull('ar.ponto_id')
            ->groupBy('ar.ponto_id', 'e.tipo')
            ->selectRaw('ar.ponto_id, e.tipo, count(*) as n')
            ->get();

        $porPonto = [];

        foreach ($itinerario as $parada) {
            $id = (int) $parada->ponto_id;

            if (isset($porPonto[$id])) {
                continue;
            }

            $porPonto[$id] = [
                'id' => $id,
                'nome' => (string) $parada->descricao,
                'lat' => $parada->latitude === null ? null : (float) $parada->latitude,
                'lng' => $parada->longitude === null ? null : (float) $parada->longitude,
                'raio' => (int) ($parada->raio_metros ?: 150),
                'vinculos' => 0,
                'idades' => [],
                'embarques' => 0,
                'desembarques' => 0,
            ];
        }

        $casas = [];

        foreach ($vinculos as $vinculo) {
            $idade = $vinculo->data_nasc === null
                ? null
                : RegraOferta::idade(substr((string) $vinculo->data_nasc, 0, 10), $ano);
            $pontoId = $vinculo->ponto_id === null ? null : (int) $vinculo->ponto_id;

            if ($pontoId !== null && isset($porPonto[$pontoId])) {
                $porPonto[$pontoId]['vinculos']++;

                if ($idade !== null) {
                    $porPonto[$pontoId]['idades'][] = $idade;
                }
            }

            if ($vinculo->residencia_latitude !== null && $vinculo->residencia_longitude !== null) {
                $casas[] = [
                    'latitude' => (float) $vinculo->residencia_latitude,
                    'longitude' => (float) $vinculo->residencia_longitude,
                ];
            }
        }

        foreach ($movimentos as $movimento) {
            $id = (int) $movimento->ponto_id;

            if (!isset($porPonto[$id])) {
                continue;
            }

            if ($movimento->tipo === 'embarque') {
                $porPonto[$id]['embarques'] = (int) $movimento->n;
            }

            if ($movimento->tipo === 'desembarque') {
                $porPonto[$id]['desembarques'] = (int) $movimento->n;
            }
        }

        $pontos = [];

        foreach ($porPonto as $ponto) {
            $pontos[] = [
                'id' => $ponto['id'],
                'nome' => $ponto['nome'],
                'lat' => $ponto['lat'],
                'lng' => $ponto['lng'],
                'raio' => $ponto['raio'],
                'alunos' => $ponto['vinculos'],
                'faixas' => FaixaEtaria::resumir($ponto['idades']),
                'embarques' => $ponto['embarques'],
                'desembarques' => $ponto['desembarques'],
            ];
        }

        return ['pontos' => $pontos, 'calor' => GradeCalor::celulas($casas)];
    }
}
