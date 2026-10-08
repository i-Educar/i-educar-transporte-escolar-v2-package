@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Aviso à família</h1>
    <p>O link abre sem login e sem aplicativo. Não há envio de SMS nem de notificação do celular.</p>
    <ul>
        @foreach($avisos as $aviso)
            <li>
                {{ \Illuminate\Support\Carbon::parse($aviso->data)->format('d/m/Y') }} · {{ $aviso->texto }}
                @if($aviso->fora_da_cerca) · fora da cerca @endif
                · <a href="{{ url('/transporte-escolar/aviso/'.$aviso->token) }}">abrir o link</a>
                @if($aviso->lido_em) · lido @endif
            </li>
        @endforeach
    </ul>
@endsection
