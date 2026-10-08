@extends('transporte-escolar::portal.layout')

@section('titulo', 'Receber avisos do transporte')

@section('portal')
    <div class="cartao">
    <h1>Receber avisos no celular</h1>
    <p>Informe a matrícula e a data de nascimento do aluno. Se conferir, este navegador passa a mostrar o aviso de embarque e de desembarque. Não pedimos senha e não enviamos SMS.</p>
    @if($erro)
        <p class="aviso">{{ $erro }}</p>
    @endif
    @if($aluno)
        <p>Cadastro conferido para <strong>{{ $aluno->primeiro }}</strong>. Toque no botão para autorizar a notificação neste aparelho.</p>
        <button type="button" id="ativar" data-matricula="{{ $matricula }}" data-nascimento="{{ $nascimento }}" data-chave="{{ $chave }}">Quero receber os avisos</button>
        <p id="resultado"></p>
    @else
        <form method="post" action="{{ url('/transporte-escolar/avisos/cadastro') }}">
            <label>Matrícula <input name="matricula" inputmode="numeric" required value="{{ $matricula ?? '' }}"></label>
            <label>Data de nascimento <input type="date" name="nascimento" required value="{{ $nascimento ?? '' }}"></label>
            <button type="submit">Conferir</button>
        </form>
    @endif
    </div>
<script>
document.getElementById('ativar')?.addEventListener('click', async function () {
    var botao = this;
    var saida = document.getElementById('resultado');
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        saida.textContent = 'Este navegador não mostra notificação.';
        return;
    }
    var permissao = await Notification.requestPermission();
    if (permissao !== 'granted') {
        saida.textContent = 'A notificação não foi autorizada.';
        return;
    }
    var registro = await navigator.serviceWorker.register('{{ url('/transporte-escolar/avisos/sw.js') }}');
    var inscricao = await registro.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: chave(botao.dataset.chave)
    });
    var chaves = inscricao.toJSON().keys || {};
    var resposta = await fetch('{{ url('/transporte-escolar/avisos/cadastro/inscricao') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            matricula: botao.dataset.matricula,
            nascimento: botao.dataset.nascimento,
            endpoint: inscricao.endpoint,
            p256dh: chaves.p256dh || '',
            auth: chaves.auth || ''
        })
    });
    var dados = await resposta.json();
    if (!resposta.ok) {
        saida.textContent = dados.erro || 'Não foi possível cadastrar.';
        return;
    }
    var pronto = await navigator.serviceWorker.ready;
    pronto.active?.postMessage({ token: dados.token });
    saida.textContent = 'Cadastro feito. Os próximos avisos deste aluno aparecem neste aparelho.';
});
function chave(valor) {
    var texto = atob((valor + '==='.slice((valor.length + 3) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    var bytes = new Uint8Array(texto.length);
    for (var i = 0; i < texto.length; i++) bytes[i] = texto.charCodeAt(i);
    return bytes;
}
</script>
@endsection
