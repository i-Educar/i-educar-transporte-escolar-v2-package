@extends('layout.default')

@section('content')
    <div style="padding:16px 24px 40px; max-width:1100px;">
        <p style="margin:0 0 8px;"><a href="{{ url('/transporte-escolar') }}">Transporte escolar</a></p>
        <p style="margin:0 0 16px; color:#444;">{{ $visaoRotulo }}</p>
        <p style="margin:0 0 20px;">
            @if($menuTransporte['rotas']) <a href="{{ url('/transporte-escolar/rotas') }}">Rotas</a> @endif
            @if($menuTransporte['alunos']) · <a href="{{ url('/transporte-escolar/alunos') }}">Alunos</a> @endif
            @if($menuTransporte['frota']) · <a href="{{ url('/transporte-escolar/veiculos') }}">Veículos</a> · <a href="{{ url('/transporte-escolar/motoristas') }}">Motoristas</a> · <a href="{{ url('/transporte-escolar/monitores') }}">Monitores</a> · <a href="{{ url('/transporte-escolar/empresas') }}">Empresas</a> · <a href="{{ url('/transporte-escolar/pontos') }}">Pontos</a> @endif
            @if($menuTransporte['copia']) · <a href="{{ url('/transporte-escolar/copia-ano') }}">Cópia do ano</a> @endif
            @if($menuTransporte['sete']) · <a href="{{ url('/transporte-escolar/sete') }}">SETE</a> @endif
            @if($menuTransporte['viagens']) · <a href="{{ url('/transporte-escolar/viagens') }}">Viagens</a> @endif
            @if($menuTransporte['offline']) · <a href="{{ url('/transporte-escolar/offline') }}">Offline</a> @endif
            @if($menuTransporte['gastos']) · <a href="{{ url('/transporte-escolar/gastos') }}">Gastos</a> @endif
            @if($menuTransporte['relatorios']) · <a href="{{ url('/transporte-escolar/relatorios') }}">Relatórios</a> @endif
            @if($menuTransporte['cacs']) · <a href="{{ url('/transporte-escolar/cacs') }}">CACS</a> @endif
            @if($menuTransporte['avisos']) · <a href="{{ url('/transporte-escolar/avisos') }}">Família</a> @endif
            @if($menuTransporte['normas']) · <a href="{{ url('/transporte-escolar/normas') }}">Normas</a> @endif
        </p>
        @if(session('ok')) <p style="background:#e7f6e7; padding:8px 12px;">{{ session('ok') }}</p> @endif
        @if(session('erro')) <p style="background:#fde8e8; padding:8px 12px;">{{ session('erro') }}</p> @endif
        @yield('transporte')
    </div>
@endsection
