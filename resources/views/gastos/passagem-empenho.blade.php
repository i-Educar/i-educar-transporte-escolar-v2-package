@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-ticket" aria-hidden="true"></i> Passagens e empenhos (PoC Onda 4)</h1>
    <p class="lead">Consolida <code>PassagemControle</code> (5.4.10/18/19) e <code>EmpenhoVerba</code> (5.4.1/12). Fonte gastos: {{ $fonteGastos }}.</p>

    <section class="card-avisos" style="margin-bottom:16px;">
        <h2>Passagens</h2>
        <p>Recebido: <strong>{{ $passagens['total_recebido'] }}</strong> · Entregue: <strong>{{ $passagens['total_entregue'] }}</strong>
            @if($saldoNegativo)
                <span class="flash erro">Saldo negativo detectado</span>
            @endif
        </p>
        <table class="table">
            <thead><tr><th>Tipo</th><th>Saldo</th></tr></thead>
            <tbody>
            @foreach($passagens['saldo_por_tipo'] as $tipo => $saldo)
                <tr><td>{{ $tipo }}</td><td>{{ $saldo }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <h3>Por rede (entregues)</h3>
        <ul>
            @foreach($passagens['por_rede'] as $rede => $q)
                <li>{{ $rede }}: {{ $q }}</li>
            @endforeach
        </ul>
    </section>

    <section class="card-avisos">
        <h2>Empenhos por verba</h2>
        <p>Total geral: <strong>R$ {{ number_format($empenhos['total_geral'], 2, ',', '.') }}</strong></p>
        <table class="table">
            <thead><tr><th>Verba</th><th>Total</th><th>Empenhos</th><th>Fornecedores</th></tr></thead>
            <tbody>
            @foreach($empenhos['por_verba'] as $verba => $info)
                <tr>
                    <td>{{ $verba }}</td>
                    <td>R$ {{ number_format($info['total'], 2, ',', '.') }}</td>
                    <td>{{ implode(', ', $info['empenhos']) }}</td>
                    <td>{{ implode(', ', $info['fornecedores']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <p><a href="{{ url('/transporte-escolar/gastos') }}">← Voltar aos gastos</a></p>
@endsection
