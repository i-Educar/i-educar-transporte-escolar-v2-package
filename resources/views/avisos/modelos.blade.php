@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-bell" aria-hidden="true"></i> Modelos de notificação</h1>
    <p class="lead">O município define o título e o texto. Use {aluno}, {rota} e {acao}. O responsável se cadastra em <a href="{{ $cadastro }}">{{ $cadastro }}</a> e recebe o aviso no navegador e no celular.</p>
    <div data-painel>
        <div class="barra-dados">
            <h2>Tipos</h2>
            <button type="button" data-abre-modal="novo-modelo">Novo modelo</button>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Código</th><th>Título</th><th>Texto</th><th>Ativo</th><th></th></tr></thead>
                <tbody>
                    @foreach($modelos as $modelo)
                        <tr>
                            <td>{{ $modelo->codigo }}</td>
                            <td>{{ $modelo->titulo }}</td>
                            <td>{{ $modelo->corpo }}</td>
                            <td>{{ $modelo->ativo ? 'Sim' : 'Não' }}</td>
                            <td class="acoes">
                                <button type="button" class="icone editar" data-abre-modal="modelo-{{ $modelo->id }}" aria-label="Editar" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <dialog class="modal" id="novo-modelo">
        <div class="modal-cabeca">
            <h2>Novo tipo</h2>
            <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
        </div>
        <form method="post" action="{{ url('/transporte-escolar/avisos/modelos') }}">
            @csrf
            <label>Código <input name="codigo" required maxlength="40" pattern="[a-z0-9_]+" placeholder="atraso"></label>
            <label>Título <input name="titulo" required maxlength="120"></label>
            <label>Texto <textarea name="corpo" required maxlength="500" rows="4"></textarea></label>
            <button type="submit">Criar</button>
        </form>
    </dialog>

    @foreach($modelos as $modelo)
        <dialog class="modal" id="modelo-{{ $modelo->id }}">
            <div class="modal-cabeca">
                <h2>Editar {{ $modelo->codigo }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" action="{{ url('/transporte-escolar/avisos/modelos/'.$modelo->id) }}">
                @csrf
                <label>Título <input name="titulo" value="{{ $modelo->titulo }}" required maxlength="120"></label>
                <label>Texto <textarea name="corpo" required maxlength="500" rows="4">{{ $modelo->corpo }}</textarea></label>
                <label><input type="checkbox" name="ativo" value="1" @checked($modelo->ativo)> Ativo</label>
                <button type="submit">Gravar</button>
            </form>
        </dialog>
    @endforeach
@endsection
