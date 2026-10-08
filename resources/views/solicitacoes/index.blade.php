@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Pedidos da família</h1>
    <p>A família abre o pedido em <a href="{{ url('/transporte-escolar/familia') }}">/transporte-escolar/familia</a>, sem login. A escola encaminha o pedido da própria escola. A rede aceita ou recusa. O andamento a família consulta na página Acompanhar.</p>
    @if($rede)
        <h2>Setor de atendimento</h2>
        <p>Estes dados aparecem para a família só quando o pedido é aceito: documentos, endereço e mapa.</p>
        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/atendimento') }}">
            @csrf
            <p><label>Nome do setor <input name="setor" value="{{ $atendimento['setor'] }}"></label></p>
            <p><label>Endereço <input name="endereco" value="{{ $atendimento['endereco'] }}" required></label></p>
            <p><label>Horário <input name="horario" value="{{ $atendimento['horario'] }}"></label></p>
            <p><label>Documentos que a família deve levar <textarea name="documentos" rows="3">{{ $atendimento['documentos'] }}</textarea></label></p>
            <p><label>Latitude <input name="latitude" value="{{ $atendimento['latitude'] }}"></label></p>
            <p><label>Longitude <input name="longitude" value="{{ $atendimento['longitude'] }}"></label></p>
            <p><button type="submit">Gravar atendimento</button></p>
        </form>
        <h2>Processo de vagas</h2>
        <p>Como na pré-matrícula: um processo vale para um ano letivo e um período. A família só pede escola e rota publicadas nele, enquanto houver vaga.</p>
        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/processo') }}">
            @csrf
            <p><label>Nome <input name="nome" required placeholder="Transporte 2026"></label></p>
            <p><label>Ano letivo <input name="ano" type="number" value="{{ date('Y') }}" required></label></p>
            <p><label>Início <input name="inicio" type="date" required></label></p>
            <p><label>Fim <input name="fim" type="date" required></label></p>
            <p><button type="submit">Abrir processo</button></p>
        </form>
        @if($processos->isNotEmpty() && $rotas->isNotEmpty())
            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/oferta') }}">
                @csrf
                <p><label>Processo
                    <select name="processo_id" required>
                        @foreach($processos as $processo)
                            <option value="{{ $processo->id }}">{{ $processo->ano }} · {{ $processo->nome }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p><label>Escola e rota
                    <select name="rota_escola" id="rota_escola" required>
                        @foreach($rotas as $rota)
                            <option value="{{ $rota->id }}|{{ $rota->cod_escola }}" data-turno="{{ $rota->periodo }}">{{ $rota->ano }} · {{ $rota->escola }} · {{ $rota->descricao }}</option>
                        @endforeach
                    </select>
                </label></p>
                <input type="hidden" name="rota_id" id="rota_id">
                <input type="hidden" name="ref_cod_escola" id="ref_cod_escola">
                <p><label>Turno
                    <select name="turno" required>
                        @foreach(\iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO as $codigo => $rotulo)
                            <option value="{{ $codigo }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p><label>Vagas <input name="vagas" type="number" min="1" value="1" required></label></p>
                <p><button type="submit">Publicar vaga</button></p>
            </form>
            <script>
                document.querySelector('form[action$="/oferta"]').addEventListener('submit', function () {
                    var partes = document.getElementById('rota_escola').value.split('|');
                    document.getElementById('rota_id').value = partes[0];
                    document.getElementById('ref_cod_escola').value = partes[1];
                });
            </script>
        @endif
        @if($processos->isNotEmpty())
            <ul>
                @foreach($processos as $processo)
                    <li>{{ $processo->ano }} · {{ $processo->nome }} · {{ \Illuminate\Support\Carbon::parse($processo->inicio)->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($processo->fim)->format('d/m/Y') }}</li>
                @endforeach
            </ul>
        @endif
    @endif
    <table>
        <thead>
            <tr><th>Protocolo</th><th>Aluno</th><th>Escola</th><th>Situação</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($pedidos as $pedido)
                <tr>
                    <td>{{ $pedido->protocolo }}</td>
                    <td>{{ $pedido->nome_aluno }}</td>
                    <td>{{ $pedido->escola_informada }}</td>
                    <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</td>
                    <td>
                        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                            @csrf
                            <input name="motivo" placeholder="Observação">
                            @if($pedido->situacao === 'recebida')
                                <button name="acao" value="encaminhar">Encaminhar</button>
                            @endif
                            @if($rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                                <button name="acao" value="deferir">Aceitar</button>
                                <button name="acao" value="indeferir">Recusar</button>
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum pedido nesta visão.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
