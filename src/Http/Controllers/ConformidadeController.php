<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\AptidaoViagem;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConformidadeController
{
    public function index(Request $request): View
    {
        $veiculoId = (int) $request->input('veiculo');
        $hoje = now()->toDateString();
        $limite = now()->addDays(30)->toDateString();
        $veiculos = DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(['id', 'placa', 'descricao', 'licenciamento_vencimento']);
        $autorizacoes = DB::table('transporte_escolar.autorizacao_veiculo as a')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'a.veiculo_id')
            ->when($veiculoId > 0, fn ($q) => $q->where('a.veiculo_id', $veiculoId))
            ->orderBy('a.validade')
            ->get(['a.*', 'v.placa', 'v.descricao']);
        $checklists = DB::table('transporte_escolar.checklist_veiculo as c')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'c.veiculo_id')
            ->when($veiculoId > 0, fn ($q) => $q->where('c.veiculo_id', $veiculoId))
            ->orderBy('v.placa')
            ->get(['c.*', 'v.placa']);

        return view('transporte-escolar::conformidade.index', [
            'veiculos' => $veiculos,
            'autorizacoes' => $autorizacoes,
            'checklists' => $checklists,
            'veiculoId' => $veiculoId,
            'hoje' => $hoje,
            'limite' => $limite,
            'itens' => AptidaoViagem::ITENS,
            'podeGravar' => Acesso::permite(Processos::VEICULOS, 'gravar'),
        ]);
    }

    public function autorizacao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'orgao' => ['required', 'in:detran,ciretran'],
            'numero' => ['required', 'string', 'max:40'],
            'validade' => ['required', 'date'],
            'anexo' => ['nullable', 'file', 'max:5120'],
        ]);
        abort_if(DB::table('transporte_escolar.veiculo')->where('id', $dados['veiculo_id'])->doesntExist(), 404);
        $id = DB::table('transporte_escolar.autorizacao_veiculo')->insertGetId([
            'veiculo_id' => $dados['veiculo_id'],
            'orgao' => $dados['orgao'],
            'numero' => trim($dados['numero']),
            'validade' => $dados['validade'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->anexo($request, 'transporte_escolar.autorizacao_veiculo', $id);

        return back()->with('ok', 'Autorização gravada.');
    }

    public function editarAutorizacao(Request $request, int $autorizacao): RedirectResponse
    {
        $linha = DB::table('transporte_escolar.autorizacao_veiculo')->where('id', $autorizacao)->first();
        abort_if($linha === null, 404);
        $dados = $request->validate([
            'orgao' => ['required', 'in:detran,ciretran'],
            'numero' => ['required', 'string', 'max:40'],
            'validade' => ['required', 'date'],
            'anexo' => ['nullable', 'file', 'max:5120'],
        ]);
        DB::table('transporte_escolar.autorizacao_veiculo')->where('id', $autorizacao)->update([
            'orgao' => $dados['orgao'],
            'numero' => trim($dados['numero']),
            'validade' => $dados['validade'],
            'updated_at' => now(),
        ]);
        $this->anexo($request, 'transporte_escolar.autorizacao_veiculo', $autorizacao);

        return back()->with('ok', 'Autorização atualizada.');
    }

    public function checklist(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'item' => ['required', 'in:faixa,cinto,tacografo,identificacao'],
            'observacao' => ['nullable', 'string', 'max:200'],
            'anexo' => ['nullable', 'file', 'max:5120'],
        ]);
        abort_if(DB::table('transporte_escolar.veiculo')->where('id', $dados['veiculo_id'])->doesntExist(), 404);
        DB::table('transporte_escolar.checklist_veiculo')->updateOrInsert(
            ['veiculo_id' => $dados['veiculo_id'], 'item' => $dados['item']],
            [
                'conforme' => $request->boolean('conforme'),
                'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $id = (int) DB::table('transporte_escolar.checklist_veiculo')
            ->where('veiculo_id', $dados['veiculo_id'])
            ->where('item', $dados['item'])
            ->value('id');
        $this->anexo($request, 'transporte_escolar.checklist_veiculo', $id);

        return back()->with('ok', 'Checklist gravado.');
    }

    public function anexoArquivo(string $tabela, int $id): StreamedResponse
    {
        abort_unless(in_array($tabela, ['autorizacao', 'checklist'], true), 404);
        $nome = $tabela === 'autorizacao' ? 'transporte_escolar.autorizacao_veiculo' : 'transporte_escolar.checklist_veiculo';
        $linha = DB::table($nome)->where('id', $id)->first();
        abort_if($linha === null || $linha->anexo_caminho === null || !str_starts_with((string) $linha->anexo_caminho, 'transporte-escolar/conformidade/'), 404);

        return Storage::download($linha->anexo_caminho, $linha->anexo_nome ?: 'anexo');
    }

    private function anexo(Request $request, string $tabela, int $id): void
    {
        if (!$request->hasFile('anexo') || $id <= 0) {
            return;
        }

        $arquivo = $request->file('anexo');
        DB::table($tabela)->where('id', $id)->update([
            'anexo_nome' => $arquivo->getClientOriginalName(),
            'anexo_caminho' => $arquivo->store('transporte-escolar/conformidade'),
            'updated_at' => now(),
        ]);
    }
}
