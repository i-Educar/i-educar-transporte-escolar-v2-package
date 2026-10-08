@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-users" aria-hidden="true"></i> Alunos na rota · {{ $ano }}</h1>
    <p class="lead">A matrícula, a turma e a série vêm do i-Educar. O vínculo com a rota é incluído pela janela de cada aluno.</p>
    <form method="get" class="acao" style="margin-bottom:12px;">
        <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
        <label>Nome ou CPF <input name="q" value="{{ $busca }}"></label>
        <button type="submit">Buscar no ano</button>
    </form>

    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Filtrar a lista já carregada"></label>
        </div>
        <h2>Matrículas</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Aluno</th><th>Escola</th><th>Série</th><th>Turma</th><th></th></tr></thead>
                <tbody>
                    @forelse($matriculas as $aluno)
                        <tr data-busca="{{ $aluno->nome }} {{ $aluno->escola }} {{ $aluno->nm_serie }} {{ $aluno->nm_turma }}">
                            <td>{{ $aluno->nome }}</td>
                            <td>{{ $aluno->escola }}</td>
                            <td>{{ $aluno->nm_serie ?: '—' }}</td>
                            <td>{{ $aluno->nm_turma ?: '—' }}</td>
                            <td>@if($rotas->isNotEmpty())<button type="button" class="botao" data-abre-modal="vincular-{{ $aluno->cod_aluno }}">Vincular</button>@endif</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhuma matrícula nesta busca.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div data-painel>
        <h2>Já vinculados</h2>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Filtrar vínculos"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Aluno</th><th>Rota</th><th>Casa</th><th></th></tr></thead>
                <tbody>
                    @forelse($vinculos as $vinculo)
                        <tr data-busca="{{ $vinculo->nome }} {{ $vinculo->descricao }}">
                            <td>{{ $vinculo->nome }}</td>
                            <td>{{ $vinculo->descricao }}</td>
                            <td>{{ $vinculo->residencia_latitude ? $vinculo->residencia_latitude.', '.$vinculo->residencia_longitude : '—' }}</td>
                            <td>
                                <form method="post" action="{{ url('/transporte-escolar/alunos/remover') }}" class="acao">
                                    @csrf
                                    <input type="hidden" name="aluno_id" value="{{ $vinculo->ref_cod_aluno }}">
                                    <input type="hidden" name="rota_id" value="{{ $vinculo->rota_id }}">
                                    <button type="submit">Retirar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="4" class="vazio">Nenhum aluno vinculado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($matriculas as $aluno)
        <dialog class="modal" id="vincular-{{ $aluno->cod_aluno }}">
            <div class="modal-cabeca">
                <h2>Vincular {{ $aluno->nome }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" action="{{ url('/transporte-escolar/alunos') }}">
                @csrf
                <input type="hidden" name="aluno_id" value="{{ $aluno->cod_aluno }}">
                <label>Rota
                    <select name="rota_id" required>
                        @foreach($rotas as $rota)<option value="{{ $rota->id }}">{{ $rota->descricao }}</option>@endforeach
                    </select>
                </label>
                <label>Turno <input name="turno"></label>
                <div class="linha">
                    <label>Latitude da casa <input name="residencia_latitude"></label>
                    <label>Longitude da casa <input name="residencia_longitude"></label>
                </div>
                <button type="submit">Vincular</button>
            </form>
        </dialog>
    @endforeach
@endsection
