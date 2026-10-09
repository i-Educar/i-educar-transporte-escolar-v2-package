<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento;
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
        $porItem = [];
        $hoje = now()->toDateString();
        $limite = now()->addDays(30)->toDateString();
        $resumo = ['abaixo' => 0, 'zerado' => 0, 'vencer' => 0, 'vencido' => 0];

        foreach ($lotes as $lote) {
            $quantidade = (float) $lote->quantidade;
            $saldos[$lote->item_id] = ($saldos[$lote->item_id] ?? 0) + $quantidade;
            $porItem[$lote->item_id][] = ['quantidade' => $quantidade, 'validade' => $lote->validade];

            if ($quantidade > 0 && $lote->validade !== null && $lote->validade < $hoje) {
                $resumo['vencido']++;
            } elseif ($quantidade > 0 && $lote->validade !== null && $lote->validade <= $limite) {
                $resumo['vencer']++;
            }
        }

        $primeira = [];

        foreach ($porItem as $itemId => $lista) {
            $primeira[$itemId] = EstoqueSaldo::primeiraValidade($lista);
        }

        foreach ($itens as $item) {
            $faixa = EstoqueSaldo::faixa((float) ($saldos[$item->id] ?? 0), (float) $item->minimo, (bool) $item->ativo);

            if ($faixa === 'abaixo') {
                $resumo['abaixo']++;
            } elseif ($faixa === 'zerado') {
                $resumo['zerado']++;
            }
        }

        return view('transporte-escolar::estoque.index', [
            'itens' => $itens,
            'lotes' => $lotes,
            'saldos' => $saldos,
            'primeira' => $primeira,
            'hoje' => $hoje,
            'resumo' => $resumo,
            'tipo' => $tipo,
            'tipos' => EstoqueSaldo::TIPOS,
            'faixas' => EstoqueSaldo::FAIXAS,
            'ordens' => DB::table('transporte_escolar.ordem_servico as o')
                ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'o.veiculo_id')
                ->whereIn('o.situacao', ['solicitada', 'autorizada', 'em_execucao'])
                ->orderByDesc('o.id')
                ->limit(80)
                ->get(['o.id', 'o.descricao', 'o.protocolo', 'o.situacao', 'v.placa']),
            'movimentos' => DB::table('transporte_escolar.estoque_movimento as m')
                ->join('transporte_escolar.estoque_lote as l', 'l.id', '=', 'm.lote_id')
                ->join('transporte_escolar.estoque_item as i', 'i.id', '=', 'l.item_id')
                ->leftJoin('transporte_escolar.veiculo as v', 'v.id', '=', 'm.veiculo_id')
                ->leftJoin('transporte_escolar.ordem_servico as o', 'o.id', '=', 'm.ordem_id')
                ->when($tipo !== '', fn ($q) => $q->where('i.tipo', $tipo))
                ->orderByDesc('m.id')
                ->limit(40)
                ->get(['m.*', 'l.codigo', 'i.nome', 'i.unidade', 'v.placa', 'o.protocolo']),
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
        $item = DB::table('transporte_escolar.estoque_item')->where('id', $dados['item_id'])->first();
        abort_if($item === null, 404);

        if (!$item->ativo) {
            return back()->with('erro', 'Item inativo não recebe entrada.');
        }

        if (!EstoqueSaldo::inteiro((string) $item->tipo, (float) $dados['quantidade'])) {
            return back()->with('erro', 'Pneu, filtro e pastilha entram por unidade inteira. Óleo pode ter fração.');
        }

        if ($item->tipo === 'oleo' && empty($dados['validade'])) {
            return back()->with('erro', 'Óleo entra com a validade do lote.');
        }
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
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);
        $lote = DB::table('transporte_escolar.estoque_lote as l')
            ->join('transporte_escolar.estoque_item as i', 'i.id', '=', 'l.item_id')
            ->where('l.id', $dados['lote_id'])
            ->first(['l.*', 'i.tipo', 'i.ativo', 'i.nome']);
        abort_if($lote === null, 404);

        if (!$lote->ativo) {
            return back()->with('erro', 'Item inativo não sai do depósito.');
        }

        if (!EstoqueSaldo::inteiro((string) $lote->tipo, (float) $dados['quantidade'])) {
            return back()->with('erro', 'Pneu, filtro e pastilha saem por unidade inteira.');
        }

        $ordem = DB::table('transporte_escolar.ordem_servico')->where('id', $dados['ordem_id'])->first();
        abort_if($ordem === null, 404);

        if (!isset(SituacaoDocumento::ORDEM[$ordem->situacao]) || SituacaoDocumento::ORDEM[$ordem->situacao] === []) {
            return back()->with('erro', 'A peça só sai numa ordem solicitada, autorizada ou em execução.');
        }

        $irmas = DB::table('transporte_escolar.estoque_lote')->where('item_id', $lote->item_id)->get(['quantidade', 'validade']);
        $recusa = EstoqueSaldo::recusaBaixa(
            $lote->validade,
            (float) $lote->quantidade,
            EstoqueSaldo::primeiraValidade($irmas->map(fn ($linha) => ['quantidade' => (float) $linha->quantidade, 'validade' => $linha->validade])->all()),
            now()->toDateString(),
        );

        if ($recusa === 'vencido') {
            return back()->with('erro', 'Lote vencido não vai para o veículo.');
        }

        if ($recusa === 'sem_saldo') {
            return back()->with('erro', 'Esse lote já está zerado.');
        }

        if ($recusa === 'fefo') {
            return back()->with('erro', 'Há um lote do mesmo item com validade mais próxima. Use esse primeiro.');
        }

        $novo = EstoqueSaldo::aplicar((float) $lote->quantidade, 'baixa', (float) $dados['quantidade']);

        if ($novo === null) {
            return back()->with('erro', 'A baixa não pode deixar o lote negativo.');
        }

        DB::table('transporte_escolar.estoque_lote')->where('id', $lote->id)->update([
            'quantidade' => $novo,
            'updated_at' => now(),
        ]);
        $this->movimento((int) $lote->id, 'baixa', (float) $dados['quantidade'], (float) $lote->quantidade, $novo, (int) $ordem->id, (int) $ordem->veiculo_id, trim((string) ($dados['motivo'] ?? '')) ?: null);

        return back()->with('ok', 'Peça baixada na ordem de serviço.');
    }

    public function contagem(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'lote_id' => ['required', 'integer'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);
        $lote = DB::table('transporte_escolar.estoque_lote as l')
            ->join('transporte_escolar.estoque_item as i', 'i.id', '=', 'l.item_id')
            ->where('l.id', $dados['lote_id'])
            ->first(['l.*', 'i.tipo']);
        abort_if($lote === null, 404);

        if (!EstoqueSaldo::inteiro((string) $lote->tipo, (float) $dados['quantidade'])) {
            return back()->with('erro', 'A contagem de pneu, filtro e pastilha é em unidade inteira.');
        }

        $novo = EstoqueSaldo::aplicar((float) $lote->quantidade, 'contagem', (float) $dados['quantidade']);

        if ($novo === null) {
            return back()->with('erro', 'A contagem precisa de um número zero ou maior.');
        }

        $diferenca = EstoqueSaldo::diferenca($novo, (float) $lote->quantidade);
        $motivo = trim((string) ($dados['motivo'] ?? ''));

        if ($diferenca != 0.0 && $motivo === '') {
            return back()->with('erro', 'Conferência diferente do sistema precisa do motivo.');
        }

        DB::table('transporte_escolar.estoque_lote')->where('id', $lote->id)->update([
            'quantidade' => $novo,
            'updated_at' => now(),
        ]);
        $this->movimento((int) $lote->id, 'contagem', $diferenca, (float) $lote->quantidade, $novo, null, null, $motivo !== '' ? $motivo : null);

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
