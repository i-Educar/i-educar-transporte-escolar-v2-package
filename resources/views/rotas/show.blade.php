@extends('transporte-escolar::layout')

@section('transporte')
    <h1>{{ $rota->descricao }} · {{ $rota->ano }}</h1>
    <p>{{ $codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota }} · {{ $codigos::SENTIDO[$rota->sentido] ?? $rota->sentido }} · {{ $alunos }} alunos</p>
    <p>Km informado: {{ number_format($kmInformado, 1, ',', '.') }} · Km calculado: {{ number_format($kmCalculado, 1, ',', '.') }} · Tempo estimado: {{ $minutos }} min</p>

    <div id="mapa" style="height:420px; margin:12px 0; border:1px solid #ccc;"></div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const pontos = @json($itinerario->map(fn ($p) => ['id' => $p->ponto_id, 'nome' => $p->descricao, 'lat' => $p->latitude, 'lng' => $p->longitude]));
        const escolas = @json($escolas);
        const mapa = L.map('mapa').setView([-14.2, -51.9], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
        const coords = [];
        pontos.forEach((ponto) => {
            if (!ponto.lat || !ponto.lng) return;
            coords.push([ponto.lat, ponto.lng]);
            L.marker([ponto.lat, ponto.lng]).addTo(mapa).bindPopup(ponto.nome);
        });
        escolas.forEach((escola) => {
            if (!escola.latitude || !escola.longitude) return;
            L.circleMarker([escola.latitude, escola.longitude], { radius: 7 }).addTo(mapa).bindPopup(escola.nome);
        });
        if (coords.length) {
            L.polyline(coords).addTo(mapa);
            mapa.fitBounds(coords, { padding: [20, 20] });
        }
        @if($podeMapa)
        mapa.on('click', (evento) => {
            document.querySelector('[name=latitude]').value = evento.latlng.lat.toFixed(7);
            document.querySelector('[name=longitude]').value = evento.latlng.lng.toFixed(7);
        });
        @endif
    </script>

    <h2>Itinerário</h2>
    <ol>
        @foreach($itinerario as $parada)
            <li>{{ $parada->descricao }} @if($parada->hora) às {{ substr($parada->hora, 0, 5) }} @endif @if($parada->placa) · {{ $parada->placa }} ({{ $parada->passageiros }} lugares) @endif</li>
        @endforeach
    </ol>

    @if($podeGravar)
        <h2>Incluir ponto no itinerário</h2>
        <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/itinerario') }}">
            @csrf
            <select name="ponto_id" required>
                @foreach($pontos as $ponto)<option value="{{ $ponto->id }}">{{ $ponto->descricao }}</option>@endforeach
            </select>
            <select name="veiculo_id">
                <option value="">Sem veículo</option>
                @foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach
            </select>
            <input name="hora" placeholder="hh:mm">
            <select name="tipo"><option value="I">Ida</option><option value="V">Volta</option></select>
            <button type="submit">Incluir</button>
        </form>
        @if($temTracado)
            <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/ordem') }}">@csrf<button type="submit">Sugerir ordem pela trilha</button></form>
        @endif
    @endif

    @if($podeMapa)
        <h2>Ponto no mapa</h2>
        <p>Clique no mapa e grave a coordenada. A latitude da escola continua em pmieducar.escola.</p>
        <form method="post" action="{{ url('/transporte-escolar/pontos') }}">
            @csrf
            <input name="descricao" placeholder="Nome do ponto" required>
            <input name="latitude" placeholder="Latitude">
            <input name="longitude" placeholder="Longitude">
            <button type="submit">Criar ponto</button>
        </form>
        @if($pontos->isNotEmpty())
            <form method="post" action="{{ url('/transporte-escolar/pontos/'.$pontos->first()->id.'/coordenada') }}">
                @csrf
                <select name="ponto_id" onchange="this.form.action='{{ url('/transporte-escolar/pontos') }}/'+this.value+'/coordenada'">
                    @foreach($pontos as $ponto)<option value="{{ $ponto->id }}">{{ $ponto->descricao }}</option>@endforeach
                </select>
                <input name="latitude" required> <input name="longitude" required>
                <button type="submit">Gravar coordenada</button>
            </form>
        @endif
        <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/gpx') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="gpx" accept=".gpx,application/gpx+xml" required>
            <button type="submit">Importar GPX</button>
        </form>
        @foreach($escolas as $escola)
            <form method="post" action="{{ url('/transporte-escolar/escolas/'.$escola->cod_escola.'/coordenada') }}">
                @csrf
                <span>{{ $escola->nome }}</span>
                <input name="latitude" value="{{ $escola->latitude }}" required>
                <input name="longitude" value="{{ $escola->longitude }}" required>
                <button type="submit">Atualizar escola</button>
            </form>
        @endforeach
    @endif
@endsection
