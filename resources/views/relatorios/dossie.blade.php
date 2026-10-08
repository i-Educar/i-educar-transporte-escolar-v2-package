@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Dossiê {{ $mes }}/{{ $ano }}</h1>
    <p>Documento para quem lança o SiGPC. Não envia a prestação e não substitui o empenho da contabilidade.</p>
    <p>PNATE {{ number_format($custo['pnate'], 2, ',', '.') }} · Recurso próprio {{ number_format($custo['proprio'], 2, ',', '.') }}</p>
    <ul>
        @foreach($custo['linhas'] as $linha)
            <li>{{ $linha['rotulo'] }} · {{ $linha['fonte'] }} · {{ number_format($linha['valor'], 2, ',', '.') }}</li>
        @endforeach
    </ul>
    <h2>Comprovantes</h2>
    <ul>
        @foreach($comprovantes as $arquivo)
            <li><a href="{{ url('/transporte-escolar/gastos/comprovante/'.$arquivo->id) }}">{{ $arquivo->nome }}</a> · {{ $arquivo->descricao }}</li>
        @endforeach
    </ul>
@endsection
