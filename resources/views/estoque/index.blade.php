@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-cubes" aria-hidden="true"></i> Estoque da frota</h1>
    <p class="lead">Só peça e insumo do ônibus: pneu, filtro, óleo e pastilha. A baixa sai na ordem de serviço. A contagem explica a diferença com o depósito.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Tipo
                    <select name="tipo" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($tipos as $codigo => $rotulo)
                            <option value="{{ $codigo }}" @selected($tipo === $codigo)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar item ou lote"></label>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="novo-item"><i class="fa fa-plus" aria-hidden="true"></i> Item</button>
                <button type="button" class="botao claro" data-abre-modal="nova-entrada"><i class="fa fa-plus" aria-hidden="true"></i> Entrada</button>
            @endif
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Item</th><th>Tipo</th><th>Saldo</th><th>Mínimo</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    @forelse($itens as $item)
                        @php $saldo = $saldos[$item->id] ?? 0; @endphp
                        <tr data-busca="{{ $item->nome }} {{ $tipos[$item->tipo] ?? $item->tipo }}">
                            <td>{{ $item->nome }}</td>
                            <td>{{ $tipos[$item->tipo] ?? $item->tipo }}</td>
                            <td>{{ number_format($saldo, 3, ',', '.') }} {{ $item->unidade }}</td>
                            <td>{{ number_format((float) $item->minimo, 3, ',', '.') }}</td>
                            <td><span class="selo {{ !$item->ativo ? 'alerta' : ($saldo < (float) $item->minimo ? 'prazo' : 'ok') }}">{{ !$item->ativo ? 'Inativo' : ($saldo < (float) $item->minimo ? 'Abaixo do mínimo' : 'No mínimo') }}</span></td>
                            <td class="acoes">@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-item-{{ $item->id }}" aria-label="Editar item" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhum item neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2>Lotes</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Item</th><th>Lote</th><th>Quantidade</th><th>Validade</th><th></th></tr></thead>
                <tbody>
                    @forelse($lotes as $lote)
                        <tr data-busca="{{ $lote->nome }} {{ $lote->codigo }}">
                            <td>{{ $lote->nome }}</td>
                            <td>{{ $lote->codigo }}</td>
                            <td>{{ number_format((float) $lote->quantidade, 3, ',', '.') }} {{ $lote->unidade }}</td>
                            <td>{{ $lote->validade ? \Illuminate\Support\Carbon::parse($lote->validade)->format('d/m/Y') : '—' }}</td>
                            <td class="acoes">
                                @if($podeGravar)
                                    <button type="button" class="icone editar" data-abre-modal="baixa-{{ $lote->id }}" aria-label="Baixar lote" title="Baixar na ordem"><i class="fa fa-wrench" aria-hidden="true"></i></button>
                                    <button type="button" class="icone ver" data-abre-modal="contar-{{ $lote->id }}" aria-label="Contar lote" title="Contagem"><i class="fa fa-balance-scale" aria-hidden="true"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhum lote neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2>Últimos movimentos</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Quando</th><th>Item</th><th>Lote</th><th>Movimento</th><th>Antes</th><th>Depois</th><th>Motivo</th></tr></thead>
                <tbody>
                    @forelse($movimentos as $movimento)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($movimento->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $movimento->nome }}</td>
                            <td>{{ $movimento->codigo }}</td>
                            <td>{{ $movimento->tipo === 'contagem' ? 'Contagem' : ($movimento->tipo === 'baixa' ? 'Baixa' : 'Entrada') }}</td>
                            <td>{{ number_format((float) $movimento->saldo_anterior, 3, ',', '.') }}</td>
                            <td>{{ number_format((float) $movimento->saldo_novo, 3, ',', '.') }}</td>
                            <td>{{ $movimento->motivo ?: ($movimento->ordem_id ? 'Ordem ' . $movimento->ordem_id : '—') }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="7" class="vazio">Ainda não há movimento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($podeGravar)
        <dialog class="modal" id="novo-item">
            <div class="modal-cabeca"><h2>Novo item</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/estoque/item') }}">
                @csrf
                @include('transporte-escolar::estoque.campos')
                <button type="submit">Gravar</button>
            </form>
        </dialog>
        <dialog class="modal" id="nova-entrada">
            <div class="modal-cabeca"><h2>Entrada de lote</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/estoque/entrada') }}">
                @csrf
                <p><label>Item <select name="item_id" required>@foreach($itens as $item)<option value="{{ $item->id }}">{{ $item->nome }}</option>@endforeach</select></label></p>
                <div class="linha"><label>Lote <input name="codigo" required></label><label>Quantidade <input name="quantidade" type="number" min="0.001" step="0.001" required></label></div>
                <p><label>Validade <input type="date" name="validade"></label></p>
                <button type="submit">Lançar entrada</button>
            </form>
        </dialog>
        @foreach($itens as $item)
            <dialog class="modal" id="editar-item-{{ $item->id }}">
                <div class="modal-cabeca"><h2>Editar item</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/estoque/item/'.$item->id) }}">
                    @csrf
                    @include('transporte-escolar::estoque.campos', ['item' => $item])
                    <p><label class="opcao"><input type="checkbox" name="ativo" value="1" @checked($item->ativo)> Ativo</label></p>
                    <button type="submit">Gravar</button>
                </form>
            </dialog>
        @endforeach
        @foreach($lotes as $lote)
            <dialog class="modal" id="baixa-{{ $lote->id }}">
                <div class="modal-cabeca"><h2>Baixa na ordem</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/estoque/baixa') }}">
                    @csrf
                    <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                    <p class="lead">{{ $lote->nome }} · lote {{ $lote->codigo }}</p>
                    <p><label>Quantidade <input name="quantidade" type="number" min="0.001" step="0.001" required></label></p>
                    <p><label>Ordem <select name="ordem_id" required>@foreach($ordens as $ordem)<option value="{{ $ordem->id }}">{{ $ordem->placa }} · {{ $ordem->descricao }}</option>@endforeach</select></label></p>
                    <p><label>Veículo <select name="veiculo_id" required>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label></p>
                    <p><label>Motivo <input name="motivo"></label></p>
                    <button type="submit">Baixar</button>
                </form>
            </dialog>
            <dialog class="modal" id="contar-{{ $lote->id }}">
                <div class="modal-cabeca"><h2>Contagem física</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/estoque/contagem') }}">
                    @csrf
                    <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                    <p class="lead">O sistema tem {{ number_format((float) $lote->quantidade, 3, ',', '.') }}. Informe o que foi contado.</p>
                    <p><label>Quantidade contada <input name="quantidade" type="number" min="0" step="0.001" value="{{ $lote->quantidade }}" required></label></p>
                    <p><label>Motivo da diferença <input name="motivo" required></label></p>
                    <button type="submit">Gravar contagem</button>
                </form>
            </dialog>
        @endforeach
    @endif
@endsection
