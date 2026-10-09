@extends('transporte-escolar::portal.layout')

@section('titulo', 'Protocolo do transporte escolar')

@section('portal')
    <h1>Acompanhar pedido</h1>
    <p class="lead">O QR Code do comprovante abre esta página. O aluno e a unidade aparecem abreviados. O status só abre depois da data de nascimento e dos 4 últimos números do telefone.</p>
    @if(session('ok')) <p class="ok">{{ session('ok') }}</p> @endif
    @if(session('erro')) <p class="erro">{{ session('erro') }}</p> @endif
    @if($novo)
        <div class="cartao ok">
            <span class="selo">Pedido recebido</span>
            <p>Anote este número. Sem ele, a secretaria não localiza o pedido por aqui.</p>
            <p class="protocolo">{{ $novo }}</p>
            <p class="dica">Para ver o andamento, informe também a data de nascimento e os 4 últimos números do telefone do pedido.</p>
        </div>
    @endif
    @if($aviso) <p class="erro">{{ $aviso }}</p> @endif
    @if(!empty($resumo) && empty($pedido))
        <div class="cartao">
            <p class="protocolo">{{ $resumo->protocolo }}</p>
            <p>{{ $resumo->aluno_publico }} · {{ $resumo->escola_publica }}</p>
            <p class="dica">O status fica oculto até a confirmação.</p>
        </div>
    @endif
    @if($pedido)
        <div class="cartao">
            <span class="selo {{ $pedido->situacao }}">{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</span>
            <p class="protocolo">{{ $pedido->protocolo }}</p>
            <p>{{ $pedido->aluno_publico }} · {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$pedido->turno] ?? $pedido->turno }} · {{ $pedido->escola_publica }}</p>
            @if(!empty($pedido->contato_escola))<p>Responsável da escola: {{ $pedido->contato_escola }}</p>@endif
            @if(!empty($pedido->contato_setor))<p>Setor de transporte: {{ $pedido->contato_setor }}</p>@endif
            @if($pedido->motivo_situacao)<p>{{ $pedido->motivo_situacao }}</p>@endif
            @if(\iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor::publicar($pedido->situacao) && !empty($pedido->documentos_em))
                <h2>Documentação conferida</h2>
                <p>O aluno foi incluído na rota {{ $pedido->rota_vinculada ?: 'de transporte escolar' }}.</p>
            @elseif(\iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor::publicar($pedido->situacao))
                <h2>Leve os documentos ao setor de {{ $atendimento['setor'] }}</h2>
                <p>O pedido foi aceito. A inclusão na rota acontece depois que a família apresenta os documentos nesse setor.</p>
                @if($atendimento['documentos'] !== '')
                    <p>{{ $atendimento['documentos'] }}</p>
                @else
                    <p class="dica">A lista de documentos ainda não foi publicada. Leve um documento do aluno e do responsável.</p>
                @endif
                @if($atendimento['horario'] !== '')
                    <p>Horário: {{ $atendimento['horario'] }}</p>
                @endif
                @if($atendimento['endereco'] !== '')
                    <p>{{ $atendimento['endereco'] }}</p>
                @else
                    <p class="dica">O endereço do setor ainda não foi publicado.</p>
                @endif
                @if($atendimento['mapa'])
                    <iframe class="mapa" title="Mapa do setor de {{ $atendimento['setor'] }}" src="https://www.openstreetmap.org/export/embed.html?bbox={{ $atendimento['mapa']['bbox'] }}&amp;layer=mapnik&amp;marker={{ $atendimento['mapa']['lat'] }}%2C{{ $atendimento['mapa']['lon'] }}"></iframe>
                    <p><a href="https://www.openstreetmap.org/?mlat={{ $atendimento['mapa']['lat'] }}&amp;mlon={{ $atendimento['mapa']['lon'] }}#map=17/{{ $atendimento['mapa']['lat'] }}/{{ $atendimento['mapa']['lon'] }}">Abrir o mapa em outra página</a></p>
                @endif
            @endif
            @if($pedido->situacao === 'espera')
                <h2>Lista de espera</h2>
                <p>O pedido foi analisado e a rota não tem vaga agora. O aluno permanece na lista até surgir uma vaga ou até a família cancelar.</p>
            @endif
            @if(in_array($pedido->situacao, ['recebida', 'em_analise', 'espera'], true))
                <p class="dica">Cancelar avisa a escola que a família não quer mais o transporte. Depois de aceito ou recusado, o cancelamento por aqui fecha.</p>
                <form method="post" action="{{ url('/transporte-escolar/familia/cancelar') }}">
                    @csrf
                    <input type="hidden" name="protocolo" value="{{ $pedido->protocolo }}">
                    <input type="hidden" name="nascimento" value="{{ request('nascimento') }}">
                    <input type="hidden" name="telefone" value="{{ request('telefone') }}">
                    <div class="acoes"><button class="botao perigo" type="submit">Cancelar pedido</button></div>
                </form>
            @endif
        </div>
    @endif
    <form class="cartao" method="post" action="{{ !empty($token) ? url('/te/'.$token) : url('/transporte-escolar/familia/protocolo') }}" style="margin-top:16px;">
        @csrf
        @if(empty($token))
            <label for="protocolo">Protocolo</label>
            <p class="dica">Copie com os traços. O último número confere se a digitação está certa.</p>
            <input id="protocolo" name="protocolo" value="{{ request('protocolo', $novo) }}" placeholder="TE-2026-000001-0" required>
        @endif
        <label for="nascimento">Data de nascimento do aluno</label>
        <input id="nascimento" name="nascimento" type="date" value="{{ request('nascimento') }}" required>
        <label for="telefone">4 últimos números do telefone</label>
        <p class="dica">Os mesmos números informados no pedido. Sem eles, o status não abre.</p>
        <input id="telefone" name="telefone" inputmode="numeric" maxlength="11" value="{{ request('telefone') }}" required>
        <div class="acoes">
            <button type="submit">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/></svg></span>
                Ver o status
            </button>
        </div>
    </form>
@endsection
