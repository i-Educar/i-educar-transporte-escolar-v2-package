<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\PedidoFamilia;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
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

        return view('transporte-escolar::portal.pedir', [
            'ofertas' => $this->ofertasAbertas(),
            'erros' => [],
            'velho' => [],
        ]);
    }

    public function gravar(Request $request): View|RedirectResponse
    {
        $this->municipio();
        $dados = $request->only(['nome_aluno', 'nascimento', 'nome_responsavel', 'telefone', 'endereco', 'oferta_id']);
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

            $dados['escola'] = $oferta->escola;
            $dados['turno'] = $oferta->turno;
            $dados['ref_cod_escola'] = $oferta->ref_cod_escola;
        }

        $erros = array_merge($erros, PedidoFamilia::erros($dados));

        if ($erros !== []) {
            return view('transporte-escolar::portal.pedir', [
                'ofertas' => $ofertas,
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
            'aviso' => null,
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

        return view('transporte-escolar::portal.protocolo', [
            'novo' => null,
            'pedido' => $resposta === 'ok' ? $pedido : null,
            'atendimento' => $this->atendimento(),
            'aviso' => match ($resposta) {
                'invalido' => 'Este protocolo não está completo. Confira os traços e o último número.',
                'nao_encontrado' => 'Não encontramos um pedido com este protocolo e esta data de nascimento.',
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

        if ($pedido === null || !ProtocoloSolicitacao::mesmaData($pedido->nascimento, $nascimento)) {
            return redirect('/transporte-escolar/familia/protocolo')->with('erro', 'Não encontramos um pedido com este protocolo e esta data de nascimento.');
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
            ->where('p.ativo', true)
            ->whereDate('p.inicio', '<=', $hoje)
            ->whereDate('p.fim', '>=', $hoje)
            ->orderBy('p.ano')
            ->orderBy('pes.nome')
            ->orderBy('r.descricao')
            ->get([
                'o.id',
                'o.vagas',
                'o.turno',
                'o.ref_cod_escola',
                'o.rota_id',
                'o.processo_id',
                'p.nome as processo',
                'p.ano',
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
