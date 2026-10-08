@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Viagens</h1>
    <p>A falta aqui é falta no transporte. A frequência da aula continua no diário.</p>
    <table cellpadding="6">
        <tr><th>Data</th><th>Rota</th><th>Sentido</th><th>Km realizado</th></tr>
        @foreach($viagens as $viagem)
            @php($km = \iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado::deOdometro($viagem->km_inicial === null ? null : (float) $viagem->km_inicial, $viagem->km_final === null ? null : (float) $viagem->km_final))
            <tr>
                <td><a href="{{ url('/transporte-escolar/viagens/'.$viagem->id) }}">{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}</a></td>
                <td>{{ $viagem->descricao }}</td>
                <td>{{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}</td>
                <td>{{ $km === null ? '—' : $km }}</td>
            </tr>
        @endforeach
    </table>
    <h2>Abrir viagem</h2>
    <form method="post">
        @csrf
        <p><label>Data <input type="date" name="data" required value="{{ date('Y-m-d') }}"></label></p>
        <p><label>Rota <select name="rota_id" required>@foreach($rotas as $rota)<option value="{{ $rota->id }}">{{ $rota->descricao }} ({{ $rota->ano }})</option>@endforeach</select></label></p>
        <p><label>Veículo <select name="veiculo_id"><option value="">—</option>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label></p>
        <p><label>Motorista <select name="motorista_id"><option value="">—</option>@foreach($motoristas as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label></p>
        <p><label>Monitor <select name="monitor_id"><option value="">—</option>@foreach($monitores as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label></p>
        <p><label>Sentido <select name="sentido">@foreach($sentidos as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label></p>
        <p><label>Km inicial <input name="km_inicial"></label> <label>Km final <input name="km_final"></label></p>
        <button type="submit">Abrir</button>
    </form>
@endsection
