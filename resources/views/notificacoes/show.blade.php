@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa {{ $linha->icone }}" aria-hidden="true"></i> {{ $linha->titulo }}</h1>
    <p class="lead">A leitura saiu da contagem do sino. A fila do painel segue enquanto a pendência existir.</p>
    <article class="cartao-rota">
        <p><span class="selo {{ $linha->tom === 'lotacao' ? 'alerta' : ($linha->tom === 'prazo' ? 'prazo' : ($linha->tom === 'servico' ? 'ok' : '')) }}">{{ $rotulos[$linha->tom] ?? $linha->tom }}</span></p>
        <p>{{ $linha->texto }}</p>
        <p>{{ $linha->detalhe }}</p>
        <dl class="ficha">
            <dt>Atualizado</dt>
            <dd>{{ \Illuminate\Support\Carbon::parse($linha->updated_at)->format('d/m/Y H:i') }}</dd>
            <dt>Situação</dt>
            <dd>{{ $linha->ativa ? 'Ainda pede ação' : 'A condição já não está ativa' }}</dd>
        </dl>
        <div class="acao">
            <a class="botao" href="{{ url($linha->url) }}"><i class="fa fa-external-link" aria-hidden="true"></i> Abrir a tela</a>
            <a class="botao claro" href="{{ url('/transporte-escolar/notificacoes') }}">Voltar à lista</a>
        </div>
    </article>
@endsection
