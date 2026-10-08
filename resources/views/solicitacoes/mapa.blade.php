@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-map-marker" aria-hidden="true"></i> Mapa do protocolo {{ $protocolo }}</h1>
    <p class="lead">{{ $endereco }}</p>
    <p class="lead">Este mapa mostra o endereço e os pontos cadastrados. Não traz nome, telefone nem nascimento.</p>
    @if($local === null)
        <p class="flash erro">Não foi possível localizar este endereço. Confira rua, número e cidade no pedido.</p>
    @else
        @php
            $origemMapa = ['lat' => $local['latitude'], 'lng' => $local['longitude']];
            $pontosMapa = $pontos;
        @endphp
        <div id="mapa" style="height:520px; border:1px solid var(--linha); border-radius:12px;"></div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const origem = @json($origemMapa);
            const pontos = @json($pontosMapa);
            const mapa = L.map('mapa').setView([origem.lat, origem.lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
            L.marker([origem.lat, origem.lng]).addTo(mapa).bindPopup('Endereço informado');
            const limites = [[origem.lat, origem.lng]];
            pontos.forEach(function (ponto) {
                limites.push([ponto.latitude, ponto.longitude]);
                L.circle([ponto.latitude, ponto.longitude], { radius: ponto.raio || 150, color: '#47728f', weight: 1 }).addTo(mapa);
                L.circleMarker([ponto.latitude, ponto.longitude], { radius: 6, color: '#188ad1' }).addTo(mapa)
                    .bindPopup(ponto.nome + '<br>' + ponto.metros + ' m');
            });
            mapa.fitBounds(limites, { padding: [24, 24] });
        </script>
        @if($pontos === [])
            <p class="lead">O endereço foi localizado. Ainda não há ponto com latitude e longitude para calcular a distância.</p>
        @else
        <h2>Distância até os pontos</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Ponto</th><th>Distância</th><th>Raio</th></tr></thead>
                <tbody>
                @foreach($pontos as $ponto)
                    <tr>
                        <td>{{ $ponto['nome'] }}</td>
                        <td>{{ $ponto['metros'] >= 1000 ? number_format($ponto['metros'] / 1000, 1, ',', '.') . ' km' : $ponto['metros'] . ' m' }}</td>
                        <td>{{ $ponto['dentro'] ? 'Dentro de ' . $ponto['raio'] . ' m' : $ponto['raio'] . ' m' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endif
@endsection
