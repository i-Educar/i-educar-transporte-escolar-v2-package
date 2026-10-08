@extends('transporte-escolar::layout')

@section('transporte')
    <h1>{{ $viagem->descricao }} · {{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}</h1>
    <p>Km do odômetro: {{ $km === null ? 'informe inicial e final' : $km }} · Km da trilha: {{ $viagem->km_trilha === null ? 'sem GPS suficiente' : $viagem->km_trilha }}</p>
    @foreach($alunos as $aluno)
        @php($chave = $aluno->ref_cod_aluno)
        <p>
            {{ $aluno->nome }}
            {!! $qrs[$aluno->ref_cod_aluno] !!}
            @if(isset($eventos[$chave.':embarque'])) · embarcou @endif
            @if(isset($eventos[$chave.':falta'])) · faltou no transporte @endif
            <form method="post" action="{{ url('/transporte-escolar/viagens/'.$viagem->id.'/evento') }}" style="display:inline">
                @csrf
                <input type="hidden" name="aluno_id" value="{{ $aluno->ref_cod_aluno }}">
                <input type="hidden" name="tipo" value="embarque">
                <button type="submit">Embarcou</button>
            </form>
            <form method="post" action="{{ url('/transporte-escolar/viagens/'.$viagem->id.'/evento') }}" style="display:inline">
                @csrf
                <input type="hidden" name="aluno_id" value="{{ $aluno->ref_cod_aluno }}">
                <input type="hidden" name="tipo" value="falta">
                <button type="submit">Faltou</button>
            </form>
        </p>
    @endforeach
@endsection
