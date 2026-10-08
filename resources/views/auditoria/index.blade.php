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
        <dialog class="modal" id="aud-{{ $linha->id }}">
            <div class="modal-cabeca"><h2>{{ $linha->operacao }} em {{ $linha->table }}</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <div class="modal-corpo">
                <p>{{ $linha->resumo ?: 'Sem campo alterado além da data.' }}</p>
                <div class="linha">
                    <p><strong>Antes</strong><pre>{{ $linha->antes_texto ?: '—' }}</pre></p>
                    <p><strong>Depois</strong><pre>{{ $linha->depois_texto ?: '—' }}</pre></p>
                </div>
            </div>
        </dialog>
    @endforeach
@endsection
