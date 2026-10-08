@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-check-square-o" aria-hidden="true"></i> Frequência</h1>
    <p class="lead">Cada viagem gera a lista de quem embarcou. Sem marcação, a linha fica como falta. A escola vê só as rotas das suas matrículas.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
                <label>Mês <input name="mes" value="{{ $mes }}" size="3"></label>
                <button type="submit">Ver mês</button>
            </form>
        </div>
        <h2>Por viagem</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Data</th><th>Horário</th><th>Rota</th><th>Sentido</th><th>Presentes</th><th>Faltas</th><th></th></tr></thead>
                <tbody>
                    @forelse($viagens as $viagem)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}</td>
                            <td>{{ $viagem->hora ? substr((string) $viagem->hora, 0, 5) : '—' }}</td>
                            <td>{{ $viagem->descricao }}</td>
                            <td>{{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}</td>
                            <td>{{ $viagem->presentes }}</td>
                            <td>{{ $viagem->faltas }}</td>
                            <td class="acoes">
                                <a class="icone ver" href="{{ url('/transporte-escolar/viagens/'.$viagem->id) }}" aria-label="Abrir chamada" title="Chamada"><i class="fa fa-book" aria-hidden="true"></i></a>
                                <a class="icone imprimir" href="{{ url('/transporte-escolar/frequencia/'.$viagem->id.'/pdf') }}" aria-label="Emitir frequência" title="Frequência"><i class="fa fa-print" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="vazio">Nenhuma viagem neste mês.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2>No mês</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Aluno</th><th>Rota</th><th>Presenças</th><th>Faltas</th><th>Situação</th></tr></thead>
                <tbody>
                    @forelse($alunos as $aluno)
                        <tr>
                            <td>{{ $aluno->nome }}</td>
                            <td>{{ $aluno->descricao }}</td>
                            <td>{{ $aluno->presencas }}</td>
                            <td>{{ $aluno->faltas }}</td>
                            <td>{{ $aluno->situacao }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="vazio">Nenhum aluno nas rotas deste mês.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
