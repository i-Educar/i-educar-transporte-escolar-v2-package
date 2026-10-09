@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-bell" aria-hidden="true"></i> Notificações</h1>
    <p class="lead">O sino deixa de contar depois da leitura. A pendência continua na fila até deixar de existir.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Situação
                    <select name="situacao" onchange="this.form.submit()">
                        <option value="abertas" @selected($situacao === 'abertas')>Em aberto</option>
                        <option value="lidas" @selected($situacao === 'lidas')>Lidas</option>
                    </select>
                </label>
                <label>Tipo
                    <select name="tom" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($rotulos as $codigo => $rotulo)
                            <option value="{{ $codigo }}" @selected($tom === $codigo)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar notificação"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th></th><th>Assunto</th><th>Tipo</th><th>Quando</th><th></th></tr></thead>
                <tbody>
                    @forelse($linhas as $linha)
                        <tr data-busca="{{ $linha->titulo }} {{ $linha->texto }}">
                            <td><i class="fa {{ $linha->icone }} tom-{{ $linha->tom }}" aria-hidden="true"></i></td>
                            <td><strong>{{ $linha->titulo }}</strong><br><span class="vazio">{{ $linha->texto }}</span></td>
                            <td><span class="selo {{ $linha->tom === 'lotacao' ? 'alerta' : ($linha->tom === 'prazo' ? 'prazo' : ($linha->tom === 'servico' ? 'ok' : '')) }}">{{ $rotulos[$linha->tom] ?? $linha->tom }}</span> @if($linha->lida_em)<span class="selo ok">Lida</span>@endif</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($linha->updated_at)->format('d/m/Y H:i') }}</td>
                            <td class="acoes"><a class="icone ver" href="{{ url('/transporte-escolar/notificacoes/'.$linha->id) }}" aria-label="Ler notificação" title="Ler"><i class="fa fa-eye" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhuma notificação neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
