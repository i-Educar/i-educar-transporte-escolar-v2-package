@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-comments" aria-hidden="true"></i> Aviso à família</h1>
    <p class="lead">O responsável escolhe o cadastro em <a href="{{ url('/transporte-escolar/avisos/cadastro') }}">{{ url('/transporte-escolar/avisos/cadastro') }}</a> e recebe o aviso no navegador e no celular. Não há SMS.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar data ou texto do aviso"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Data</th><th>Texto</th><th>Cerca</th><th>Leitura</th><th></th></tr></thead>
                <tbody>
                    @forelse($avisos as $aviso)
                        <tr data-busca="{{ \Illuminate\Support\Carbon::parse($aviso->data)->format('d/m/Y') }} {{ $aviso->texto }}">
                            <td>{{ \Illuminate\Support\Carbon::parse($aviso->data)->format('d/m/Y') }}</td>
                            <td>{{ $aviso->texto }}</td>
                            <td>{{ $aviso->fora_da_cerca ? 'Fora' : '—' }}</td>
                            <td>{{ $aviso->lido_em ? 'Lido' : 'Não lido' }}</td>
                            <td><a href="{{ url('/transporte-escolar/aviso/'.$aviso->token) }}">Abrir</a></td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhum aviso.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
