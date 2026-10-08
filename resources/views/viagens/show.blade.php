@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $hora = $viagem->hora ? substr((string) $viagem->hora, 0, 5) : null;
        $sentido = \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::SENTIDO[$viagem->sentido] ?? $viagem->sentido;
    @endphp
    <h1><i class="fa fa-book" aria-hidden="true"></i> Chamada · {{ $viagem->descricao }}</h1>
    <p class="lead">{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}{{ $hora ? ' · '.$hora : '' }} · {{ $sentido }}. A falta já vem marcada. A presença só entra quando alguém confirma o embarque.</p>
    <div class="barra-dados">
        <a class="botao" href="{{ url('/transporte-escolar/frequencia/'.$viagem->id.'/pdf') }}"><i class="fa fa-print" aria-hidden="true"></i> Frequência</a>
    </div>
    <p>Km do odômetro: {{ $km === null ? 'informe inicial e final' : $km }} · Km da trilha: {{ $viagem->km_trilha === null ? 'sem GPS suficiente' : $viagem->km_trilha }}</p>
    <div class="tabela-wrap">
        <table class="diario">
            <thead>
                <tr><th>Nº</th><th>Aluno</th><th>Ações</th></tr>
            </thead>
            <tbody>
            @forelse($alunos as $aluno)
                @php($presente = isset($eventos[$aluno->ref_cod_aluno.':embarque']))
                <tr class="{{ $presente ? '' : 'ausente' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="aluno-linha">
                            {!! $qrs[$aluno->ref_cod_aluno] !!}
                            <div>
                                <strong>{{ $aluno->nome }}</strong>
                                <small>{{ $aluno->responsavel ?: 'Responsável não informado' }}{{ $aluno->telefone ? ' · '.$aluno->telefone : '' }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="acoes">
                        <form method="post" action="{{ url('/transporte-escolar/viagens/'.$viagem->id.'/evento') }}">
                            @csrf
                            <input type="hidden" name="aluno_id" value="{{ $aluno->ref_cod_aluno }}">
                            <input type="hidden" name="tipo" value="embarque">
                            <button type="submit" class="icone aceitar {{ $presente ? 'marcado' : '' }}" aria-label="Presença" title="Presença"><i class="fa fa-check" aria-hidden="true"></i></button>
                        </form>
                        <form method="post" action="{{ url('/transporte-escolar/viagens/'.$viagem->id.'/evento') }}">
                            @csrf
                            <input type="hidden" name="aluno_id" value="{{ $aluno->ref_cod_aluno }}">
                            <input type="hidden" name="tipo" value="falta">
                            <button type="submit" class="icone cancelar {{ $presente ? '' : 'marcado' }}" aria-label="Falta" title="Falta"><i class="fa fa-times" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="vazio">Esta rota ainda não tem aluno vinculado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
