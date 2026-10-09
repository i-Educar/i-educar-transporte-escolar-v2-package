@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-road" aria-hidden="true"></i> Rotas de {{ $ano }}</h1>
    <p class="lead">A lista mostra as rotas do ano. A inclusão abre numa janela, sem sair da tabela.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
                <button type="submit">Ver ano</button>
            </form>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar rota, tipo ou quilometragem"></label>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="nova-rota"><i class="fa fa-plus" aria-hidden="true"></i> Nova rota</button>
            @endif
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Rota</th><th>Tipo</th><th>Km informado</th><th>Km calculado</th></tr></thead>
                <tbody>
                    @forelse($rotas as $rota)
                        <tr data-busca="{{ $rota->descricao }} {{ $codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota }}">
                            <td>
                                <a href="{{ url('/transporte-escolar/rotas/'.$rota->id) }}">{{ $rota->descricao }}</a>
                                @if(!empty($divergencias[$rota->id]))
                                    <div><span class="selo alerta">{{ count($divergencias[$rota->id]) === 1 ? '1 aluno com matrícula em turno diferente do transporte' : count($divergencias[$rota->id]).' alunos com matrícula em turno diferente do transporte' }}</span></div>
                                @endif
                            </td>
                            <td>{{ $codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota }}</td>
                            <td>{{ number_format((float) $rota->km_pav + (float) $rota->km_npav, 1, ',', '.') }}</td>
                            <td>{{ $rota->km_calculado === null ? '—' : number_format((float) $rota->km_calculado, 1, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="4" class="vazio">Nenhuma rota neste ano.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($podeGravar)
        <dialog class="modal" id="nova-rota">
            <div class="modal-cabeca">
                <h2>Nova rota</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" action="{{ url('/transporte-escolar/rotas') }}">
                @csrf
                <label>Ano <input name="ano" value="{{ $ano }}" required></label>
                <label>Descrição <input name="descricao" required maxlength="80"></label>
                <div class="linha">
                    <label>Tipo <select name="tipo_rota">@foreach($codigos::TIPO_ROTA as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                    <label>Período <select name="periodo">@foreach($codigos::PERIODO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                </div>
                <label>Sentido <select name="sentido">@foreach($codigos::SENTIDO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                <div class="linha">
                    <label>Km pavimentado <input name="km_pav"></label>
                    <label>Km não pavimentado <input name="km_npav"></label>
                </div>
                <label>Velocidade média (km/h) <input name="velocidade_media_kmh" value="40"></label>
                <label><input type="checkbox" name="terceirizada" value="1"> Terceirizada</label>
                <div>
                    <strong>Escolas atendidas</strong>
                    @foreach($escolas as $escola)
                        <label><input type="checkbox" name="escolas[]" value="{{ $escola->cod_escola }}"> {{ $escola->nome }}</label>
                    @endforeach
                </div>
                <button type="submit">Gravar rota</button>
            </form>
        </dialog>
    @endif
@endsection
