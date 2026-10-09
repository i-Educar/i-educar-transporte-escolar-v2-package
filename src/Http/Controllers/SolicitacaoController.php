<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AtribuicaoAluno;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ComprovantePedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\ConferenciaPedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\HistoricoPedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\OcupacaoOferta;
use iEducar\Packages\BuritiTransporteEscolar\Support\IdentidadeMunicipio;
use iEducar\Packages\BuritiTransporteEscolar\Support\LocalizarEndereco;
use iEducar\Packages\BuritiTransporteEscolar\Support\PdfSimples;
use iEducar\Packages\BuritiTransporteEscolar\Support\QrCodigo;
use iEducar\Packages\BuritiTransporteEscolar\Support\PontosProximos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoEdicao;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
use iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SolicitacaoController
{
    public function index(): View
    {
        $escolas = VisaoRede::escolas();
        $consulta = DB::table('transporte_escolar.solicitacao as s')
            ->leftJoin('transporte_escolar.rota as r', 'r.id', '=', 's.rota_id')
            ->leftJoin('transporte_escolar.rota as rv', 'rv.id', '=', 's.vinculo_rota_id')
            ->leftJoin('transporte_escolar.processo_vaga as pv', 'pv.id', '=', 's.processo_id')
            ->leftJoin('transporte_escolar.processo_grupo as pg', 'pg.id', '=', 'pv.grupo_id')
            ->leftJoin('transporte_escolar.processo_vaga_oferta as of', 'of.id', '=', 's.oferta_id')
            ->leftJoin('pmieducar.serie as ser', 'ser.cod_serie', '=', 's.ref_cod_serie')
            ->leftJoin('pmieducar.turma as tur', 'tur.cod_turma', '=', 's.ref_cod_turma')
            ->orderByDesc('s.id')
            ->select(['s.*', 'r.descricao as rota', 'rv.descricao as rota_vinculada', 'pv.nome as processo', 'pv.situacao as situacao_processo', 'pg.situacao as situacao_grupo', 'of.situacao as situacao_oferta', 'ser.nm_serie', 'tur.nm_turma']);

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
            ->leftJoin('pmieducar.curso as c', 'c.cod_curso', '=', 's.ref_cod_curso')
            ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 't.ref_ref_cod_escola')
            ->leftJoin('cadastro.pessoa as ep', 'ep.idpes', '=', 'e.ref_idpes')
            ->where('t.ativo', 1)
            ->orderBy('ep.nome')
            ->orderBy('c.nm_curso')
            ->orderBy('s.nm_serie')
            ->orderBy('t.nm_turma')
            ->get(['t.cod_turma', 't.nm_turma', 't.ano', 't.ref_ref_cod_escola', 't.ref_ref_cod_serie', 's.nm_serie', 'c.nm_curso', 'ep.nome as escola']);
        $nomesSerie = $series->pluck('nm_serie', 'cod_serie');
        $nomesTurma = $turmas->pluck('nm_turma', 'cod_turma');

        $pedidos = $consulta->limit(80)->get();
        $historicos = Schema::hasTable('transporte_escolar.solicitacao_historico')
            ? DB::table('transporte_escolar.solicitacao_historico')->whereIn('solicitacao_id', $pedidos->pluck('id')->all() ?: [0])->orderBy('em')->get()->groupBy('solicitacao_id')
            : collect();

        return view('transporte-escolar::solicitacoes.index', [
            'pedidos' => $pedidos,
            'historicos' => $historicos,
            'rede' => $escolas === null,
            'processos' => DB::table('transporte_escolar.processo_vaga as p')
                ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
                ->orderByDesc('p.ano')
                ->orderBy('p.nome')
                ->get(['p.*', 'g.nome as grupo']),
            'grupos' => DB::table('transporte_escolar.processo_grupo')->orderByDesc('ano')->orderBy('nome')->get(),
            'usoProcesso' => DB::table('transporte_escolar.solicitacao')
                ->whereNotNull('processo_id')
                ->selectRaw("processo_id, count(*) as pedidos, count(*) filter (where situacao = 'deferida') as deferidos")
                ->groupBy('processo_id')
                ->get()
                ->keyBy('processo_id'),
            'usoOferta' => collect(OcupacaoOferta::porId()),
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
            'escolasRota' => DB::table('transporte_escolar.rota_escola')
                ->get(['rota_id', 'ref_cod_escola'])
                ->groupBy('rota_id')
                ->map(static fn ($linhas) => $linhas->pluck('ref_cod_escola')->map(static fn ($id): int => (int) $id)->unique()->values()->all())
                ->all(),
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

    public function situacaoOferta(Request $request, int $oferta): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional pausa a vaga.');
        }

        $registro = DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $oferta)->first();

        if ((string) $request->input('acao') === 'fechar') {
            return $this->fecharOferta($registro);
        }

        $proxima = $registro === null ? null : ProcessoVaga::proxima((string) ($registro->situacao ?? ProcessoVaga::ABERTO), (string) $request->input('acao'));

        if ($proxima === null) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Essa mudança de situação não cabe na vaga agora.');
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $oferta)->update([
            'situacao' => $proxima,
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Vaga ' . mb_strtolower(ProcessoVaga::rotulo($proxima)) . '. Pedidos novos desta vaga ficam suspensos.');
    }

    private function fecharOferta(?object $registro): RedirectResponse
    {
        if ($registro === null || ($registro->situacao ?? ProcessoVaga::ABERTO) === ProcessoVaga::FECHADO) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Esta vaga já está fechada.');
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $registro->id)->update([
            'situacao' => ProcessoVaga::FECHADO,
            'updated_at' => now(),
        ]);

        $aprovados = DB::table('transporte_escolar.solicitacao')
            ->where('oferta_id', $registro->id)
            ->where('situacao', SituacaoPedido::DEFERIDA)
            ->get();
        $rota = (string) DB::table('transporte_escolar.rota')->where('id', $registro->rota_id)->value('descricao');

        foreach ($aprovados as $pedido) {
            DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
                'situacao' => SituacaoPedido::ESPERA,
                'atualizado_em' => now(),
            ]);
            $pedido->situacao = SituacaoPedido::ESPERA;
            HistoricoPedido::registrar((int) $pedido->id, SituacaoPedido::ESPERA, HistoricoPedido::lugar($pedido, $rota), 'Vagas fechadas. O pedido aprovado foi para a lista de espera.');
        }

        $quantidade = $aprovados->count();
        $texto = $quantidade === 1 ? '1 pedido aprovado foi para a lista de espera.' : $quantidade . ' pedidos aprovados foram para a lista de espera.';

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Vaga fechada. ' . $texto);
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
        $grupo = $processo === null || $processo->grupo_id === null
            ? null
            : DB::table('transporte_escolar.processo_grupo')->where('id', $processo->grupo_id)->first();
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
        $recorte = $this->recortePedido($request, $series, $turmas);
        $series = $recorte['series'];
        $turmas = $recorte['turmas'];
        $recorteOk = $recorte['ok'] && $this->recorteCabe((int) ($rota->id ?? 0), (int) ($processo->ano ?? 0), $series, $turmas);
        $idadeOk = ($idadeMin === null || ($idadeMin >= 0 && $idadeMin <= 30))
            && ($idadeMax === null || ($idadeMax >= 0 && $idadeMax <= 30))
            && ($idadeMin === null || $idadeMax === null || $idadeMin <= $idadeMax);

        if ($processo === null || $rota === null || (int) $rota->ano !== (int) $processo->ano || !$liga || !isset(Codigos::PERIODO[$turno]) || $vagas < 1 || !$recorteOk || !$idadeOk || !ProcessoVaga::recebePedido($processo->situacao ?? null, $grupo->situacao ?? null)) {
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

    public function editarOferta(Request $request, int $oferta): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional altera vaga publicada.');
        }

        $atual = DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $oferta)->first();

        if ($atual === null) {
            abort(404);
        }

        $uso = DB::table('transporte_escolar.solicitacao')->where('oferta_id', $oferta)->selectRaw("count(*) as pedidos, count(*) filter (where situacao = 'deferida') as deferidos, count(*) filter (where situacao in ('recebida', 'em_analise', 'deferida', 'conferida')) as ocupadas")->first();
        $pedidos = (int) ($uso->pedidos ?? 0);
        $processoId = $pedidos > 0 ? (int) $atual->processo_id : (int) $request->input('processo_id', $atual->processo_id);
        $rotaId = $pedidos > 0 ? (int) $atual->rota_id : (int) $request->input('rota_id', $atual->rota_id);
        $escola = $pedidos > 0 ? (int) $atual->ref_cod_escola : (int) $request->input('ref_cod_escola', $atual->ref_cod_escola);
        $turno = $pedidos > 0 ? (string) $atual->turno : (string) $request->input('turno', $atual->turno);
        $vagas = (int) $request->input('vagas');
        $idadeMin = $request->input('idade_min');
        $idadeMax = $request->input('idade_max');
        $idadeMin = $idadeMin === null || $idadeMin === '' ? null : (int) $idadeMin;
        $idadeMax = $idadeMax === null || $idadeMax === '' ? null : (int) $idadeMax;
        $series = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('series', [])))));
        $turmas = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('turmas', [])))));
        $deferidos = (int) ($uso->deferidos ?? 0);

        if ($deferidos === 0) {
            $recorte = $this->recortePedido($request, $series, $turmas);
            $series = $recorte['series'];
            $turmas = $recorte['turmas'];
            $ano = (int) DB::table('transporte_escolar.processo_vaga')->where('id', $processoId)->value('ano');

            if (!$recorte['ok'] || !$this->recorteCabe($rotaId, $ano, $series, $turmas)) {
                return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Série e turma precisam existir no ano do processo, em uma escola desta rota. Sem essa restrição, deixe a vaga aberta para qualquer série.');
            }
        }

        $motivo = ProcessoEdicao::oferta([
            'processo_id' => (int) $atual->processo_id,
            'rota_id' => (int) $atual->rota_id,
            'escola' => (int) $atual->ref_cod_escola,
            'turno' => (string) $atual->turno,
            'vagas' => (int) $atual->vagas,
            'idade_min' => $atual->idade_min === null ? null : (int) $atual->idade_min,
            'idade_max' => $atual->idade_max === null ? null : (int) $atual->idade_max,
            'series' => RegraOferta::lista($atual->series),
            'turmas' => RegraOferta::lista($atual->turmas),
        ], [
            'processo_id' => $processoId,
            'rota_id' => $rotaId,
            'escola' => $escola,
            'turno' => $turno,
            'vagas' => $vagas,
            'idade_min' => $idadeMin,
            'idade_max' => $idadeMax,
            'series' => $series,
            'turmas' => $turmas,
        ], $pedidos, $deferidos, (int) ($uso->ocupadas ?? 0));

        if ($motivo !== null || $vagas < 1) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', $motivo ?? 'Informe ao menos uma vaga.');
        }

        if ($pedidos === 0) {
            $processo = DB::table('transporte_escolar.processo_vaga')->where('id', $processoId)->first();
            $rota = DB::table('transporte_escolar.rota')->where('id', $rotaId)->first();
            $liga = $rota === null ? false : DB::table('transporte_escolar.rota_escola')->where('rota_id', $rotaId)->where('ref_cod_escola', $escola)->exists();
            $idadeOk = ($idadeMin === null || ($idadeMin >= 0 && $idadeMin <= 30)) && ($idadeMax === null || ($idadeMax >= 0 && $idadeMax <= 30)) && ($idadeMin === null || $idadeMax === null || $idadeMin <= $idadeMax);

            if ($processo === null || $rota === null || (int) $rota->ano !== (int) $processo->ano || !$liga || !isset(Codigos::PERIODO[$turno]) || !$idadeOk) {
                return redirect('/transporte-escolar/solicitacoes')->with('erro', 'A vaga precisa de processo, rota do mesmo ano, escola dessa rota, turno e regras coerentes.');
            }
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $oferta)->update([
            'processo_id' => $processoId,
            'rota_id' => $rotaId,
            'ref_cod_escola' => $escola,
            'turno' => $turno,
            'vagas' => $vagas,
            'idade_min' => $idadeMin,
            'idade_max' => $idadeMax,
            'series' => $series === [] ? null : json_encode($series),
            'turmas' => $turmas === [] ? null : json_encode($turmas),
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Vaga atualizada.');
    }

    public function situacao(Request $request, int $solicitacao): RedirectResponse
    {
        $escolas = VisaoRede::escolas();
        $pedido = DB::table('transporte_escolar.solicitacao')->where('id', $solicitacao)->first();

        if ($pedido === null || $escolas === [] || (is_array($escolas) && !in_array((int) $pedido->ref_cod_escola, $escolas, true))) {
            abort(404);
        }

        $acao = (string) $request->input('acao');
        $contexto = DB::table('transporte_escolar.solicitacao as s')
            ->leftJoin('transporte_escolar.processo_vaga as p', 'p.id', '=', 's.processo_id')
            ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
            ->leftJoin('transporte_escolar.processo_vaga_oferta as o', 'o.id', '=', 's.oferta_id')
            ->where('s.id', $solicitacao)
            ->first(['p.situacao as situacao_processo', 'g.situacao as situacao_grupo', 'o.situacao as situacao_oferta']);

        if (in_array($acao, ['encaminhar', 'deferir', 'esperar'], true) && $contexto !== null && !ProcessoVaga::recebePedido($contexto->situacao_processo, $contexto->situacao_grupo, $contexto->situacao_oferta)) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'O processo ou a vaga está pausado. O pedido não segue enquanto a pausa durar.');
        }

        $temVaga = $this->temVaga($pedido);

        if ($acao === 'deferir' && in_array($pedido->situacao, [SituacaoPedido::RECEBIDA, SituacaoPedido::ANALISE], true) && $temVaga === false) {
            if ($request->input('ciente') !== '1') {
                return redirect('/transporte-escolar/solicitacoes')->with('erro', 'A linha está lotada. Confirme a ciência para colocar o pedido na lista de espera.');
            }

            $acao = 'esperar';
        }

        if ($acao === 'esperar') {
            if ($temVaga === null) {
                return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Este pedido não está ligado a uma vaga publicada.');
            }

            if ($temVaga) {
                return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Ainda há vaga nesta rota. Aceite o pedido em vez de colocar na lista de espera.');
            }
        }

        if ($acao === 'deferir' && $pedido->situacao === SituacaoPedido::ESPERA && $temVaga !== true) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Ainda não há vaga para chamar este aluno da lista de espera.');
        }

        $proxima = SituacaoPedido::mover($pedido->situacao, $acao, $escolas === null ? 'rede' : 'escola');

        if ($proxima === null) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Esta mudança de situação não cabe neste perfil.');
        }

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'situacao' => $proxima,
            'motivo_situacao' => trim((string) $request->input('motivo')) ?: null,
            'atualizado_em' => now(),
        ]);
        $pedido->situacao = $proxima;
        HistoricoPedido::registrar(
            (int) $pedido->id,
            $proxima,
            HistoricoPedido::lugar($pedido, (string) DB::table('transporte_escolar.rota')->where('id', $pedido->rota_id)->value('descricao')),
            trim((string) $request->input('motivo')) ?: null,
        );

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Situação do protocolo ' . $pedido->protocolo . ' atualizada.');
    }

    public function matriculas(Request $request, int $solicitacao): JsonResponse
    {
        $pedido = $this->pedidoRede($solicitacao);

        if ($pedido === null || $pedido->situacao !== SituacaoPedido::DEFERIDA) {
            abort(404);
        }

        $rotaId = (int) $request->input('rota_id', $pedido->rota_id);
        $turno = (string) $request->input('turno', $pedido->turno);
        $alunoId = (int) $request->input('aluno_id', 0);
        $matriculaId = (int) $request->input('matricula_id', 0);

        if ($alunoId > 0) {
            $escolhida = $this->matriculasDoPedido($pedido)->where('a.cod_aluno', $alunoId);

            if ($matriculaId > 0) {
                $escolhida->where('m.cod_matricula', $matriculaId);
            }

            $matricula = $escolhida->first();

            if ($matricula === null) {
                return response()->json(['ok' => false, 'avisos' => ['Não há matrícula ativa deste aluno no ano do pedido.']]);
            }

            return response()->json($this->leitura($pedido, $matricula, $rotaId, $turno));
        }

        $q = trim((string) $request->input('q', ''));
        $digitos = preg_replace('/\D/', '', $q) ?? '';

        if (mb_strlen($q) < 2) {
            return response()->json(['alunos' => []]);
        }

        $linhas = $this->matriculasDoPedido($pedido)
            ->where(function ($consulta) use ($q, $digitos) {
                $consulta->where('p.nome', 'ilike', '%' . $q . '%');

                if ($digitos !== '' && ctype_digit($digitos)) {
                    $consulta->orWhere('m.cod_matricula', (int) $digitos)
                        ->orWhereRaw("regexp_replace(coalesce(f.cpf::text, ''), '\\D', '', 'g') = ?", [$digitos]);
                }
            })
            ->limit(8)
            ->get();

        return response()->json([
            'alunos' => $linhas->map(fn ($linha): array => $this->leitura($pedido, $linha, $rotaId, $turno))->values(),
        ]);
    }

    public function vincular(Request $request, int $solicitacao): RedirectResponse
    {
        $pedido = $this->pedidoRede($solicitacao);

        if ($pedido === null || $pedido->situacao !== SituacaoPedido::DEFERIDA) {
            abort(404);
        }

        $alunoId = (int) $request->input('aluno_id');
        $matriculaId = (int) $request->input('matricula_id');
        $rotaId = (int) $request->input('rota_id');
        $rotaEscolhida = DB::table('transporte_escolar.rota')->where('id', $rotaId)->first();
        $turno = (string) ($rotaEscolhida->periodo ?? '');
        $voltar = fn (string $mensagem) => redirect('/transporte-escolar/solicitacoes')->with('erro', $mensagem)->with('conferir', $solicitacao);

        if ($rotaEscolhida === null || !isset(Codigos::PERIODO[$turno])) {
            return $voltar('A rota escolhida não tem um turno definido.');
        }

        $exigidos = HistoricoPedido::exigidos(
            Schema::hasColumn('transporte_escolar.processo_vaga', 'documentos_exigidos')
                ? DB::table('transporte_escolar.processo_vaga')->where('id', $pedido->processo_id)->value('documentos_exigidos')
                : null,
        );
        $marcados = array_map('strval', (array) $request->input('exigido', []));
        $faltam = [];

        if ($exigidos === []) {
            if (!$request->boolean('documentos')) {
                return $voltar('Assinale que a documentação foi conferida.');
            }
        } else {
            foreach ($exigidos as $indice => $nome) {
                if (!in_array((string) $indice, $marcados, true)) {
                    $faltam[] = $nome;
                }
            }

            if ($faltam !== []) {
                return $voltar('Falta assinalar: ' . implode(', ', $faltam) . '.');
            }
        }

        $rotaNome = (string) $rotaEscolhida->descricao;
        $documentos = $exigidos === [] ? 'Documentação conferida, sem rol obrigatório neste processo.' : 'Documentos assinalados: ' . implode(', ', $exigidos) . '.';
        $avisos = [];
        $alunoGravado = null;

        if ($alunoId > 0 && $matriculaId > 0) {
            $matricula = $this->matriculasDoPedido($pedido)
                ->where('a.cod_aluno', $alunoId)
                ->where('m.cod_matricula', $matriculaId)
                ->first();

            if ($matricula === null) {
                return $voltar('A matrícula escolhida não está ativa no ano do pedido.');
            }

            $turnoMatricula = Codigos::periodoTurma(isset($matricula->turma_turno_id) ? (int) $matricula->turma_turno_id : null);

            if ($turnoMatricula !== null && $turnoMatricula !== $turno) {
                $avisos[] = 'A matrícula está no turno ' . (Codigos::PERIODO[$turnoMatricula] ?? $turnoMatricula) . ' e a rota é ' . Codigos::PERIODO[$turno] . '.';
            }

            try {
                $rotaAnterior = (int) ($pedido->vinculo_rota_id ?? 0);

                if ($rotaAnterior > 0 && $rotaAnterior !== $rotaId && (int) ($pedido->ref_cod_aluno ?? 0) === $alunoId) {
                    AtribuicaoAluno::desvincular($alunoId, $rotaAnterior);
                }

                AtribuicaoAluno::vincular($alunoId, $rotaId, null, $turno, null, null, $matriculaId);
            } catch (RuntimeException $e) {
                return $voltar($e->getMessage());
            }

            $alunoGravado = $alunoId;
        } else {
            $avisos[] = 'Sem matrícula ativa. O aluno entrou na rota e o aviso permanece até a matrícula ser informada.';
        }

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'situacao' => SituacaoPedido::CONFERIDA,
            'ref_cod_aluno' => $alunoGravado,
            'vinculo_rota_id' => $rotaId,
            'documentos_em' => now(),
            'atualizado_em' => now(),
        ]);
        $pedido->situacao = SituacaoPedido::CONFERIDA;
        HistoricoPedido::registrar(
            (int) $pedido->id,
            SituacaoPedido::CONFERIDA,
            HistoricoPedido::lugar($pedido, $rotaNome),
            $documentos . ($avisos === [] ? '' : ' ' . implode(' ', $avisos)),
        );

        $texto = 'Documentação conferida. O aluno entrou na rota ' . $rotaNome . '.';

        if ($avisos !== []) {
            $texto .= ' ' . implode(' ', $avisos);
        }

        return redirect('/transporte-escolar/solicitacoes')->with('ok', $texto);
    }

    private function temVaga(object $pedido): ?bool
    {
        if ((int) ($pedido->oferta_id ?? 0) < 1) {
            return null;
        }

        $vagas = DB::table('transporte_escolar.processo_vaga_oferta')->where('id', $pedido->oferta_id)->value('vagas');

        if ($vagas === null) {
            return null;
        }

        return ProcessoVaga::cabe((int) $vagas, OcupacaoOferta::ocupadas((int) $pedido->oferta_id));
    }

    private function pedidoRede(int $solicitacao): ?object
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o setor de transporte confere a documentação e inclui o aluno na rota.');
        }

        return DB::table('transporte_escolar.solicitacao')->where('id', $solicitacao)->first();
    }

    private function matriculasDoPedido(object $pedido)
    {
        return RedeConsulta::matriculasAtivas((int) $pedido->ano)
            ->addSelect(['m.cod_matricula', 'f.data_nasc', 'm.ref_ref_cod_serie as cod_serie', 'mt.ref_cod_turma as cod_turma', 't.turma_turno_id']);
    }

    /**
     * @return array{id: int, matricula: int, rotulo: string, ok: bool, avisos: list<string>}
     */
    private function leitura(object $pedido, object $matricula, int $rotaId, string $turno): array
    {
        $rota = DB::table('transporte_escolar.rota')->where('id', $rotaId)->first();

        if ($rota !== null) {
            $rota->escolas = DB::table('transporte_escolar.rota_escola')->where('rota_id', $rota->id)->pluck('ref_cod_escola')->map(static fn ($id): int => (int) $id)->all();
        }

        $atuais = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->where('ar.ref_cod_aluno', $matricula->cod_aluno)
            ->where('ar.ano', (int) $pedido->ano)
            ->pluck('r.descricao')
            ->all();
        $avisos = ConferenciaPedido::avisos($pedido, $matricula, $rota, $turno, $atuais);

        if ($avisos === []) {
            $avisos[] = 'Nenhuma divergência entre o pedido, a matrícula e a rota.';
        }

        return [
            'id' => (int) $matricula->cod_aluno,
            'matricula' => (int) $matricula->cod_matricula,
            'rotulo' => trim($matricula->cod_matricula . ' · ' . $matricula->nome . ' · ' . ($matricula->escola ?: '') . ' · ' . ($matricula->nm_serie ?: '') . ' ' . ($matricula->nm_turma ?: '')),
            'ok' => count($avisos) === 1 && str_starts_with($avisos[0], 'Nenhuma'),
            'avisos' => $avisos,
        ];
    }

    public function pdf(int $solicitacao): Response
    {
        $pedido = DB::table('transporte_escolar.solicitacao as s')
            ->leftJoin('transporte_escolar.rota as r', 'r.id', '=', 's.rota_id')
            ->leftJoin('transporte_escolar.processo_vaga as pv', 'pv.id', '=', 's.processo_id')
            ->where('s.id', $solicitacao)
            ->first(['s.*', 'r.descricao as rota', 'pv.nome as processo']);

        if ($pedido === null) {
            abort(404);
        }

        $escolas = VisaoRede::escolas();

        if ($escolas === [] || (is_array($escolas) && !in_array((int) $pedido->ref_cod_escola, $escolas, true))) {
            abort(404);
        }

        $token = $this->acompanhamento($pedido);
        $contatos = $this->contatos($pedido);
        $link = url('/te/' . $token);
        $pdf = PdfSimples::impresso([
            'municipio' => IdentidadeMunicipio::rotulo(),
            'titulo' => 'PEDIDO DE TRANSPORTE ESCOLAR',
            'protocolo' => (string) $pedido->protocolo,
            'campos' => ComprovantePedido::campos(
                SituacaoPedido::frase((string) $pedido->situacao),
                date('d/m/Y H:i', strtotime((string) $pedido->pedido_em)),
                (string) $pedido->nome_aluno,
                (string) $pedido->escola_informada,
                Codigos::PERIODO[$pedido->turno] ?? (string) $pedido->turno,
                $contatos['escola'],
                $contatos['setor'],
                $link,
            ),
            'assinaturas' => ['Responsavel', 'Escola', 'Transporte escolar'],
            'observacao' => 'Aluno e unidade abreviados. O QR pede a confirmacao antes do status.',
            'logo' => IdentidadeMunicipio::arquivo(),
            'qr' => QrCodigo::matriz($link),
        ]);

        $arquivo = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $pedido->protocolo) ?: 'pedido';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $arquivo . '.pdf"',
        ]);
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

    private function acompanhamento(object $pedido): string
    {
        $token = trim((string) ($pedido->acompanhamento ?? ''));

        if ($token !== '') {
            return $token;
        }

        do {
            $token = Str::lower(Str::random(10));
        } while (DB::table('transporte_escolar.solicitacao')->where('acompanhamento', $token)->exists());

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'acompanhamento' => $token,
            'atualizado_em' => now(),
        ]);

        return $token;
    }

    /**
     * @return array{escola: string, setor: string}
     */
    private function contatos(object $pedido): array
    {
        $gestor = (string) DB::table('pmieducar.escola as e')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes_gestor')
            ->where('e.cod_escola', $pedido->ref_cod_escola)
            ->value('p.nome');
        $usuario = Auth::user();
        $logado = trim((string) ($usuario->name ?? ''));
        $setor = trim((string) DB::table('transporte_escolar.configuracao')->where('chave', 'atendimento_setor')->value('valor'));
        $escola = $gestor !== '' ? $gestor : $logado;
        $setorTexto = trim(($setor !== '' ? $setor : 'Transporte escolar') . ($logado !== '' ? ' · ' . $logado : ''));

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'contato_escola' => $escola !== '' ? $escola : null,
            'contato_setor' => $setorTexto,
            'atualizado_em' => now(),
        ]);

        return ['escola' => $escola, 'setor' => $setorTexto];
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

            if (Schema::hasColumn('transporte_escolar.processo_vaga', 'documentos_exigidos')) {
                $dados['documentos_exigidos'] = HistoricoPedido::gravarExigidos(preg_split('/\r\n|\r|\n/', (string) $request->input('documentos_exigidos')) ?: []);
            }
        }

        $ids = $comGrupo
            ? [$id]
            : DB::table('transporte_escolar.processo_vaga')->where('grupo_id', $id)->pluck('id')->all();
        $pedidos = $ids === [] ? 0 : (int) DB::table('transporte_escolar.solicitacao')->whereIn('processo_id', $ids)->count();
        $motivo = ProcessoEdicao::processo((int) $registro->ano, $ano, $pedidos);

        if ($motivo !== null) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', $motivo);
        }

        DB::table($tabela)->where('id', $id)->update($dados);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Dados atualizados.');
    }

    private function mudarSituacao(string $tabela, int $id, string $acao): RedirectResponse
    {
        if (VisaoRede::escolas() !== null) {
            abort(403, 'Só o perfil institucional pausa o processo de vagas.');
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

    /**
     * @param list<int> $series
     * @param list<int> $turmas
     * @return array{ok: bool, series: list<int>, turmas: list<int>}
     */
    private function recortePedido(Request $request, array $series, array $turmas): array
    {
        $recorte = (string) $request->input('recorte', 'qualquer');

        if ($recorte === 'qualquer') {
            return ['ok' => true, 'series' => [], 'turmas' => []];
        }

        if ($recorte === 'serie') {
            return ['ok' => $series !== [], 'series' => $series, 'turmas' => []];
        }

        if ($recorte === 'turma') {
            return ['ok' => $turmas !== [], 'series' => [], 'turmas' => $turmas];
        }

        return ['ok' => false, 'series' => $series, 'turmas' => $turmas];
    }

    /**
     * @param list<int> $series
     * @param list<int> $turmas
     */
    private function recorteCabe(int $rotaId, int $ano, array $series, array $turmas): bool
    {
        if ($series === [] && $turmas === []) {
            return true;
        }

        $escolas = DB::table('transporte_escolar.rota_escola')->where('rota_id', $rotaId)->pluck('ref_cod_escola')->all();

        if ($escolas === [] || $ano < 2000) {
            return false;
        }

        $mapa = [];

        foreach (DB::table('pmieducar.turma')->where('ativo', 1)->where('ano', $ano)->whereIn('ref_ref_cod_escola', $escolas)->get(['cod_turma', 'ref_ref_cod_serie']) as $linha) {
            $mapa[(int) $linha->cod_turma] = (int) $linha->ref_ref_cod_serie;
        }

        return RegraOferta::recorteCabe($series, $turmas, $mapa);
    }
}
