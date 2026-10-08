<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\LocalizarEndereco;
use iEducar\Packages\BuritiTransporteEscolar\Support\PontosProximos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
use iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SolicitacaoController
{
    public function index(): View
    {
        $escolas = VisaoRede::escolas();
        $consulta = DB::table('transporte_escolar.solicitacao as s')
            ->leftJoin('transporte_escolar.rota as r', 'r.id', '=', 's.rota_id')
            ->leftJoin('transporte_escolar.processo_vaga as pv', 'pv.id', '=', 's.processo_id')
            ->leftJoin('pmieducar.serie as ser', 'ser.cod_serie', '=', 's.ref_cod_serie')
            ->leftJoin('pmieducar.turma as tur', 'tur.cod_turma', '=', 's.ref_cod_turma')
            ->orderByDesc('s.id')
            ->select(['s.*', 'r.descricao as rota', 'pv.nome as processo', 'ser.nm_serie', 'tur.nm_turma']);

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('s.ref_cod_escola', $escolas);
        }

        $linhas = DB::table('transporte_escolar.configuracao')
            ->where('chave', 'like', 'atendimento_%')
            ->pluck('valor', 'chave');

        $series = DB::table('pmieducar.serie')->where('ativo', 1)->orderBy('nm_serie')->get(['cod_serie', 'nm_serie']);
        $turmas = DB::table('pmieducar.turma as t')
            ->leftJoin('pmieducar.serie as s', 's.cod_serie', '=', 't.ref_ref_cod_serie')
            ->where('t.ativo', 1)
            ->orderByDesc('t.ano')
            ->orderBy('t.nm_turma')
            ->get(['t.cod_turma', 't.nm_turma', 't.ano', 't.ref_ref_cod_escola', 't.ref_ref_cod_serie', 's.nm_serie']);
        $nomesSerie = $series->pluck('nm_serie', 'cod_serie');
        $nomesTurma = $turmas->pluck('nm_turma', 'cod_turma');

        return view('transporte-escolar::solicitacoes.index', [
            'pedidos' => $consulta->limit(80)->get(),
            'rede' => $escolas === null,
            'processos' => DB::table('transporte_escolar.processo_vaga as p')
                ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
                ->orderByDesc('p.ano')
                ->orderBy('p.nome')
                ->get(['p.*', 'g.nome as grupo']),
            'grupos' => DB::table('transporte_escolar.processo_grupo')->orderByDesc('ano')->orderBy('nome')->get(),
            'ofertas' => DB::table('transporte_escolar.processo_vaga_oferta as o')
                ->join('transporte_escolar.processo_vaga as p', 'p.id', '=', 'o.processo_id')
                ->join('transporte_escolar.rota as r', 'r.id', '=', 'o.rota_id')
                ->join('pmieducar.escola as e', 'e.cod_escola', '=', 'o.ref_cod_escola')
                ->join('cadastro.pessoa as pes', 'pes.idpes', '=', 'e.ref_idpes')
                ->orderByDesc('p.ano')
                ->orderBy('pes.nome')
                ->get(['o.*', 'p.nome as processo', 'p.ano', 'r.descricao as rota', 'pes.nome as escola'])
                ->map(function ($oferta) use ($nomesSerie, $nomesTurma) {
                    $oferta->series_nomes = array_map(
                        static fn (int $id): string => (string) ($nomesSerie[$id] ?? $id),
                        RegraOferta::lista($oferta->series),
                    );
                    $oferta->turmas_nomes = array_map(
                        static fn (int $id): string => (string) ($nomesTurma[$id] ?? $id),
                        RegraOferta::lista($oferta->turmas),
                    );

                    return $oferta;
                }),
            'series' => $series,
            'turmas' => $turmas,
            'rotas' => DB::table('transporte_escolar.rota as r')
                ->join('transporte_escolar.rota_escola as re', 're.rota_id', '=', 'r.id')
                ->join('pmieducar.escola as e', 'e.cod_escola', '=', 're.ref_cod_escola')
                ->join('cadastro.pessoa as pes', 'pes.idpes', '=', 'e.ref_idpes')
                ->orderByDesc('r.ano')
                ->orderBy('r.descricao')
                ->get(['r.id', 'r.descricao', 'r.ano', 'r.periodo', 'e.cod_escola', 'pes.nome as escola']),
            'atendimento' => [
                'setor' => $linhas['atendimento_setor'] ?? 'Transporte Escolar',
                'endereco' => $linhas['atendimento_endereco'] ?? '',
                'documentos' => $linhas['atendimento_documentos'] ?? '',
                'horario' => $linhas['atendimento_horario'] ?? '',
                'latitude' => $linhas['atendimento_latitude'] ?? '',
                'longitude' => $linhas['atendimento_longitude'] ?? '',
            ],
        ]);
    }

    public function atendimento(Request $request): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional grava o endereço do setor.');
        }

        $dados = [
            'atendimento_setor' => trim((string) $request->input('setor')) ?: 'Transporte Escolar',
            'atendimento_endereco' => trim((string) $request->input('endereco')),
            'atendimento_documentos' => trim((string) $request->input('documentos')),
            'atendimento_horario' => trim((string) $request->input('horario')),
            'atendimento_latitude' => trim((string) $request->input('latitude')),
            'atendimento_longitude' => trim((string) $request->input('longitude')),
        ];

        if (AtendimentoSetor::mapa($dados['atendimento_latitude'], $dados['atendimento_longitude']) === null
            && ($dados['atendimento_latitude'] !== '' || $dados['atendimento_longitude'] !== '')) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Latitude e longitude precisam formar um ponto válido para o mapa.');
        }

        foreach ($dados as $chave => $valor) {
            DB::table('transporte_escolar.configuracao')->updateOrInsert(['chave' => $chave], ['valor' => $valor]);
        }

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Endereço do setor gravado. A família vê o mapa quando o pedido é aceito.');
    }

    public function processo(Request $request): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional abre processo de vagas.');
        }

        $nome = trim((string) $request->input('nome'));
        $ano = (int) $request->input('ano');
        $inicio = (string) $request->input('inicio');
        $fim = (string) $request->input('fim');
        $grupoId = (int) $request->input('grupo_id');
        $grupo = $grupoId > 0
            ? DB::table('transporte_escolar.processo_grupo')->where('id', $grupoId)->where('ano', $ano)->first()
            : null;

        if (mb_strlen($nome) < 3 || $ano < 2000 || !ProcessoVaga::noPeriodo(true, $inicio, $fim, new \DateTimeImmutable($inicio)) || ($grupoId > 0 && $grupo === null)) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Informe nome, ano letivo, um período válido e, se escolher, um agrupamento do mesmo ano.');
        }

        DB::table('transporte_escolar.processo_vaga')->insert([
            'nome' => $nome,
            'ano' => $ano,
            'inicio' => $inicio,
            'fim' => $fim,
            'ativo' => true,
            'grupo_id' => $grupo?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Processo de vagas aberto para ' . $ano . '.');
    }

    public function editarProcesso(Request $request, int $processo): RedirectResponse
    {
        return $this->editarPeriodo('transporte_escolar.processo_vaga', $processo, $request, true);
    }

    public function situacaoProcesso(Request $request, int $processo): RedirectResponse
    {
        return $this->mudarSituacao('transporte_escolar.processo_vaga', $processo, (string) $request->input('acao'));
    }

    public function editarGrupo(Request $request, int $grupo): RedirectResponse
    {
        return $this->editarPeriodo('transporte_escolar.processo_grupo', $grupo, $request, false);
    }

    public function situacaoGrupo(Request $request, int $grupo): RedirectResponse
    {
        return $this->mudarSituacao('transporte_escolar.processo_grupo', $grupo, (string) $request->input('acao'));
    }

    public function grupo(Request $request): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional agrupa processos.');
        }

        $nome = trim((string) $request->input('nome'));
        $ano = (int) $request->input('ano');
        $inicio = (string) $request->input('inicio');
        $fim = (string) $request->input('fim');

        if (mb_strlen($nome) < 3 || $ano < 2000 || !ProcessoVaga::noPeriodo(true, $inicio, $fim, new \DateTimeImmutable($inicio))) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'O agrupamento precisa de nome, ano e um período em que o fim não seja anterior ao início.');
        }

        $grupoId = DB::table('transporte_escolar.processo_grupo')->insertGetId([
            'nome' => $nome,
            'ano' => $ano,
            'inicio' => $inicio,
            'fim' => $fim,
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ids = array_map('intval', (array) $request->input('processos', []));

        if ($ids !== []) {
            DB::table('transporte_escolar.processo_vaga')
                ->whereIn('id', $ids)
                ->where('ano', $ano)
                ->update(['grupo_id' => $grupoId, 'updated_at' => now()]);
        }

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Agrupamento aberto. A família vê os processos dele juntos.');
    }

    public function oferta(Request $request): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional publica vaga de rota.');
        }

        $processo = DB::table('transporte_escolar.processo_vaga')->where('id', (int) $request->input('processo_id'))->first();
        $rota = DB::table('transporte_escolar.rota')->where('id', (int) $request->input('rota_id'))->first();
        $escola = (int) $request->input('ref_cod_escola');
        $turno = (string) $request->input('turno');
        $vagas = (int) $request->input('vagas');
        $liga = $rota === null ? false : DB::table('transporte_escolar.rota_escola')
            ->where('rota_id', $rota->id)
            ->where('ref_cod_escola', $escola)
            ->exists();

        $idadeMin = $request->input('idade_min');
        $idadeMax = $request->input('idade_max');
        $idadeMin = $idadeMin === null || $idadeMin === '' ? null : (int) $idadeMin;
        $idadeMax = $idadeMax === null || $idadeMax === '' ? null : (int) $idadeMax;
        $series = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('series', [])))));
        $turmas = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('turmas', [])))));
        $seriesOk = $series === [] || DB::table('pmieducar.serie')->where('ativo', 1)->whereIn('cod_serie', $series)->count() === count($series);
        $turmasOk = $turmas === [] || DB::table('pmieducar.turma')
            ->where('ativo', 1)
            ->where('ref_ref_cod_escola', $escola)
            ->where('ano', $processo->ano ?? 0)
            ->whereIn('cod_turma', $turmas)
            ->count() === count($turmas);
        $idadeOk = ($idadeMin === null || ($idadeMin >= 0 && $idadeMin <= 30))
            && ($idadeMax === null || ($idadeMax >= 0 && $idadeMax <= 30))
            && ($idadeMin === null || $idadeMax === null || $idadeMin <= $idadeMax);

        if ($processo === null || $rota === null || (int) $rota->ano !== (int) $processo->ano || !$liga || !isset(Codigos::PERIODO[$turno]) || $vagas < 1 || !$seriesOk || !$turmasOk || !$idadeOk || ($processo->situacao ?? ProcessoVaga::ABERTO) === ProcessoVaga::BLOQUEADO) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'A vaga precisa de processo, rota do mesmo ano, escola dessa rota, turno e regras de idade, série e turma coerentes.');
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->updateOrInsert(
            [
                'processo_id' => $processo->id,
                'rota_id' => $rota->id,
                'ref_cod_escola' => $escola,
                'turno' => $turno,
            ],
            [
                'vagas' => $vagas,
                'idade_min' => $idadeMin,
                'idade_max' => $idadeMax,
                'series' => $series === [] ? null : json_encode($series),
                'turmas' => $turmas === [] ? null : json_encode($turmas),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Vaga publicada na rota ' . $rota->descricao . '.');
    }

    public function situacao(Request $request, int $solicitacao): RedirectResponse
    {
        $escolas = VisaoRede::escolas();
        $pedido = DB::table('transporte_escolar.solicitacao')->where('id', $solicitacao)->first();

        if ($pedido === null || $escolas === [] || (is_array($escolas) && !in_array((int) $pedido->ref_cod_escola, $escolas, true))) {
            abort(404);
        }

        $proxima = SituacaoPedido::mover($pedido->situacao, (string) $request->input('acao'), $escolas === null ? 'rede' : 'escola');

        if ($proxima === null) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Esta mudança de situação não cabe neste perfil.');
        }

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'situacao' => $proxima,
            'motivo_situacao' => trim((string) $request->input('motivo')) ?: null,
            'atualizado_em' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Situação do protocolo ' . $pedido->protocolo . ' atualizada.');
    }

    public function proximos(int $solicitacao): JsonResponse
    {
        $pedido = $this->pedidoVisivel($solicitacao);
        $local = LocalizarEndereco::coordenada((string) $pedido->endereco);

        if ($local === null) {
            return response()->json([
                'ok' => false,
                'aviso' => 'Não foi possível localizar este endereço no mapa. O nome e o telefone não foram enviados.',
                'pontos' => [],
            ]);
        }

        return response()->json([
            'ok' => true,
            'aviso' => null,
            'pontos' => $this->pontosPerto($local['latitude'], $local['longitude']),
        ]);
    }

    public function mapa(int $solicitacao): View
    {
        $pedido = $this->pedidoVisivel($solicitacao);
        $local = LocalizarEndereco::coordenada((string) $pedido->endereco);

        return view('transporte-escolar::solicitacoes.mapa', [
            'protocolo' => $pedido->protocolo,
            'endereco' => $pedido->endereco,
            'local' => $local,
            'pontos' => $local === null ? [] : $this->pontosPerto($local['latitude'], $local['longitude'], 12),
        ]);
    }

    private function pedidoVisivel(int $solicitacao): object
    {
        $escolas = VisaoRede::escolas();
        $pedido = DB::table('transporte_escolar.solicitacao')->where('id', $solicitacao)->first();

        if ($pedido === null || $escolas === [] || (is_array($escolas) && !in_array((int) $pedido->ref_cod_escola, $escolas, true))) {
            abort(404);
        }

        return $pedido;
    }

    /**
     * @return list<array{id: int, nome: string, metros: int, raio: int, dentro: bool, latitude: float, longitude: float}>
     */
    private function pontosPerto(float $latitude, float $longitude, int $limite = 5): array
    {
        $cadastro = [];

        foreach (DB::table('transporte_escolar.ponto')->whereNotNull('latitude')->whereNotNull('longitude')->get() as $ponto) {
            $cadastro[] = [
                'id' => (int) $ponto->id,
                'nome' => (string) $ponto->descricao,
                'latitude' => (float) $ponto->latitude,
                'longitude' => (float) $ponto->longitude,
                'raio' => (int) ($ponto->raio_metros ?? 150),
            ];
        }

        $proximos = PontosProximos::ordenar($latitude, $longitude, $cadastro, $limite);
        $porId = [];

        foreach ($cadastro as $ponto) {
            $porId[$ponto['id']] = $ponto;
        }

        foreach ($proximos as &$proximo) {
            $proximo['latitude'] = $porId[$proximo['id']]['latitude'];
            $proximo['longitude'] = $porId[$proximo['id']]['longitude'];
        }

        return $proximos;
    }

    private function editarPeriodo(string $tabela, int $id, Request $request, bool $comGrupo): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional altera o processo de vagas.');
        }

        $registro = DB::table($tabela)->where('id', $id)->first();
        $nome = trim((string) $request->input('nome'));
        $ano = (int) $request->input('ano');
        $inicio = (string) $request->input('inicio');
        $fim = (string) $request->input('fim');
        $grupoId = $comGrupo ? (int) $request->input('grupo_id') : 0;
        $grupo = $grupoId > 0
            ? DB::table('transporte_escolar.processo_grupo')->where('id', $grupoId)->where('ano', $ano)->first()
            : null;

        if ($registro === null || mb_strlen($nome) < 3 || $ano < 2000 || !ProcessoVaga::noPeriodo(true, $inicio, $fim, new \DateTimeImmutable($inicio)) || ($grupoId > 0 && $grupo === null)) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'A edição precisa de nome, ano e um período válido. O agrupamento, se houver, tem de ser do mesmo ano.');
        }

        $dados = [
            'nome' => $nome,
            'ano' => $ano,
            'inicio' => $inicio,
            'fim' => $fim,
            'updated_at' => now(),
        ];

        if ($comGrupo) {
            $dados['grupo_id'] = $grupo?->id;
        }

        DB::table($tabela)->where('id', $id)->update($dados);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Dados atualizados.');
    }

    private function mudarSituacao(string $tabela, int $id, string $acao): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional pausa ou bloqueia o processo de vagas.');
        }

        $registro = DB::table($tabela)->where('id', $id)->first();
        $proxima = $registro === null ? null : ProcessoVaga::proxima((string) ($registro->situacao ?? ProcessoVaga::ABERTO), $acao);

        if ($proxima === null) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Essa mudança de situação não cabe no processo agora.');
        }

        DB::table($tabela)->where('id', $id)->update([
            'situacao' => $proxima,
            'ativo' => $proxima === ProcessoVaga::ABERTO,
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Situação alterada para ' . ProcessoVaga::rotulo($proxima) . '.');
    }
}
