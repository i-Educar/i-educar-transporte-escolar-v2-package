@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-dashboard" aria-hidden="true"></i> Painel</h1>
    <p class="lead">A cor segue a ordem do dia: rede, ano, viagem, frota e contas. O nome de cada ícone aparece ao passar o mouse.</p>
    <ol class="fluxograma">
        @foreach(\iEducar\Packages\BuritiTransporteEscolar\Support\MenuFluxo::grupos($menuTransporte) as $grupo)
            @continue($grupo['numero'] === null)
            <li class="etapa" data-passo="{{ $grupo['numero'] }}">
                <header>
                    <span class="passo">{{ $grupo['numero'] }}</span>
                    <span>
                        <strong>{{ $grupo['nome'] }}</strong>
                        <small>{{ $grupo['frase'] }}</small>
                    </span>
                </header>
                <div class="icones-fluxo">
                    @foreach($grupo['itens'] as $item)
                        <a href="{{ $item['url'] }}" aria-label="{{ $item['rotulo'] }}">
                            <i class="fa {{ $item['icone'] }}" aria-hidden="true"></i>
                            <span class="dica" aria-hidden="true">{{ $item['rotulo'] }}</span>
                        </a>
                    @endforeach
                </div>
            </li>
        @endforeach
    </ol>
    <section class="card-avisos" aria-labelledby="titulo-avisos">
        <h2 id="titulo-avisos"><i class="fa fa-bell" aria-hidden="true"></i> Notificações e informes</h2>
        @php
            $visiveis = array_values(array_filter($avisos, static fn (array $aviso): bool => !empty($menuTransporte[$aviso['chave']])));
            $rotulos = ['pedido' => 'Pedidos', 'prazo' => 'Prazos', 'lotacao' => 'Lotação', 'servico' => 'Serviços'];
            $contagem = array_count_values(array_column($visiveis, 'tom'));
        @endphp
        @if($visiveis !== [])
            <div class="indicadores">
                @foreach($rotulos as $tom => $rotulo)
                    @if(($contagem[$tom] ?? 0) > 0)
                        <span class="{{ $tom }}"><i></i>{{ $contagem[$tom] }} {{ $rotulo }}</span>
                    @endif
                @endforeach
            </div>
        @endif
        @if($visiveis === [])
            <p class="vazio">Nenhum informe pendente.</p>
        @else
            <ul>
                @foreach($visiveis as $aviso)
                    <li class="{{ $aviso['tom'] }}">
                        <i class="fa {{ $aviso['icone'] }}" aria-hidden="true"></i>
                        <span>{{ $aviso['texto'] }}</span>
                        <a href="{{ url($aviso['url']) }}">Abrir</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
