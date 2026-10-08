@if($situacao === 'aberto')
    <form method="post" class="acao" action="{{ url('/transporte-escolar/solicitacoes/'.$alvo.'/'.$id.'/situacao') }}">
        @csrf
        <input type="hidden" name="acao" value="pausar">
        <button type="submit" class="botao claro">Pausar</button>
    </form>
@elseif($situacao === 'pausado')
    <form method="post" class="acao" action="{{ url('/transporte-escolar/solicitacoes/'.$alvo.'/'.$id.'/situacao') }}">
        @csrf
        <input type="hidden" name="acao" value="retomar">
        <button type="submit" class="botao claro">Retomar</button>
    </form>
@endif
@if($situacao === 'bloqueado')
    <form method="post" class="acao" action="{{ url('/transporte-escolar/solicitacoes/'.$alvo.'/'.$id.'/situacao') }}">
        @csrf
        <input type="hidden" name="acao" value="desbloquear">
        <button type="submit" class="botao claro">Desbloquear</button>
    </form>
@else
    <form method="post" class="acao" action="{{ url('/transporte-escolar/solicitacoes/'.$alvo.'/'.$id.'/situacao') }}">
        @csrf
        <input type="hidden" name="acao" value="bloquear">
        <button type="submit" class="botao claro">Bloquear</button>
    </form>
@endif
