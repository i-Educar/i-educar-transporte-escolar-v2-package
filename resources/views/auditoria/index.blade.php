@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-history" aria-hidden="true"></i> Auditoria</h1>
    <p class="lead">A tela mostra {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Pagina::TAMANHO }} registros por vez. A planilha traz o filtro inteiro, para conferir inclusão, alteração e exclusão.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Tabela
                    <select name="tabela">
                        <option value="">Todas</option>
                        @foreach($tabelas as $nome)
                            <option value="{{ $nome }}" @selected($tabela === $nome)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Operação
                    <select name="operacao">
                        <option value="">Todas</option>
                        @foreach(['Inclusão', 'Alteração', 'Exclusão'] as $nome)
                            <option value="{{ $nome }}" @selected($operacao === $nome)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Filtrar</button>
            </form>
            <a class="botao claro" href="{{ url('/transporte-escolar/auditoria/csv') }}?{{ http_build_query(array_filter(['tabela' => $tabela, 'operacao' => $operacao])) }}"><i class="fa fa-download" aria-hidden="true"></i> Exportar</a>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar nesta página"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Quando</th><th>Tabela</th><th>Operação</th><th>Campos</th><th></th></tr></thead>
                <tbody>
                @forelse($linhas as $linha)
                    <tr data-busca="{{ $linha->table }} {{ $linha->operacao }} {{ implode(' ', $linha->campos) }}">
                        <td>{{ \Illuminate\Support\Carbon::parse($linha->date)->format('d/m/Y H:i') }}</td>
                        <td>{{ $linha->table }}</td>
                        <td><span class="selo {{ $linha->operacao === 'Exclusão' ? 'alerta' : ($linha->operacao === 'Inclusão' ? 'ok' : 'prazo') }}">{{ $linha->operacao }}</span></td>
                        <td>{{ $linha->campos === [] ? '—' : implode(', ', array_slice($linha->campos, 0, 6)) }}</td>
                        <td class="acoes"><button type="button" class="icone ver" data-abre-modal="aud-{{ $linha->id }}" aria-label="Ver registro" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button></td>
                    </tr>
                @empty
                    <tr data-vazio><td colspan="5" class="vazio">Nenhum registro de auditoria do transporte.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('transporte-escolar::paginacao', [
            'url' => url('/transporte-escolar/auditoria'),
            'consulta' => array_filter(['tabela' => $tabela, 'operacao' => $operacao]),
        ])
    </div>
    @foreach($linhas as $linha)
        <dialog class="modal modal-leitura" id="aud-{{ $linha->id }}">
            <div class="modal-cabeca">
                <h2><span class="selo {{ $linha->operacao === 'Exclusão' ? 'alerta' : ($linha->operacao === 'Inclusão' ? 'ok' : 'prazo') }}">{{ $linha->operacao }}</span> {{ $linha->assunto }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-corpo">
                @php
                    $quando = \Illuminate\Support\Carbon::parse($linha->date);
                    $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
                @endphp
                <p class="leitura-quando">{{ $quando->format('d') }} de {{ $meses[(int) $quando->format('n')] }} de {{ $quando->format('Y') }}, às {{ $quando->format('H:i') }}</p>
                <p class="leitura-frase">{{ $linha->leitura['frase'] }}</p>
                @if($linha->leitura['linhas'] === [])
                    <p class="leitura-vazio">Não há informação de conteúdo nesta ação. A mudança ficou só na data de atualização.</p>
                @else
                    <table class="leitura-tabela">
                        <thead>
                            <tr>
                                <th>Informação</th>
                                @if($linha->leitura['modo'] !== 'inclusao')<th>Como estava</th>@endif
                                @if($linha->leitura['modo'] !== 'exclusao')<th>Como ficou</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($linha->leitura['linhas'] as $item)
                            <tr>
                                <th>{{ $item['rotulo'] }}</th>
                                @if($linha->leitura['modo'] !== 'inclusao')<td>{{ $item['antes'] }}</td>@endif
                                @if($linha->leitura['modo'] !== 'exclusao')<td class="leitura-novo">{{ $item['depois'] }}</td>@endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </dialog>
    @endforeach
    <style>
        dialog.modal-leitura { width: min(880px, calc(100% - 32px)); }
        .leitura-quando { margin: 0; color: var(--suave); font-size: .92rem; }
        .leitura-frase { margin: 0; font-size: 1.05rem; }
        .leitura-vazio { margin: 0; background: #fff; border: 1px solid var(--linha); border-radius: 8px; padding: 14px; }
        .leitura-tabela { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid var(--linha); border-radius: 8px; overflow: hidden; }
        .leitura-tabela th, .leitura-tabela td { padding: 10px 12px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--linha); }
        .leitura-tabela thead th { background: var(--azul-claro); color: var(--azul-escuro); font-size: .78rem; letter-spacing: .02em; }
        .leitura-tabela tbody th { width: 34%; font-weight: 650; color: var(--tinta); }
        .leitura-tabela td { color: var(--suave); }
        .leitura-tabela td.leitura-novo { color: var(--tinta); font-weight: 700; background: #f3faf6; }
        .leitura-tabela tr:last-child th, .leitura-tabela tr:last-child td { border-bottom: 0; }
    </style>
@endsection
