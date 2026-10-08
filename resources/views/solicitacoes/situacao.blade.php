@php $pausado = in_array($situacao, ['pausado', 'bloqueado'], true); @endphp
<form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$alvo.'/'.$id.'/situacao') }}">
    @csrf
    <input type="hidden" name="acao" value="{{ $pausado ? 'retomar' : 'pausar' }}">
    <button type="submit" class="icone {{ $pausado ? 'aceitar' : 'pausar' }}" aria-label="{{ $pausado ? 'Retomar' : 'Pausar' }}" title="{{ $pausado ? 'Retomar' : 'Pausar' }}"><i class="fa {{ $pausado ? 'fa-play' : 'fa-pause' }}" aria-hidden="true"></i></button>
</form>
