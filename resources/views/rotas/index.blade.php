@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Rotas de {{ $ano }}</h1>
    <form method="get">
        <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
        <button type="submit">Ver</button>
    </form>
    <table cellpadding="6">
        <tr><th>Rota</th><th>Tipo</th><th>Km informado</th><th>Km calculado</th></tr>
        @foreach($rotas as $rota)
            <tr>
                <td><a href="{{ url('/transporte-escolar/rotas/'.$rota->id) }}">{{ $rota->descricao }}</a></td>
                <td>{{ $codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota }}</td>
                <td>{{ number_format((float) $rota->km_pav + (float) $rota->km_npav, 1, ',', '.') }}</td>
                <td>{{ $rota->km_calculado === null ? '—' : number_format((float) $rota->km_calculado, 1, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    @if($podeGravar)
        <h2>Nova rota</h2>
        <form method="post" action="{{ url('/transporte-escolar/rotas') }}">
            @csrf
            <p><label>Ano <input name="ano" value="{{ $ano }}" required></label></p>
            <p><label>Descrição <input name="descricao" required maxlength="80"></label></p>
            <p><label>Tipo
                <select name="tipo_rota">@foreach($codigos::TIPO_ROTA as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label></p>
            <p><label>Período
                <select name="periodo">@foreach($codigos::PERIODO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label></p>
            <p><label>Sentido
                <select name="sentido">@foreach($codigos::SENTIDO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label></p>
            <p><label>Km pavimentado <input name="km_pav"></label> <label>Km não pavimentado <input name="km_npav"></label></p>
            <p><label>Velocidade média (km/h) <input name="velocidade_media_kmh" value="40"></label></p>
            <p><label><input type="checkbox" name="terceirizada" value="1"> Terceirizada</label></p>
            <p>Escolas atendidas<br>
                @foreach($escolas as $escola)
                    <label><input type="checkbox" name="escolas[]" value="{{ $escola->cod_escola }}"> {{ $escola->nome }}</label><br>
                @endforeach
            </p>
            <button type="submit">Gravar rota</button>
        </form>
    @endif
@endsection
