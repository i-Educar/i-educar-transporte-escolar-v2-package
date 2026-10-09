<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstoqueController
{
    public function index(Request $request): View
    {
        $tipo = (string) $request->input('tipo', '');

        if (!isset(EstoqueSaldo::TIPOS[$tipo])) {
            $tipo = '';
        }

        $itens = DB::table('transporte_escolar.estoque_item')
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('nome')
            ->get();
        $lotes = DB::table('transporte_escolar.estoque_lote as l')
            ->join('transporte_escolar.estoque_item as i', 'i.id', '=', 'l.item_id')
            ->when($tipo !== '', fn ($q) => $q->where('i.tipo', $tipo))
            ->orderBy('i.nome')
            ->orderBy('l.codigo')
            ->get(['l.*', 'i.nome', 'i.tipo', 'i.unidade', 'i.minimo', 'i.ativo']);
        $saldos = [];

        foreach ($lotes as $lote) {
            $saldos[$lote->item_id] = ($saldos[$lote->item_id] ?? 0) + (float) $lote->quantidade;
        }

        return view('transporte-escolar::estoque.index', [
            'itens' => $itens,
            'lotes' => $lotes,
            'saldos' => $saldos,
            'tipo' => $tipo,
            'tipos' => EstoqueSaldo::TIPOS,
            'veiculos' => DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(['id', 'placa', 'descricao']),
            'ordens' => DB::table('transporte_escolar.ordem_servico as o')
                ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'o.veiculo_id')
                ->orderByDesc('o.id')
                ->limit(80)
                ->get(['o.id', 'o.descricao', 'v.placa']),
            'movimentos' => DB::table('transporte_escolar.estoque_movimento as m')
                ->join('transporte_escolar.estoque_lote as l', 'l.id', '=', 'm.lote_id')
                ->join('transporte_escolar.estoque_item as i', 'i.id', '=', 'l.item_id')
                ->orderByDesc('m.id')
                ->limit(40)
                ->get(['m.*', 'l.codigo', 'i.nome']),
            'podeGravar' => Acesso::permite(Processos::GASTOS, 'gravar'),
        ]);
    }

    public function item(Request $request): RedirectResponse
    {
        $dados = $this->dadosItem($request);
        DB::table('transporte_escolar.estoque_item')->insert($dados + [
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Item incluído no depósito da frota.');
    }

    public function editarItem(Request $request, int $item): RedirectResponse
    {
        abort_if(DB::table('transporte_escolar.estoque_item')->where('id', $item)->doesntExist(), 404);
        DB::table('transporte_escolar.estoque_item')->where('id', $item)->update($this->dadosItem($request) + [
            'ativo' => $request->boolean('ativo'),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Item atualizado.');
    }

    public function entrada(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'item_id' => ['required', 'integer'],
            'codigo' => ['required', 'string', 'max:40'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'validade' => ['nullable', 'date'],
        ]);
        abort_if(DB::table('transporte_escolar.estoque_item')->where('id', $dados['item_id'])->doesntExist(), 404);
        $codigo = trim($dados['codigo']);
        $lote = DB::table('transporte_escolar.estoque_lote')->where('item_id', $dados['item_id'])->where('codigo', $codigo)->first();
        $saldo = $lote === null ? 0.0 : (float) $lote->quantidade;
        $novo = EstoqueSaldo::aplicar($saldo, 'entrada', (float) $dados['quantidade']);

        if ($novo === null) {
            return back()->with('erro', 'A entrada precisa de uma quantidade maior que zero.');
        }

        if ($lote === null) {
            $loteId = DB::table('transporte_escolar.estoque_lote')->insertGetId([
                'item_id' => $dados['item_id'],
                'codigo' => $codigo,
                'quantidade' => $novo,
                'validade' => $dados['validade'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $loteId = (int) $lote->id;
            DB::table('transporte_escolar.estoque_lote')->where('id', $loteId)->update([
                'quantidade' => $novo,
                'validade' => $dados['validade'] ?? $lote->validade,
                'updated_at' => now(),
            ]);
        }

        $this->movimento($loteId, 'entrada', (float) $dados['quantidade'], $saldo, $novo, null, null, null);

        return back()->with('ok', 'Entrada lançada no lote ' . $codigo . '.');
    }

    public function baixa(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'lote_id' => ['required', 'integer'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'ordem_id' => ['required', 'integer'],
            'veiculo_id' => ['required', 'integer'],
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);
        $lote = DB::table('transporte_escolar.estoque_lote')->where('id', $dados['lote_id'])->first();
        abort_if($lote === null, 404);
        abort_if(DB::table('transporte_escolar.ordem_servico')->where('id', $dados['ordem_id'])->doesntExist(), 404);
        abort_if(DB::table('transporte_escolar.veiculo')->where('id', $dados['veiculo_id'])->doesntExist(), 404);
        $novo = EstoqueSaldo::aplicar((float) $lote->quantidade, 'baixa', (float) $dados['quantidade']);

        if ($novo === null) {
            return back()->with('erro', 'A baixa não pode deixar o lote negativo.');
        }

        DB::table('transporte_escolar.estoque_lote')->where('id', $lote->id)->update([
            'quantidade' => $novo,
            'updated_at' => now(),
        ]);
        $this->movimento((int) $lote->id, 'baixa', (float) $dados['quantidade'], (float) $lote->quantidade, $novo, (int) $dados['ordem_id'], (int) $dados['veiculo_id'], trim((string) ($dados['motivo'] ?? '')) ?: null);

        return back()->with('ok', 'Peça baixada na ordem de serviço.');
    }

    public function contagem(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'lote_id' => ['required', 'integer'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'motivo' => ['required', 'string', 'max:200'],
        ]);
        $lote = DB::table('transporte_escolar.estoque_lote')->where('id', $dados['lote_id'])->first();
        abort_if($lote === null, 404);
        $novo = EstoqueSaldo::aplicar((float) $lote->quantidade, 'contagem', (float) $dados['quantidade']);

        if ($novo === null) {
            return back()->with('erro', 'A contagem precisa de um número zero ou maior.');
        }

        DB::table('transporte_escolar.estoque_lote')->where('id', $lote->id)->update([
            'quantidade' => $novo,
            'updated_at' => now(),
        ]);
        $this->movimento((int) $lote->id, 'contagem', EstoqueSaldo::diferenca($novo, (float) $lote->quantidade), (float) $lote->quantidade, $novo, null, null, trim($dados['motivo']));

        return back()->with('ok', 'Contagem gravada. A diferença ficou no movimento.');
    }

    /**
     * @return array{tipo: string, nome: string, unidade: string, minimo: float}
     */
    private function dadosItem(Request $request): array
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:pneu,filtro,oleo,pastilha'],
            'nome' => ['required', 'string', 'max:120'],
            'unidade' => ['required', 'string', 'max:20'],
            'minimo' => ['required', 'numeric', 'min:0'],
        ]);

        return [
            'tipo' => $dados['tipo'],
            'nome' => trim($dados['nome']),
            'unidade' => trim($dados['unidade']),
            'minimo' => (float) $dados['minimo'],
        ];
    }

    private function movimento(int $loteId, string $tipo, float $quantidade, float $antes, float $depois, ?int $ordemId, ?int $veiculoId, ?string $motivo): void
    {
        DB::table('transporte_escolar.estoque_movimento')->insert([
            'lote_id' => $loteId,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'saldo_anterior' => $antes,
            'saldo_novo' => $depois,
            'ordem_id' => $ordemId,
            'veiculo_id' => $veiculoId,
            'motivo' => $motivo,
            'created_at' => now(),
        ]);
    }
}
