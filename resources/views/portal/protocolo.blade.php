@extends('transporte-escolar::portal.layout')

@section('titulo', 'Protocolo do transporte escolar')

@section('portal')
    <h1>Acompanhar pedido</h1>
    <p class="lead">O protocolo tem o formato TE-ano-número-dígito. Junto com a data de nascimento, ele mostra só o seu pedido.</p>
    @if(session('ok')) <p class="ok">{{ session('ok') }}</p> @endif
    @if(session('erro')) <p class="erro">{{ session('erro') }}</p> @endif
    @if($novo)
        <div class="cartao ok">
            <span class="selo">Pedido recebido</span>
            <p>Anote este número. Sem ele, a secretaria não localiza o pedido por aqui.</p>
            <p class="protocolo">{{ $novo }}</p>
            <p class="dica">Na próxima consulta, informe também a data de nascimento do aluno. Uma data diferente não mostra o pedido.</p>
        </div>
    @endif
    @if($aviso) <p class="erro">{{ $aviso }}</p> @endif
    @if($pedido)
        <div class="cartao">
            <span class="selo {{ $pedido->situacao }}">{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</span>
            <p class="protocolo">{{ $pedido->protocolo }}</p>
            <p>{{ $pedido->nome_aluno }} · {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$pedido->turno] ?? $pedido->turno }} · {{ $pedido->escola_informada }}@if(!empty($pedido->rota_nome)) · rota {{ $pedido->rota_nome }}@endif @if(!empty($pedido->processo_nome)) · {{ $pedido->processo_nome }}@endif</p>
            @if($pedido->motivo_situacao)<p>{{ $pedido->motivo_situacao }}</p>@endif
            @if(\iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor::publicar($pedido->situacao))
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
            @if(in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                <p class="dica">Cancelar avisa a escola que a família não quer mais o transporte. Depois de aceito ou recusado, o cancelamento por aqui fecha.</p>
                <form method="post" action="{{ url('/transporte-escolar/familia/cancelar') }}">
                    @csrf
                    <input type="hidden" name="protocolo" value="{{ $pedido->protocolo }}">
                    <input type="hidden" name="nascimento" value="{{ request('nascimento') }}">
                    <div class="acoes"><button class="botao perigo" type="submit">Cancelar pedido</button></div>
                </form>
            @endif
        </div>
    @endif
    <form class="cartao" method="post" action="{{ url('/transporte-escolar/familia/protocolo') }}" style="margin-top:16px;">
        @csrf
        <label for="protocolo">Protocolo</label>
        <p class="dica">Copie com os traços. O último número confere se a digitação está certa.</p>
        <input id="protocolo" name="protocolo" value="{{ request('protocolo') }}" placeholder="TE-2026-000001-0" required>
        <label for="nascimento">Data de nascimento do aluno</label>
        <p class="dica">A mesma data informada no pedido. Ela impede que outra pessoa abra o protocolo só com o número.</p>
        <input id="nascimento" name="nascimento" type="date" value="{{ request('nascimento') }}" required>
        <div class="acoes">
            <button type="submit">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/></svg></span>
                Consultar
            </button>
        </div>
    </form>
@endsection
