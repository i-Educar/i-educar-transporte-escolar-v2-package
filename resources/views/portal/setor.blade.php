@extends('transporte-escolar::portal.layout')

@section('titulo', 'Setor de transporte escolar')

@section('portal')
    <h1>Setor de transporte escolar</h1>
    <p class="lead">{{ $setor }}</p>
    @if($endereco !== '')
        <p>{{ $endereco }}</p>
    @endif
    @if($horario !== '')
        <p>Atendimento: {{ $horario }}</p>
    @endif
    <div id="mapa" style="height: 420px; border-radius: 12px; margin-top: 12px;"></div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const ponto = @json($mapa);
        const mapa = L.map('mapa').setView([ponto.lat, ponto.lon], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
        L.marker([ponto.lat, ponto.lon]).addTo(mapa).bindPopup(@json($setor)).openPopup();
    </script>
@endsection
