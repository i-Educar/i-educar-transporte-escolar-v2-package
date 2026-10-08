@extends('transporte-escolar::portal.layout')

@section('titulo', 'Transporte escolar para a família')

@section('portal')
    <h1>Peça o ônibus da escola</h1>
    <p class="lead">Quem cuida do aluno abre o pedido aqui. A escola encaminha. A secretaria aceita ou recusa. Você acompanha sem entrar no sistema da prefeitura.</p>
    <div class="passos">
        <article class="passo">
            <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg></span>
            <strong>1. Conte quem é o aluno</strong>
            <p>Nome, nascimento, telefone e endereço de saída. O ano, a escola e a rota saem do processo de vagas que estiver aberto.</p>
        </article>
        <article class="passo">
            <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/></svg></span>
            <strong>2. Guarde o protocolo</strong>
            <p>Ele começa com TE, traz o ano e um número. Anote também a data de nascimento: sem ela a consulta não abre.</p>
        </article>
        <article class="passo">
            <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></span>
            <strong>3. Veja o andamento</strong>
            <p>Recebido, em análise, aceito ou recusado. Enquanto não houver decisão, você ainda pode cancelar.</p>
        </article>
    </div>
    <div class="cartao">
        <p>O pedido não cria matrícula e não reserva vaga no ônibus. Aceitar o pedido significa que a secretaria vai incluir o aluno numa rota.</p>
        <div class="acoes">
            <a class="botao" href="{{ url('/transporte-escolar/familia/pedir') }}">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg></span>
                Abrir um pedido
            </a>
            <a class="botao claro" href="{{ url('/transporte-escolar/familia/protocolo') }}">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/></svg></span>
                Já tenho protocolo
            </a>
        </div>
    </div>
    <p class="aviso"><strong>Cuidado com pedido de senha.</strong> A prefeitura não pede dados do aluno por rede social e não pede senha. O andamento fica só na página Acompanhar. Se alguém pedir senha ou documento por WhatsApp, Instagram ou outra rede, não envie.</p>
@endsection
