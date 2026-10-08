<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SolicitacaoController
{
    public function index(): View
    {
        $escolas = VisaoRede::escolas();
        $consulta = DB::table('transporte_escolar.solicitacao')->orderByDesc('id');

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('ref_cod_escola', $escolas);
        }

        $linhas = DB::table('transporte_escolar.configuracao')
            ->where('chave', 'like', 'atendimento_%')
            ->pluck('valor', 'chave');

        return view('transporte-escolar::solicitacoes.index', [
            'pedidos' => $consulta->limit(80)->get(),
            'rede' => $escolas === null,
            'processos' => DB::table('transporte_escolar.processo_vaga')->orderByDesc('ano')->orderBy('nome')->get(),
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

        if (mb_strlen($nome) < 3 || $ano < 2000 || !ProcessoVaga::noPeriodo(true, $inicio, $fim, new \DateTimeImmutable($inicio))) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'Informe nome, ano letivo e um período em que o fim não seja anterior ao início.');
        }

        DB::table('transporte_escolar.processo_vaga')->insert([
            'nome' => $nome,
            'ano' => $ano,
            'inicio' => $inicio,
            'fim' => $fim,
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/solicitacoes')->with('ok', 'Processo de vagas aberto para ' . $ano . '.');
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

        if ($processo === null || $rota === null || (int) $rota->ano !== (int) $processo->ano || !$liga || !isset(Codigos::PERIODO[$turno]) || $vagas < 1) {
            return redirect('/transporte-escolar/solicitacoes')->with('erro', 'A vaga precisa de um processo, uma rota do mesmo ano, uma escola dessa rota e um turno.');
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->updateOrInsert(
            [
                'processo_id' => $processo->id,
                'rota_id' => $rota->id,
                'ref_cod_escola' => $escola,
                'turno' => $turno,
            ],
            ['vagas' => $vagas, 'created_at' => now(), 'updated_at' => now()],
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
}
