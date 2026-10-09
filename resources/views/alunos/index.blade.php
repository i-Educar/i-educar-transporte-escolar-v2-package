@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $mostrar = fn ($nome) => \iEducar\Packages\BuritiTransporteEscolar\Support\NomeExibicao::exibir((string) $nome, $admin);
    @endphp
    <h1><i class="fa fa-users" aria-hidden="true"></i> Alunos na rota · {{ $ano }}</h1>
    <p class="lead">A matrícula vem do i-Educar. @if($admin) O perfil institucional vê o nome completo. @else O perfil da escola vê o primeiro nome e a inicial do sobrenome. @endif</p>
    <form method="get" class="acao" style="margin-bottom:12px;">
        <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
        <label>Nome, matrícula ou CPF <input name="q" value="{{ $busca }}"></label>
        <button type="submit">Buscar no ano</button>
    </form>

    <div data-painel>
        <h2>Alunos Transportados</h2>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Filtrar a página carregada"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Matrícula</th><th>Aluno</th><th>Rota</th><th>Cartão</th><th>Responsável</th><th></th></tr></thead>
                <tbody>
                    @forelse($vinculos as $vinculo)
                        @php($nome = $mostrar($vinculo->nome))
                        <tr data-busca="{{ $vinculo->cod_matricula }} {{ $nome }} {{ $vinculo->descricao }} {{ $vinculo->nome_responsavel }}">
                            <td>{{ $vinculo->cod_matricula ?: '—' }}</td>
                            <td>{{ $nome }}</td>
                            <td>{{ $vinculo->descricao }}</td>
                            <td>{{ $vinculo->cartao ?: '—' }}</td>
                            <td>{{ $vinculo->nome_responsavel ?: '—' }}</td>
                            <td class="acoes">
                                <button type="button" class="icone ver" data-abre-modal="detalhe-{{ $vinculo->ref_cod_aluno }}-{{ $vinculo->rota_id }}" aria-label="Ver detalhes" title="Ver detalhes"><i class="fa fa-eye" aria-hidden="true"></i></button>
                                <button type="button" class="icone editar" data-abre-modal="editar-{{ $vinculo->ref_cod_aluno }}-{{ $vinculo->rota_id }}" aria-label="Editar" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                                <form method="post" action="{{ url('/transporte-escolar/alunos/remover') }}">
                                    @csrf
                                    <input type="hidden" name="aluno_id" value="{{ $vinculo->ref_cod_aluno }}">
                                    <input type="hidden" name="rota_id" value="{{ $vinculo->rota_id }}">
                                    <button type="submit" class="icone cancelar" aria-label="Retirar da rota" title="Retirar"><i class="fa fa-times" aria-hidden="true"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhum aluno transportado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('transporte-escolar::paginacao', ['pagina' => $pagina['pagina'], 'paginas' => $pagina['paginas'], 'total' => $pagina['total'], 'url' => url('/transporte-escolar/alunos'), 'consulta' => ['ano' => $ano, 'q' => $busca]])
    </div>

    @foreach($vinculos as $vinculo)
        @php($nome = $mostrar($vinculo->nome))
        <dialog class="modal" id="detalhe-{{ $vinculo->ref_cod_aluno }}-{{ $vinculo->rota_id }}">
            <div class="modal-cabeca">
                <h2>Aluno {{ $nome }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            @include('transporte-escolar::frota.fotos', ['lista' => $fotos[$vinculo->ref_cod_aluno] ?? [], 'carteirinha' => true])
            <dl class="ficha">
                <dt>Matrícula</dt><dd>{{ $vinculo->cod_matricula ?: '—' }}</dd>
                <dt>Aluno</dt><dd>{{ $nome }}</dd>
                <dt>Escola</dt><dd>{{ $vinculo->escola ?: '—' }}</dd>
                <dt>Série e turma</dt><dd>{{ $vinculo->nm_serie ?: '—' }} · {{ $vinculo->nm_turma ?: '—' }}</dd>
                <dt>Rota</dt><dd>{{ $vinculo->descricao }}</dd>
                <dt>Turno</dt><dd>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$vinculo->turno] ?? ($vinculo->turno ?: '—') }}</dd>
                <dt>Cartão</dt><dd>{{ $vinculo->cartao ?: '—' }}</dd>
                <dt>Responsável</dt><dd>{{ $vinculo->nome_responsavel ?: '—' }}</dd>
                <dt>Telefone</dt><dd>{{ $vinculo->fone ?: '—' }}</dd>
                <dt>Casa</dt><dd>{{ $vinculo->residencia_latitude ? $vinculo->residencia_latitude.', '.$vinculo->residencia_longitude : '—' }}</dd>
            </dl>
        </dialog>
        <dialog class="modal" id="editar-{{ $vinculo->ref_cod_aluno }}-{{ $vinculo->rota_id }}">
            <div class="modal-cabeca">
                <h2>Editar {{ $nome }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" enctype="multipart/form-data" action="{{ url('/transporte-escolar/alunos/atualizar') }}">
                @csrf
                <input type="hidden" name="aluno_id" value="{{ $vinculo->ref_cod_aluno }}">
                <input type="hidden" name="rota_origem" value="{{ $vinculo->rota_id }}">
                <label>Rota
                    <select name="rota_id" required>
                        @foreach($rotas as $rota)<option value="{{ $rota->id }}" @selected($rota->id == $vinculo->rota_id)>{{ $rota->descricao }}</option>@endforeach
                    </select>
                </label>
                <label>Turno
                    <select name="turno" required>
                        <option value="">Escolha o turno</option>
                        @foreach(\iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO as $codigo => $rotulo)
                            <option value="{{ $codigo }}" @selected($vinculo->turno === $codigo)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Cartão ou RFID <input name="cartao" value="{{ $vinculo->cartao }}" maxlength="40"></label>
                @include('transporte-escolar::frota.fotos', ['lista' => $fotos[$vinculo->ref_cod_aluno] ?? [], 'carteirinha' => true])
                <label>Carteirinha <input type="file" name="carteirinha" accept="image/jpeg,image/png,image/webp"></label>
                <div class="linha">
                    <label>Latitude da casa <input name="residencia_latitude" value="{{ $vinculo->residencia_latitude }}"></label>
                    <label>Longitude da casa <input name="residencia_longitude" value="{{ $vinculo->residencia_longitude }}"></label>
                </div>
                <button type="submit">Gravar</button>
            </form>
        </dialog>
    @endforeach

@endsection
