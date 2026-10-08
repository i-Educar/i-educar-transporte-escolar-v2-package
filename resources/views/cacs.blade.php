@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Painel do CACS · {{ $ano }}</h1>
    <p>Leitura da rede. Não calcula o Ideges.</p>
    <ul>
        <li>Alunos em rota rural: {{ $rurais }}</li>
        <li>Veículos ativos: {{ $frota }}</li>
        <li>Licenças vencidas: {{ $vencidos }}</li>
        <li>CNH vencidas: {{ $cnh }}</li>
        <li>Gasto PNATE no mês: {{ number_format($custo['pnate'], 2, ',', '.') }}</li>
        <li>Recurso próprio no mês, com salário rateado: {{ number_format($custo['proprio'], 2, ',', '.') }}</li>
    </ul>
@endsection
