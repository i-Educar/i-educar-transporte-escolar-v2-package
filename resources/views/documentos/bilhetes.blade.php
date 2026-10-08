@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-tint" aria-hidden="true"></i> Bilhetes de abastecimento</h1>
    <p class="lead">O bilhete autoriza o posto. O PDF traz o protocolo e o canhoto. O custo só recebe o abastecimento confirmado.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar protocolo, veículo ou posto"></label>
            <a class="botao claro" href="{{ url('/transporte-escolar/bilhetes.csv') }}"><i class="fa fa-download" aria-hidden="true"></i> Exportar</a>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="novo-bilhete"><i class="fa fa-plus" aria-hidden="true"></i> Solicitar</button>
            @endif
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Protocolo</th><th>Situação</th><th>Veículo</th><th>Autorizado</th><th>Abastecido</th><th></th></tr></thead>
                <tbody>
                    @forelse($bilhetes as $bilhete)
                        <tr data-busca="{{ $bilhete->protocolo }} {{ $bilhete->placa }} {{ $bilhete->veiculo }} {{ $bilhete->posto }} {{ $bilhete->situacao }}">
                            <td>{{ $bilhete->protocolo ?: '—' }}</td>
                            <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::frase((string) ($bilhete->situacao ?? 'abastecido')) }}</td>
                            <td>{{ $bilhete->placa ?: $bilhete->veiculo }}</td>
                            <td>{{ $bilhete->litros_autorizados === null ? '—' : number_format((float) $bilhete->litros_autorizados, 2, ',', '.') }} L</td>
                            <td>{{ number_format((float) $bilhete->litros, 2, ',', '.') }} L · {{ number_format((float) $bilhete->valor, 2, ',', '.') }}</td>
                            <td>
                                @if($bilhete->protocolo)
                                    <a href="{{ url('/transporte-escolar/bilhetes/'.$bilhete->id.'/pdf') }}">Imprimir</a>
                                @endif
                                @php $proximas = \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::proximas(\iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::BILHETE, (string) ($bilhete->situacao ?? '')); @endphp
                                @if($podeGravar && $proximas !== [])
                                    <form method="post" action="{{ url('/transporte-escolar/bilhetes/'.$bilhete->id.'/situacao') }}" class="acao">
                                        @csrf
                                        <select name="situacao">
                                            @foreach($proximas as $codigo)
                                                <option value="{{ $codigo }}">{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::frase($codigo) }}</option>
                                            @endforeach
                                        </select>
                                        <input name="litros" placeholder="Litros" size="6">
                                        <input name="valor" placeholder="Valor" size="6">
                                        <input name="km" placeholder="Km" size="5">
                                        <button type="submit">Atualizar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhum bilhete.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($podeGravar)
        <dialog class="modal" id="novo-bilhete">
            <div class="modal-cabeca">
                <h2>Solicitar bilhete</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" action="{{ url('/transporte-escolar/bilhetes') }}">
                @csrf
                <label>Veículo
                    <select name="veiculo_id" required>
                        @foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach
                    </select>
                </label>
                <div class="linha">
                    <label>Data <input type="date" name="data" value="{{ date('Y-m-d') }}" required></label>
                    <label>Litros autorizados <input name="litros_autorizados" required></label>
                </div>
                <div class="linha">
                    <label>Posto <input name="posto"></label>
                    <label>Motorista <input name="motorista"></label>
                </div>
                <label>Quem pediu <input name="solicitante" placeholder="Escola ou setor"></label>
                <label>Fonte <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                <button type="submit">Solicitar bilhete</button>
            </form>
        </dialog>
    @endif
@endsection
