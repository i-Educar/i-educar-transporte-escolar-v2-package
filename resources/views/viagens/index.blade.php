@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-clock-o" aria-hidden="true"></i> Viagens</h1>
    <p class="lead">A falta aqui é falta no transporte. A frequência da aula continua no diário.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar data, rota ou sentido"></label>
            <button type="button" class="botao" data-abre-modal="abrir-viagem"><i class="fa fa-plus" aria-hidden="true"></i> Abrir viagem</button>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Data</th><th>Rota</th><th>Sentido</th><th>Km realizado</th></tr></thead>
                <tbody>
                    @forelse($viagens as $viagem)
                        @php($km = \iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado::deOdometro($viagem->km_inicial === null ? null : (float) $viagem->km_inicial, $viagem->km_final === null ? null : (float) $viagem->km_final))
                        <tr data-busca="{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }} {{ $viagem->descricao }} {{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}">
                            <td><a href="{{ url('/transporte-escolar/viagens/'.$viagem->id) }}">{{ \Illuminate\Support\Carbon::parse($viagem->data)->format('d/m/Y') }}</a></td>
                            <td>{{ $viagem->descricao }}</td>
                            <td>{{ $sentidos[$viagem->sentido] ?? $viagem->sentido }}</td>
                            <td>{{ $km === null ? '—' : $km }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="4" class="vazio">Nenhuma viagem.</td></tr>
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
@endsection
