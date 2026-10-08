@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-wrench" aria-hidden="true"></i> Ordens de serviço</h1>
    <p class="lead">A ordem descreve o serviço a realizar. O PDF é o controle impresso. O valor só entra no custo quando a ordem fica concluída.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar protocolo, veículo ou serviço"></label>
            <a class="botao claro" href="{{ url('/transporte-escolar/ordens.csv') }}"><i class="fa fa-download" aria-hidden="true"></i> Exportar</a>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="nova-ordem"><i class="fa fa-plus" aria-hidden="true"></i> Solicitar</button>
            @endif
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Protocolo</th><th>Situação</th><th>Veículo</th><th>Serviço</th><th>Valor</th><th></th></tr></thead>
                <tbody>
                    @forelse($ordens as $ordem)
                        <tr data-busca="{{ $ordem->protocolo }} {{ $ordem->placa }} {{ $ordem->veiculo }} {{ $ordem->descricao }} {{ $ordem->situacao }}">
                            <td>{{ $ordem->protocolo ?: '—' }}</td>
                            <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::frase((string) $ordem->situacao) }}</td>
                            <td>{{ $ordem->placa ?: $ordem->veiculo }}</td>
                            <td>{{ $ordem->descricao }}</td>
                            <td>{{ number_format((float) $ordem->valor, 2, ',', '.') }}</td>
                            <td class="acao">
                                @if($ordem->protocolo)
                                    <a href="{{ url('/transporte-escolar/ordens/'.$ordem->id.'/pdf') }}">Imprimir</a>
                                @endif
                                @php $proximas = \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::proximas(\iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::ORDEM, (string) $ordem->situacao); @endphp
                                @if($podeGravar && $proximas !== [])
                                    <form method="post" action="{{ url('/transporte-escolar/ordens/'.$ordem->id.'/situacao') }}" class="acao">
                                        @csrf
                                        <select name="situacao">
                                            @foreach($proximas as $codigo)
                                                <option value="{{ $codigo }}">{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::frase($codigo) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Atualizar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhuma ordem.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($podeGravar)
        <dialog class="modal" id="nova-ordem">
            <div class="modal-cabeca">
                <h2>Solicitar ordem</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post" action="{{ url('/transporte-escolar/ordens') }}">
                @csrf
                <label>Veículo
                    <select name="veiculo_id" required>
                        @foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach
                    </select>
                </label>
                <label>Fornecedor
                    <select name="fornecedor_id"><option value="">A definir</option>@foreach($fornecedores as $fornecedor)<option value="{{ $fornecedor->id }}">{{ $fornecedor->nome }}</option>@endforeach</select>
                </label>
                <label>Serviço a realizar <input name="descricao" required maxlength="500"></label>
                <div class="linha">
                    <label>Peça <input name="peca"></label>
                    <label>Valor previsto <input name="valor" required></label>
                </div>
                <div class="linha">
                    <label>Mão de obra <input name="mao_obra"></label>
                    <label>Data <input type="date" name="data" value="{{ date('Y-m-d') }}" required></label>
                </div>
                <label>Fonte <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                <label>Quem pediu <input name="solicitante" placeholder="Escola, motorista ou setor"></label>
                <label><input type="checkbox" name="preventiva" value="1"> Preventiva</label>
                <button type="submit">Solicitar ordem</button>
            </form>
        </dialog>
    @endif
@endsection
