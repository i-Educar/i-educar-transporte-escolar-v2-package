<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\ComprovantePedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\NomeExibicao;
use iEducar\Packages\BuritiTransporteEscolar\Support\PedidoFamilia;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
use iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProtocoloSolicitacao;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamiliaPortalController
{
    public function inicio(): View
    {
        $this->municipio();

        return view('transporte-escolar::portal.inicio');
    }

    public function pedir(): View
    {
        $this->municipio();

        $ofertas = $this->ofertasAbertas();

        return view('transporte-escolar::portal.pedir', [
            'ofertas' => $ofertas,
            'series' => $this->seriesDas($ofertas),
            'turmas' => $this->turmasDas($ofertas),
            'erros' => [],
            'velho' => [],
        ]);
    }

    public function gravar(Request $request): View|RedirectResponse
    {
        $this->municipio();
        $dados = $request->only(['nome_aluno', 'nascimento', 'nome_responsavel', 'telefone', 'endereco', 'oferta_id', 'serie_id', 'turma_id']);
        $ofertas = $this->ofertasAbertas();
        $oferta = $ofertas->firstWhere('id', (int) ($dados['oferta_id'] ?? 0));
        $erros = [];

        if ($oferta === null) {
            $erros[] = 'Escolha um processo aberto, com escola e rota que ainda tenham vaga.';
            $dados['escola'] = 'processo';
            $dados['turno'] = 'manha';
        } else {
            $ocupadas = (int) DB::table('transporte_escolar.solicitacao')
                ->where('oferta_id', $oferta->id)
                ->whereIn('situacao', ['recebida', 'em_analise', 'deferida'])
                ->count();

            if (!ProcessoVaga::cabe((int) $oferta->vagas, $ocupadas)) {
                $erros[] = 'Esta rota não tem mais vaga neste processo.';
            }

            $serieId = (int) ($dados['serie_id'] ?? 0) ?: null;
            $turmaId = (int) ($dados['turma_id'] ?? 0) ?: null;
            $idade = RegraOferta::idade((string) ($dados['nascimento'] ?? ''), (int) $oferta->ano);

            if (!RegraOferta::cabeIdade($idade, $oferta->idade_min === null ? null : (int) $oferta->idade_min, $oferta->idade_max === null ? null : (int) $oferta->idade_max)) {
                $erros[] = 'A idade do aluno em 31 de março deste ano não cabe nesta vaga.';
            }

            if (!RegraOferta::cabeLista($serieId, RegraOferta::lista($oferta->series))) {
                $erros[] = 'Escolha uma série permitida nesta vaga.';
            }

            if (!RegraOferta::cabeLista($turmaId, RegraOferta::lista($oferta->turmas))) {
                $erros[] = 'Escolha uma turma permitida nesta vaga.';
            }

            $dados['escola'] = $oferta->escola;
            $dados['turno'] = $oferta->turno;
            $dados['ref_cod_escola'] = $oferta->ref_cod_escola;
        }

        $erros = array_merge($erros, PedidoFamilia::erros($dados));

        if ($erros !== []) {
            return view('transporte-escolar::portal.pedir', [
                'ofertas' => $ofertas,
                'series' => $this->seriesDas($ofertas),
                'turmas' => $this->turmasDas($ofertas),
                'erros' => $erros,
                'velho' => $dados,
            ]);
        }

        $ano = (int) $oferta->ano;
        $sequencia = (int) DB::table('transporte_escolar.solicitacao')->where('ano', $ano)->max('sequencia') + 1;
        $protocolo = ProtocoloSolicitacao::montar($ano, $sequencia);

        DB::table('transporte_escolar.solicitacao')->insert([
            'ano' => $ano,
            'sequencia' => $sequencia,
            'protocolo' => $protocolo,
            'nome_aluno' => trim((string) $dados['nome_aluno']),
            'nascimento' => $dados['nascimento'],
            'nome_responsavel' => trim((string) $dados['nome_responsavel']),
            'telefone' => preg_replace('/\D+/', '', (string) $dados['telefone']),
            'endereco' => trim((string) $dados['endereco']),
            'turno' => $oferta->turno,
            'escola_informada' => $oferta->escola,
            'ref_cod_escola' => $oferta->ref_cod_escola,
            'processo_id' => $oferta->processo_id,
            'rota_id' => $oferta->rota_id,
            'oferta_id' => $oferta->id,
            'ref_cod_serie' => (int) ($dados['serie_id'] ?? 0) ?: null,
            'ref_cod_turma' => (int) ($dados['turma_id'] ?? 0) ?: null,
            'situacao' => SituacaoPedido::RECEBIDA,
            'pedido_em' => now(),
            'atualizado_em' => now(),
        ]);

        return redirect('/transporte-escolar/familia/protocolo')->with('protocolo_novo', $protocolo);
    }

    public function protocolo(): View
    {
        $this->municipio();

        return view('transporte-escolar::portal.protocolo', [
            'novo' => session('protocolo_novo'),
            'pedido' => null,
            'resumo' => null,
            'aviso' => null,
            'token' => null,
            'atendimento' => $this->atendimento(),
        ]);
    }

    public function acompanhar(string $token): View
    {
        $this->municipio();
        $pedido = DB::table('transporte_escolar.solicitacao')->where('acompanhamento', $token)->first();

        return view('transporte-escolar::portal.protocolo', [
            'novo' => null,
            'pedido' => null,
            'resumo' => $pedido === null ? null : $this->publico($pedido),
            'token' => $token,
            'aviso' => $pedido === null ? 'Não encontramos este acompanhamento.' : null,
            'atendimento' => $this->atendimento(),
        ]);
    }

    public function confirmar(Request $request, string $token): View
    {
        $this->municipio();
        $pedido = DB::table('transporte_escolar.solicitacao')->where('acompanhamento', $token)->first();
        $liberado = $pedido !== null
            && ProtocoloSolicitacao::mesmaData($pedido->nascimento, (string) $request->input('nascimento'))
            && ComprovantePedido::telefone((string) $pedido->telefone, (string) $request->input('telefone'));

        return view('transporte-escolar::portal.protocolo', [
            'novo' => null,
            'pedido' => $liberado ? $this->publico($pedido) : null,
            'resumo' => $pedido === null ? null : $this->publico($pedido),
            'token' => $token,
            'aviso' => $liberado ? null : 'A data de nascimento ou os 4 últimos números do telefone não conferem.',
            'atendimento' => $this->atendimento(),
        ]);
    }

    public function consultar(Request $request): View
    {
        $this->municipio();
        $protocolo = strtoupper(trim((string) $request->input('protocolo')));
        $nascimento = (string) $request->input('nascimento');
        $formatoOk = ProtocoloSolicitacao::conferir($protocolo);
        $pedido = $formatoOk
            ? DB::table('transporte_escolar.solicitacao as s')
                ->leftJoin('transporte_escolar.rota as r', 'r.id', '=', 's.rota_id')
                ->leftJoin('transporte_escolar.processo_vaga as p', 'p.id', '=', 's.processo_id')
                ->where('s.protocolo', $protocolo)
                ->first(['s.*', 'r.descricao as rota_nome', 'p.nome as processo_nome'])
            : null;
        $resposta = ProtocoloSolicitacao::respostaConsulta(
            $formatoOk,
            $pedido !== null,
            $pedido !== null && ProtocoloSolicitacao::mesmaData($pedido->nascimento, $nascimento),
        );
        $telefoneOk = $pedido !== null && ComprovantePedido::telefone((string) $pedido->telefone, (string) $request->input('telefone'));
        $liberado = $resposta === 'ok' && $telefoneOk;

        return view('transporte-escolar::portal.protocolo', [
            'novo' => null,
            'pedido' => $liberado ? $this->publico($pedido) : null,
            'resumo' => null,
            'token' => null,
            'atendimento' => $this->atendimento(),
            'aviso' => match (true) {
                $resposta === 'invalido' => 'Este protocolo não está completo. Confira os traços e o último número.',
                !$liberado => 'Não encontramos um pedido com este protocolo, esta data de nascimento e estes 4 números do telefone.',
                default => null,
            },
        ]);
    }

    public function cancelar(Request $request): RedirectResponse
    {
        $this->municipio();
        $protocolo = strtoupper(trim((string) $request->input('protocolo')));
        $nascimento = (string) $request->input('nascimento');
        $pedido = ProtocoloSolicitacao::conferir($protocolo)
            ? DB::table('transporte_escolar.solicitacao')->where('protocolo', $protocolo)->first()
            : null;

        if ($pedido === null || !ProtocoloSolicitacao::mesmaData($pedido->nascimento, $nascimento) || !ComprovantePedido::telefone((string) $pedido->telefone, (string) $request->input('telefone'))) {
            return redirect('/transporte-escolar/familia/protocolo')->with('erro', 'Não encontramos um pedido com este protocolo, esta data de nascimento e estes 4 números do telefone.');
        }

        $proxima = SituacaoPedido::mover($pedido->situacao, 'cancelar', 'familia');

        if ($proxima === null) {
            return redirect('/transporte-escolar/familia/protocolo')->with('erro', 'Este pedido já foi decidido e não pode ser cancelado por aqui.');
        }

        DB::table('transporte_escolar.solicitacao')->where('id', $pedido->id)->update([
            'situacao' => $proxima,
            'atualizado_em' => now(),
        ]);

        return redirect('/transporte-escolar/familia/protocolo')->with('ok', 'Pedido cancelado. Guarde o protocolo ' . $protocolo . '.');
    }

    private function publico(object $pedido): object
    {
        $pedido->aluno_publico = NomeExibicao::exibir((string) $pedido->nome_aluno, false);
        $pedido->escola_publica = NomeExibicao::exibir((string) $pedido->escola_informada, false);

        if (trim((string) ($pedido->contato_escola ?? '')) === '') {
            $pedido->contato_escola = (string) DB::table('pmieducar.escola as e')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes_gestor')
                ->where('e.cod_escola', $pedido->ref_cod_escola)
                ->value('p.nome');
        }

        if (trim((string) ($pedido->contato_setor ?? '')) === '') {
            $pedido->contato_setor = trim((string) DB::table('transporte_escolar.configuracao')->where('chave', 'atendimento_setor')->value('valor')) ?: 'Transporte escolar';
        }

        return $pedido;
    }

    /**
     * @return array{setor: string, endereco: string, documentos: string, horario: string, mapa: ?array}
     */
    private function atendimento(): array
    {
        $linhas = DB::table('transporte_escolar.configuracao')
            ->whereIn('chave', [
                'atendimento_setor',
                'atendimento_endereco',
                'atendimento_documentos',
                'atendimento_horario',
                'atendimento_latitude',
                'atendimento_longitude',
            ])
            ->pluck('valor', 'chave');

        return [
            'setor' => trim((string) ($linhas['atendimento_setor'] ?? '')) ?: 'Transporte Escolar',
            'endereco' => trim((string) ($linhas['atendimento_endereco'] ?? '')),
            'documentos' => trim((string) ($linhas['atendimento_documentos'] ?? '')),
            'horario' => trim((string) ($linhas['atendimento_horario'] ?? '')),
            'mapa' => AtendimentoSetor::mapa($linhas['atendimento_latitude'] ?? null, $linhas['atendimento_longitude'] ?? null),
        ];
    }

    private function ofertasAbertas()
    {
        $hoje = now()->toDateString();
        $ofertas = DB::table('transporte_escolar.processo_vaga_oferta as o')
            ->join('transporte_escolar.processo_vaga as p', 'p.id', '=', 'o.processo_id')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'o.rota_id')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 'o.ref_cod_escola')
            ->join('cadastro.pessoa as pes', 'pes.idpes', '=', 'e.ref_idpes')
            ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
            ->where('p.ativo', true)
            ->where('p.situacao', ProcessoVaga::ABERTO)
            ->where(function ($oferta) {
                $oferta->where('o.situacao', ProcessoVaga::ABERTO)->orWhereNull('o.situacao');
            })
            ->whereDate('p.inicio', '<=', $hoje)
            ->whereDate('p.fim', '>=', $hoje)
            ->where(function ($consulta) use ($hoje) {
                $consulta->whereNull('p.grupo_id')
                    ->orWhere(function ($grupo) use ($hoje) {
                        $grupo->where('g.ativo', true)
                            ->where('g.situacao', ProcessoVaga::ABERTO)
                            ->whereDate('g.inicio', '<=', $hoje)
                            ->whereDate('g.fim', '>=', $hoje);
                    });
            })
            ->orderBy('p.ano')
            ->orderBy('pes.nome')
            ->orderBy('r.descricao')
            ->get([
                'o.id',
                'o.vagas',
                'o.turno',
                'o.idade_min',
                'o.idade_max',
                'o.series',
                'o.turmas',
                'o.ref_cod_escola',
                'o.rota_id',
                'o.processo_id',
                'p.nome as processo',
                'p.ano',
                'p.grupo_id',
                'g.nome as grupo',
                'r.descricao as rota',
                'pes.nome as escola',
            ]);

        $ocupadas = DB::table('transporte_escolar.solicitacao')
            ->whereIn('oferta_id', $ofertas->pluck('id')->all() ?: [0])
            ->whereIn('situacao', ['recebida', 'em_analise', 'deferida'])
            ->selectRaw('oferta_id, count(*) as n')
            ->groupBy('oferta_id')
            ->pluck('n', 'oferta_id');

        $mapa = [];

        foreach ($ocupadas as $id => $quantidade) {
            $mapa[(int) $id] = (int) $quantidade;
        }

        return $ofertas->map(function ($item) use ($mapa) {
            $item->livres = max(0, (int) $item->vagas - ($mapa[(int) $item->id] ?? 0));

            return $item;
        })->filter(fn ($item) => $item->livres > 0)->values();
    }

    private function seriesDas($ofertas)
    {
        $ids = [];

        foreach ($ofertas as $oferta) {
            foreach (RegraOferta::lista($oferta->series) as $id) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return collect();
        }

        return DB::table('pmieducar.serie')->whereIn('cod_serie', array_values($ids))->orderBy('nm_serie')->get(['cod_serie', 'nm_serie']);
    }

    private function turmasDas($ofertas)
    {
        $ids = [];

        foreach ($ofertas as $oferta) {
            foreach (RegraOferta::lista($oferta->turmas) as $id) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return collect();
        }

        return DB::table('pmieducar.turma')->whereIn('cod_turma', array_values($ids))->orderBy('nm_turma')->get(['cod_turma', 'nm_turma', 'ref_ref_cod_escola', 'ano']);
    }

    private function municipio(): void
    {
        if (!\App\Support\Tenancy\PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE)) {
            abort(404);
        }
    }

    private function escolas()
    {
        return DB::table('pmieducar.escola as e')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
            ->where('e.ativo', 1)
            ->orderBy('p.nome')
            ->get(['e.cod_escola', 'p.nome']);
    }

    private function escolaInformada(mixed $id): ?int
    {
        $id = (int) $id;

        if ($id < 1) {
            return null;
        }

        $existe = DB::table('pmieducar.escola')->where('cod_escola', $id)->where('ativo', 1)->exists();

        return $existe ? $id : null;
    }
}
