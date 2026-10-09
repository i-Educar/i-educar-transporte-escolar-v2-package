@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-cubes" aria-hidden="true"></i> Estoque da frota</h1>
    <p class="lead">O depósito guarda pneu, filtro, óleo e pastilha. A entrada identifica a nota ou o lote. A saída vai para o veículo da ordem ainda aberta, e o óleo vencido ou mais novo fica no depósito.</p>
    <div class="kpis">
        <article class="kpi alerta"><span class="marca-kpi"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span><span><strong>{{ $resumo['abaixo'] }}</strong><em>Abaixo do mínimo</em></span></article>
        <article class="kpi"><span class="marca-kpi"><i class="fa fa-ban" aria-hidden="true"></i></span><span><strong>{{ $resumo['zerado'] }}</strong><em>Sem saldo</em></span></article>
        <article class="kpi ambar"><span class="marca-kpi"><i class="fa fa-clock-o" aria-hidden="true"></i></span><span><strong>{{ $resumo['vencer'] }}</strong><em>Lotes a vencer</em></span></article>
        <article class="kpi alerta"><span class="marca-kpi"><i class="fa fa-calendar-times-o" aria-hidden="true"></i></span><span><strong>{{ $resumo['vencido'] }}</strong><em>Lotes vencidos</em></span></article>
    </div>
    <div data-painel>
        <section class="bloco">
            <div class="barra-dados">
                <h2>Itens</h2>
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
                    <button type="button" class="botao claro" data-abre-modal="nova-entrada"><i class="fa fa-sign-in" aria-hidden="true"></i> Entrada</button>
                @endif
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Tipo</th><th>Saldo</th><th>Mínimo</th><th>Situação</th><th></th></tr></thead>
                    <tbody>
                        @forelse($itens as $item)
                            @php
                                $saldo = $saldos[$item->id] ?? 0;
                                $faixa = \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::faixa((float) $saldo, (float) $item->minimo, (bool) $item->ativo);
                            @endphp
                            <tr data-busca="{{ $item->nome }} {{ $tipos[$item->tipo] ?? $item->tipo }}">
                                <td><strong>{{ $item->nome }}</strong></td>
                                <td>{{ $tipos[$item->tipo] ?? $item->tipo }}</td>
                                <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $saldo) }} {{ $item->unidade }}</td>
                                <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $item->minimo) }}</td>
                                <td><span class="selo {{ $faixa === 'regular' ? 'ok' : ($faixa === 'abaixo' ? 'prazo' : 'alerta') }}">{{ $faixas[$faixa] }}</span></td>
                                <td class="acoes">@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-item-{{ $item->id }}" aria-label="Editar item" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="6" class="vazio">Nenhum item neste filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="bloco">
            <h2>Lotes</h2>
            <p class="lead">Óleo e filtro com validade saem do lote que vence primeiro. Pneu e pastilha não têm prazo.</p>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Nota ou lote</th><th>Quantidade</th><th>Validade</th><th>Saída</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lotes as $lote)
                            @php
                                $recusa = \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::recusaBaixa($lote->validade, (float) $lote->quantidade, $primeira[$lote->item_id] ?? null, $hoje);
                                $prazo = !$lote->validade ? null : ($lote->validade < $hoje ? 'vencido' : ($lote->validade <= now()->addDays(30)->toDateString() ? 'perto' : 'ok'));
                            @endphp
                            <tr data-busca="{{ $lote->nome }} {{ $lote->codigo }}">
                                <td>{{ $lote->nome }}</td>
                                <td>{{ $lote->codigo }}</td>
                                <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $lote->quantidade) }} {{ $lote->unidade }}</td>
                                <td>
                                    @if($prazo === null)
                                        —
                                    @else
                                        <span class="selo {{ $prazo === 'ok' ? 'ok' : ($prazo === 'perto' ? 'prazo' : 'alerta') }}">{{ \Illuminate\Support\Carbon::parse($lote->validade)->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($recusa === 'vencido')
                                        <span class="selo alerta">Vencido</span>
                                    @elseif($recusa === 'fefo')
                                        <span class="selo prazo">Há lote mais próximo</span>
                                    @elseif($recusa === 'sem_saldo')
                                        <span class="selo">Zerado</span>
                                    @elseif($lote->validade && $lote->validade === ($primeira[$lote->item_id] ?? null))
                                        <span class="selo ok">Sai primeiro</span>
                                    @else
                                        <span class="selo ok">Pode sair</span>
                                    @endif
                                </td>
                                <td class="acoes">
                                    @if($podeGravar)
                                        @if($recusa === null)
                                            <button type="button" class="botao" data-abre-modal="baixa-{{ $lote->id }}">Baixar</button>
                                        @endif
                                        <button type="button" class="botao claro" data-abre-modal="contar-{{ $lote->id }}">Contar</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="6" class="vazio">Nenhum lote neste filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="bloco">
            <h2>Últimos movimentos</h2>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Quando</th><th>Item</th><th>Lote</th><th>Movimento</th><th>Antes</th><th>Depois</th><th>Destino</th></tr></thead>
                    <tbody>
                        @forelse($movimentos as $movimento)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($movimento->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $movimento->nome }}</td>
                                <td>{{ $movimento->codigo }}</td>
                                <td><span class="selo {{ $movimento->tipo === 'baixa' ? 'alerta' : ($movimento->tipo === 'contagem' ? 'prazo' : 'ok') }}">{{ $movimento->tipo === 'contagem' ? 'Contagem' : ($movimento->tipo === 'baixa' ? 'Baixa' : 'Entrada') }}</span></td>
                                <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $movimento->saldo_anterior) }}</td>
                                <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $movimento->saldo_novo) }}</td>
                                <td>{{ $movimento->placa ? trim(($movimento->protocolo ? $movimento->protocolo . ' · ' : '') . $movimento->placa) : ($movimento->motivo ?: '—') }}</td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="7" class="vazio">Ainda não há movimento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
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
            <div class="modal-cabeca"><h2>Entrada no depósito</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/estoque/entrada') }}">
                @csrf
                <p><label>Item
                    <select name="item_id" required>
                        @foreach($itens as $item)
                            @continue(!$item->ativo)
                            <option value="{{ $item->id }}" data-tipo="{{ $item->tipo }}">{{ $item->nome }} · {{ $tipos[$item->tipo] ?? $item->tipo }}</option>
                        @endforeach
                    </select>
                </label></p>
                <div class="linha">
                    <label>Nota fiscal ou lote <input name="codigo" required maxlength="40"></label>
                    <label>Quantidade <input name="quantidade" type="number" min="0.001" step="0.001" required></label>
                </div>
                <p><label>Validade do óleo ou do filtro <input type="date" name="validade"></label></p>
                <button type="submit">Lançar entrada</button>
            </form>
        </dialog>
        @foreach($itens as $item)
            <dialog class="modal" id="editar-item-{{ $item->id }}">
                <div class="modal-cabeca"><h2>Editar item</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/estoque/item/'.$item->id) }}">
                    @csrf
                    @include('transporte-escolar::estoque.campos', ['item' => $item])
                    <p><label class="opcao"><input type="checkbox" name="ativo" value="1" @checked($item->ativo)> Ativo no depósito</label></p>
                    <button type="submit">Gravar</button>
                </form>
            </dialog>
        @endforeach
        @foreach($lotes as $lote)
            @php $recusa = \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::recusaBaixa($lote->validade, (float) $lote->quantidade, $primeira[$lote->item_id] ?? null, $hoje); @endphp
            @if($recusa === null)
                <dialog class="modal" id="baixa-{{ $lote->id }}">
                    <div class="modal-cabeca"><h2>Baixa na ordem</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                    <form method="post" action="{{ url('/transporte-escolar/estoque/baixa') }}">
                        @csrf
                        <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                        <p class="lead">{{ $lote->nome }} · {{ $lote->codigo }} · saldo {{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $lote->quantidade) }} {{ $lote->unidade }}. O veículo é o da ordem.</p>
                        <p><label>Quantidade <input name="quantidade" type="number" min="{{ in_array($lote->tipo, ['pneu', 'filtro', 'pastilha'], true) ? '1' : '0.001' }}" step="{{ in_array($lote->tipo, ['pneu', 'filtro', 'pastilha'], true) ? '1' : '0.001' }}" required></label></p>
                        <p><label>Ordem aberta
                            <select name="ordem_id" required>
                                @forelse($ordens as $ordem)
                                    <option value="{{ $ordem->id }}">{{ $ordem->protocolo ?: 'Ordem ' . $ordem->id }} · {{ $ordem->placa }} · {{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::frase($ordem->situacao) }} · {{ $ordem->descricao }}</option>
                                @empty
                                    <option value="">Nenhuma ordem aberta</option>
                                @endforelse
                            </select>
                        </label></p>
                        <p><label>Onde foi usada <input name="motivo" maxlength="200" placeholder="Eixo dianteiro, troca preventiva"></label></p>
                        <button type="submit" @disabled($ordens->isEmpty())>Baixar</button>
                    </form>
                </dialog>
            @endif
            <dialog class="modal" id="contar-{{ $lote->id }}">
                <div class="modal-cabeca"><h2>Contagem física</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/estoque/contagem') }}">
                    @csrf
                    <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                    <p class="lead">O sistema tem {{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::texto((float) $lote->quantidade) }} {{ $lote->unidade }}. Se o número for o mesmo, o motivo pode ficar em branco.</p>
                    <p><label>Quantidade contada <input name="quantidade" type="number" min="0" step="{{ in_array($lote->tipo, ['pneu', 'filtro', 'pastilha'], true) ? '1' : '0.001' }}" value="{{ $lote->quantidade }}" required></label></p>
                    <p><label>Motivo, se houver diferença <input name="motivo" maxlength="200"></label></p>
                    <button type="submit">Gravar contagem</button>
                </form>
            </dialog>
        @endforeach
    @endif
@endsection
