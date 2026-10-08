@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-clock-o" aria-hidden="true"></i> Viagens</h1>
    <p class="lead">A chamada e a frequência ficam nesta lista. O PDF de cada viagem usa o CPF abreviado e marca frequência ou ausência.</p>
    <form method="get" class="acao" style="margin-bottom:12px;">
        <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
        <label>Mês <input name="mes" value="{{ $mes }}" size="3"></label>
        <button type="submit">Ver mês</button>
    </form>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar data, rota ou sentido"></label>
            <button type="button" class="botao claro" data-abre-modal="lote-viagem"><i class="fa fa-calendar" aria-hidden="true"></i> Dias letivos</button>
            <button type="button" class="botao" data-abre-modal="abrir-viagem"><i class="fa fa-plus" aria-hidden="true"></i> Abrir viagem</button>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Data</th><th>Horário</th><th>Rota</th><th>Sentido</th><th>Km realizado</th><th>Ações</th></tr></thead>
                <tbody>
                    @forelse($viagens as $viagem)
                        @php($km = \iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado::deOdometro($viagem->km_inicial === null ? null : (float) $viagem->km_inicial, $viagem->km_final === null ? null : (float) $viagem->km_final))
                        <tr data-busca="{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }} {{ $viagem->descricao }} {{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}">
                            <td>{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}</td>
                            <td>{{ $viagem->hora ? substr((string) $viagem->hora, 0, 5) : '—' }}</td>
                            <td>{{ $viagem->descricao }}</td>
                            <td>{{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}</td>
                            <td>{{ $km === null ? '—' : $km }}</td>
                            <td class="acoes">
                                <a class="icone ver" href="{{ url('/transporte-escolar/viagens/'.$viagem->id) }}" aria-label="Abrir chamada" title="Chamada"><i class="fa fa-book" aria-hidden="true"></i></a>
                                <a class="icone imprimir" href="{{ url('/transporte-escolar/frequencia/'.$viagem->id.'/pdf') }}" aria-label="Emitir frequência" title="Frequência"><i class="fa fa-print" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhuma viagem neste mês.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <dialog class="modal" id="abrir-viagem">
        <div class="modal-cabeca">
            <h2>Abrir viagem</h2>
            <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
        </div>
        <form method="post">
            @csrf
            <label>Data <input type="date" name="data" required value="{{ date('Y-m-d') }}"></label>
            <label>Rota <select name="rota_id" required>@foreach($rotas as $rota)<option value="{{ $rota->id }}">{{ $rota->descricao }} ({{ $rota->ano }})</option>@endforeach</select></label>
            <label>Veículo <select name="veiculo_id"><option value="">—</option>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label>
            <label>Motorista <select name="motorista_id"><option value="">—</option>@foreach($motoristas as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label>
            <label>Monitor <select name="monitor_id"><option value="">—</option>@foreach($monitores as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label>
            <label>Sentido <select name="sentido">@foreach($sentidos as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
            <div class="linha">
                <label>Km inicial <input name="km_inicial"></label>
                <label>Km final <input name="km_final"></label>
            </div>
            <button type="submit">Abrir</button>
        </form>
    </dialog>
    <dialog class="modal" id="lote-viagem">
        <div class="modal-cabeca">
            <h2>Viagens dos dias letivos</h2>
            <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
        </div>
        <form method="post" action="{{ url('/transporte-escolar/viagens/lote') }}">
            @csrf
            <p class="lead">Abre a ida e a volta de cada dia letivo, no horário da primeira parada da linha. Sem calendário da escola, usa os dias de semana.</p>
            <div class="linha">
                <label>Ano <input name="ano" type="number" required value="{{ $ano }}"></label>
                <label>Mês <input name="mes" type="number" min="1" max="12" required value="{{ $mes }}"></label>
            </div>
            <label>Rota <select name="rota_id" required>@foreach($rotas as $rota)<option value="{{ $rota->id }}">{{ $rota->descricao }} ({{ $rota->ano }})</option>@endforeach</select></label>
            <label>Veículo <select name="veiculo_id"><option value="">—</option>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label>
            <label>Motorista <select name="motorista_id"><option value="">—</option>@foreach($motoristas as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label>
            <label>Monitor <select name="monitor_id"><option value="">—</option>@foreach($monitores as $pessoa)<option value="{{ $pessoa->id }}">{{ $pessoa->nome }}</option>@endforeach</select></label>
            <button type="submit">Gerar viagens</button>
        </form>
    </dialog>
@endsection
