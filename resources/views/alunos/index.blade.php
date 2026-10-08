@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Alunos na rota · {{ $ano }}</h1>
    <p>A matrícula, a turma, a série e a zona rural vêm do i-Educar. Esta tela só grava o vínculo com a rota e atualiza tipo de transporte, veículo e nome da rota na ficha do aluno.</p>
    <form method="get">
        <input name="ano" value="{{ $ano }}" size="6">
        <input name="q" value="{{ $busca }}" placeholder="Nome ou CPF">
        <button type="submit">Buscar</button>
    </form>
    @foreach($matriculas as $aluno)
        <form method="post" action="{{ url('/transporte-escolar/alunos') }}">
            @csrf
            <input type="hidden" name="aluno_id" value="{{ $aluno->cod_aluno }}">
            <strong>{{ $aluno->nome }}</strong>
            — {{ $aluno->escola }}
            @if($aluno->nm_serie) · {{ $aluno->nm_serie }} @endif
            @if($aluno->nm_turma) · {{ $aluno->nm_turma }} @endif
            @if($aluno->zona_localizacao_censo) · zona {{ $aluno->zona_localizacao_censo }} @endif
            <select name="rota_id" required>
                @foreach($rotas as $rota)<option value="{{ $rota->id }}">{{ $rota->descricao }}</option>@endforeach
            </select>
            <input name="turno" placeholder="Turno">
            <input name="residencia_latitude" placeholder="Latitude da casa">
            <input name="residencia_longitude" placeholder="Longitude da casa">
            <button type="submit">Vincular</button>
        </form>
    @endforeach

    <h2>Já vinculados</h2>
    @foreach($vinculos as $vinculo)
        <form method="post" action="{{ url('/transporte-escolar/alunos/remover') }}">
            @csrf
            <input type="hidden" name="aluno_id" value="{{ $vinculo->ref_cod_aluno }}">
            <input type="hidden" name="rota_id" value="{{ $vinculo->rota_id }}">
            {{ $vinculo->nome }} · {{ $vinculo->descricao }}
            @if($vinculo->residencia_latitude) · casa {{ $vinculo->residencia_latitude }}, {{ $vinculo->residencia_longitude }} @endif
            <button type="submit">Retirar</button>
        </form>
    @endforeach
@endsection
