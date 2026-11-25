@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-exchange" aria-hidden="true"></i> SETE</h1>
    <p class="lead">A planilha entra de novo sem duplicar placa, CPF ou rota do mesmo ano. Pessoa que não existe no i-Educar vira divergência, não um cadastro novo.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar divergência"></label>
            @if($podeImportar)
                <button type="button" class="botao" data-abre-modal="importar-sete"><i class="fa fa-upload" aria-hidden="true"></i> Importar</button>
            @endif
        </div>
        @if($podeImportar)
            <p class="acao">
                <a href="{{ url('/transporte-escolar/sete/exportar/alunos') }}">Exportar alunos</a>
                <a href="{{ url('/transporte-escolar/sete/exportar/veiculos') }}">Veículos</a>
                <a href="{{ url('/transporte-escolar/sete/exportar/motoristas') }}">Motoristas</a>
                <a href="{{ url('/transporte-escolar/sete/exportar/monitores') }}">Monitores</a>
            </p>
        @endif
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Situação</th><th>Entidade</th><th>Linha</th><th>Mensagem</th></tr></thead>
                <tbody>
                    @forelse($linhas as $linha)
                        <tr data-busca="{{ $linha->situacao }} {{ $linha->entidade }} {{ $linha->mensagem }}">
                            <td>{{ $linha->situacao }}</td>
                            <td>{{ $linha->entidade }}</td>
                            <td>{{ $linha->linha }}</td>
                            <td>{{ $linha->mensagem }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="4" class="vazio">Nenhuma divergência.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($podeImportar)
        <dialog class="modal" id="importar-sete">
            <div class="modal-cabeca">
                <h2>Importar planilha</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" enctype="multipart/form-data">
                @csrf
                <label>Ano <input name="ano" value="{{ date('Y') }}" required></label>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'arquivo',
                    'rotulo' => 'Planilha ou arquivo do SETE',
                    'tituloAcao' => 'Soltar o arquivo aqui',
                    'ajuda' => 'CSV, TXT ou Excel — arraste ou toque para escolher.',
                    'accept' => '.csv,.txt,.xlsx,.xls',
                    'obrigatorio' => true,
                    'icone' => 'fa-file-excel-o',
                ])
                <button type="submit">Importar</button>
            </form>
        </dialog>
    @endif
@endsection
