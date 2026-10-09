@extends('transporte-escolar::layout')

@section('transporte')
    <h1>{{ $rota->descricao }} · {{ $rota->ano }}</h1>
    <p>{{ $codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota }} · {{ $codigos::SENTIDO[$rota->sentido] ?? $rota->sentido }} · {{ $alunos }} alunos</p>
    <p>Km informado: {{ number_format($kmInformado, 1, ',', '.') }} · Km calculado: {{ number_format($kmCalculado, 1, ',', '.') }} · Tempo estimado: {{ $minutos }} min</p>

    @php
        $pontosMapa = [];
        foreach ($cobertura['pontos'] as $ponto) {
            $pontosMapa[] = [
                'nome' => $ponto['nome'],
                'lat' => $ponto['lat'],
                'lng' => $ponto['lng'],
                'raio' => $ponto['raio'],
                'alunos' => $ponto['alunos'],
                'embarques' => $ponto['embarques'],
                'desembarques' => $ponto['desembarques'],
                'faixas' => $ponto['faixas'],
            ];
        }
        $escolasMapa = [];
        foreach ($escolas as $escola) {
            if (\iEducar\Packages\BuritiTransporteEscolar\Support\Coordenada::noTerritorio($escola->latitude, $escola->longitude) !== null) {
                continue;
            }
            $escolasMapa[] = [
                'nome' => $escola->nome,
                'lat' => $escola->latitude,
                'lng' => $escola->longitude,
            ];
        }
        $calorMapa = $cobertura['calor'];
    @endphp
    <p class="lead">A escola fica em verde. O início da linha fica em azul e o fim em vermelho. O traço laranja segue o percurso do veículo quando há trilha gravada; sem ela, o mapa pede o caminho pelas ruas.</p>
    <p id="percurso" class="lead">Calculando a distância do percurso…</p>
    <div id="mapa" style="height:480px; margin:12px 0; border:1px solid var(--linha); border-radius:12px;"></div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const pontos = @json($pontosMapa);
        const escolas = @json($escolasMapa);
        const calor = @json($calorMapa);
        const trilha = @json($trilha);
        const raioEscola = {{ (int) $raioEscola }};
        const mapa = L.map('mapa').setView([-14.2, -51.9], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
        const coords = [];
        const paradas = pontos.filter((ponto) => ponto.lat && ponto.lng);
        paradas.forEach((ponto, indice) => {
            coords.push([ponto.lat, ponto.lng]);
            const extremo = indice === 0 ? 'inicio' : (indice === paradas.length - 1 ? 'fim' : 'meio');
            const cor = extremo === 'inicio' ? '#188ad1' : (extremo === 'fim' ? '#9b2c2c' : '#47728f');
            let texto = (extremo === 'inicio' ? 'Início da linha' : (extremo === 'fim' ? 'Fim da linha' : 'Parada')) + '<br>' + ponto.nome;
            texto += '<br>' + ponto.alunos + ' aluno(s) vinculado(s)';
            texto += '<br>Entradas: ' + ponto.embarques + ' · Saídas: ' + ponto.desembarques;
            if (ponto.faixas) {
                Object.keys(ponto.faixas).forEach((faixa) => { texto += '<br>' + faixa + ': ' + ponto.faixas[faixa]; });
            }
            L.circle([ponto.lat, ponto.lng], { radius: ponto.raio || 150, color: cor, weight: 1, fillOpacity: 0.08 }).addTo(mapa);
            L.circleMarker([ponto.lat, ponto.lng], { radius: extremo === 'meio' ? 6 : 9, color: cor, fillColor: cor, fillOpacity: 1 }).addTo(mapa).bindPopup(texto);
        });
        escolas.forEach((escola) => {
            if (!escola.lat || !escola.lng) return;
            coords.push([escola.lat, escola.lng]);
            L.circle([escola.lat, escola.lng], { radius: raioEscola, color: '#0f6b4c', weight: 1, fillColor: '#0f6b4c', fillOpacity: 0.08 }).addTo(mapa);
            L.circleMarker([escola.lat, escola.lng], { radius: 9, color: '#0f6b4c', fillColor: '#0f6b4c', fillOpacity: 1 }).addTo(mapa).bindPopup('Escola<br>' + escola.nome);
        });
        calor.forEach((celula) => {
            coords.push([celula.latitude, celula.longitude]);
            L.circleMarker([celula.latitude, celula.longitude], {
                radius: 8 + celula.alunos,
                color: '#c45c26',
                fillOpacity: 0.35
            }).addTo(mapa).bindPopup(celula.alunos + ' residências nesta área');
        });
        const avisoPercurso = document.getElementById('percurso');
        function desenharPercurso(latlngs, texto) {
            if (latlngs.length > 1) {
                const linha = L.polyline(latlngs, { color: '#b86e00', weight: 5, opacity: 0.9 }).addTo(mapa);
                latlngs.forEach((ponto) => coords.push(ponto));
                if (avisoPercurso) avisoPercurso.textContent = texto;
                linha.bringToBack();
            }
        }
        if (trilha.length > 2) {
            let metros = 0;
            for (let i = 1; i < trilha.length; i++) {
                metros += mapa.distance(trilha[i - 1], trilha[i]);
            }
            desenharPercurso(trilha, 'Percurso gravado da trilha: ' + (metros / 1000).toFixed(1).replace('.', ',') + ' km.');
            if (coords.length) mapa.fitBounds(coords, { padding: [20, 20] });
        } else if (paradas.length > 1) {
            const caminho = paradas.map((ponto) => ponto.lng + ',' + ponto.lat).join(';');
            fetch('https://router.project-osrm.org/route/v1/driving/' + caminho + '?overview=full&geometries=geojson')
                .then((resposta) => resposta.json())
                .then((dados) => {
                    const rota = dados.routes && dados.routes[0];
                    if (!rota) throw new Error('sem rota');
                    const linha = rota.geometry.coordinates.map((par) => [par[1], par[0]]);
                    const minutos = Math.round(rota.duration / 60);
                    desenharPercurso(linha, 'Percurso pelas ruas: ' + (rota.distance / 1000).toFixed(1).replace('.', ',') + ' km · cerca de ' + minutos + ' min.');
                    if (coords.length) mapa.fitBounds(coords, { padding: [20, 20] });
                })
                .catch(() => {
                    desenharPercurso(paradas.map((ponto) => [ponto.lat, ponto.lng]), 'O caminho pelas ruas não respondeu. A linha mostra a ligação direta entre as paradas.');
                    if (coords.length) mapa.fitBounds(coords, { padding: [20, 20] });
                });
        } else if (avisoPercurso) {
            avisoPercurso.textContent = 'Ainda não há paradas suficientes para calcular o percurso.';
        }
        if (coords.length && trilha.length < 3 && paradas.length < 2) {
            mapa.fitBounds(coords, { padding: [20, 20] });
        }
        @if($podeMapa)
        let escuta = 'novo';
        window.escolherClique = function (qual) {
            escuta = qual;
            const aviso = document.getElementById('mapa-aviso');
            if (aviso) {
                aviso.textContent = qual === 'novo'
                    ? 'O próximo clique preenche o ponto novo.'
                    : 'O próximo clique ajusta o ponto selecionado.';
            }
        };
        mapa.on('click', (evento) => {
            const lat = document.getElementById(escuta === 'novo' ? 'lat-novo' : 'lat-ajuste');
            const lng = document.getElementById(escuta === 'novo' ? 'lng-novo' : 'lng-ajuste');
            if (!lat || !lng) return;
            lat.value = evento.latlng.lat.toFixed(7);
            lng.value = evento.latlng.lng.toFixed(7);
        });
        window.preencherAjuste = function (select) {
            const opcao = select.options[select.selectedIndex];
            document.getElementById('lat-ajuste').value = opcao.getAttribute('data-lat') || '';
            document.getElementById('lng-ajuste').value = opcao.getAttribute('data-lng') || '';
            document.getElementById('form-ajuste').action = '{{ url('/transporte-escolar/pontos') }}/' + opcao.value + '/coordenada';
            window.escolherClique('ajuste');
        };
        @endif
    </script>

    <h2>Pontos da rota</h2>
    <p class="lead">A ida sobe os alunos no ponto e deixa todos na última parada. A volta sobe todos na primeira parada e deixa cada um no próprio ponto. “No ônibus” é quem continua a bordo depois daquela parada.</p>
    <p class="legenda-viagem"><span class="marca-ida">Ida</span><span class="marca-volta">Volta</span></p>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Viagem</th><th>Ordem</th><th>Ponto</th><th>Hora</th><th>Embarques</th><th>Desembarques</th><th>No ônibus</th></tr></thead>
            <tbody>
            @forelse($movimento as $passo)
                <tr class="{{ $passo['sentido'] === 'V' ? 'viagem-volta' : 'viagem-ida' }}">
                    <td>{{ $passo['rotulo'] }}</td>
                    <td>{{ $passo['seq'] }}</td>
                    <td>{{ $passo['nome'] }}</td>
                    <td>{{ $passo['hora'] }}</td>
                    <td>{{ $passo['embarcam'] }}</td>
                    <td>{{ $passo['desembarcam'] }}</td>
                    <td>{{ $passo['a_bordo'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vazio">Esta rota ainda não tem pontos no itinerário.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <h2>Itinerário</h2>
    <p class="lead">A lista é a ordem da viagem. A faixa azul é a ida e a faixa âmbar é a volta. Incluir uma parada usa um ponto que já existe. Criar um ponto novo é outro passo: o clique no mapa preenche só o formulário que estiver escutando.</p>
    <p class="legenda-viagem"><span class="marca-ida">Ida</span><span class="marca-volta">Volta</span></p>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Ordem</th><th>Ponto</th><th>Sentido</th><th>Hora</th><th>Veículo</th><th>No mapa</th><th></th></tr></thead>
            <tbody>
            @forelse($itinerario as $parada)
                <tr class="{{ $parada->tipo === 'V' ? 'viagem-volta' : 'viagem-ida' }}">
                    <td>{{ $parada->seq }}</td>
                    <td>{{ $parada->descricao }}</td>
                    <td>{{ $parada->tipo === 'V' ? 'Volta' : 'Ida' }}</td>
                    <td>{{ $parada->hora ? substr($parada->hora, 0, 5) : '—' }}</td>
                    <td>{{ $parada->placa ?: '—' }}</td>
                    <td>{{ $parada->latitude === null ? 'Sem coordenada' : 'Sim' }}</td>
                    <td class="acoes">
                        @if($podeGravar)
                            <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/itinerario/'.$parada->id.'/remover') }}">
                                @csrf
                                <button type="submit" class="icone cancelar" aria-label="Retirar parada" title="Retirar"><i class="fa fa-times" aria-hidden="true"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="vazio">Nenhuma parada nesta rota.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($podeGravar)
        <section class="cartao-rota">
            <h2>1. Incluir parada</h2>
            <p class="lead">Usa um ponto já cadastrado. Não grava coordenada.</p>
            @if($pontos->isEmpty())
                <p>Ainda não há ponto cadastrado. Crie o ponto no passo 2.</p>
            @else
                <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/itinerario') }}">
                    @csrf
                    <label>Ponto
                        <select name="ponto_id" required>
                            @foreach($pontos as $ponto)
                                <option value="{{ $ponto->id }}">{{ $ponto->descricao }}{{ $ponto->latitude === null ? ' · sem coordenada' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Veículo
                        <select name="veiculo_id">
                            <option value="">Sem veículo</option>
                            @foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach
                        </select>
                    </label>
                    <label>Hora <input name="hora" type="time"></label>
                    <label>Sentido
                        <select name="tipo"><option value="I">Ida</option><option value="V">Volta</option></select>
                    </label>
                    <button type="submit">Incluir parada</button>
                </form>
            @endif
        </section>
    @endif

    @if($podeMapa)
        <section class="cartao-rota" id="criar-ponto">
            <h2>2. Criar ponto e colocar nesta rota</h2>
            <p class="lead" id="mapa-aviso">O próximo clique preenche o ponto novo.</p>
            <button type="button" class="botao claro" onclick="escolherClique('novo')">Usar o clique neste ponto</button>
            <form method="post" action="{{ url('/transporte-escolar/pontos') }}">
                @csrf
                <input type="hidden" name="rota_id" value="{{ $rota->id }}">
                <label>Nome <input name="descricao" required maxlength="70"></label>
                <label>Latitude <input id="lat-novo" name="latitude" required></label>
                <label>Longitude <input id="lng-novo" name="longitude" required></label>
                <label>Hora <input name="hora" type="time"></label>
                <label>Sentido
                    <select name="tipo"><option value="I">Ida</option><option value="V">Volta</option></select>
                </label>
                <label>Veículo
                    <select name="veiculo_id">
                        <option value="">Sem veículo</option>
                        @foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach
                    </select>
                </label>
                <button type="submit">Criar e incluir</button>
            </form>
        </section>
        @php $pontosDaRota = $itinerario->unique('ponto_id')->values(); @endphp
        @if($pontosDaRota->isNotEmpty())
            <section class="cartao-rota">
                <h2>3. Ajustar coordenada de um ponto desta rota</h2>
                <p class="lead">O clique no mapa só entra aqui depois de escolher “Usar o clique neste ajuste”.</p>
                <form method="post" id="form-ajuste" action="{{ url('/transporte-escolar/pontos/'.$pontosDaRota->first()->ponto_id.'/coordenada') }}">
                    @csrf
                    <label>Ponto
                        <select id="ponto-ajuste" onchange="preencherAjuste(this)">
                            @foreach($pontosDaRota as $ponto)
                                <option value="{{ $ponto->ponto_id }}" data-lat="{{ $ponto->latitude }}" data-lng="{{ $ponto->longitude }}">{{ $ponto->descricao }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Latitude <input id="lat-ajuste" name="latitude" value="{{ $pontosDaRota->first()->latitude }}" required></label>
                    <label>Longitude <input id="lng-ajuste" name="longitude" value="{{ $pontosDaRota->first()->longitude }}" required></label>
                    <button type="button" class="botao claro" onclick="escolherClique('ajuste')">Usar o clique neste ajuste</button>
                    <button type="submit">Gravar coordenada</button>
                </form>
            </section>
        @endif
        <section class="cartao-rota">
            <h2>Escola e traçado</h2>
            <p class="lead">A coordenada da escola não muda com o clique do mapa. O cálculo usa os pontos do itinerário e grava o caminho, o quilômetro e o tempo.</p>
            @if($podeGravar)
                <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/percurso') }}">
                    @csrf
                    <button type="submit">Calcular percurso pelas ruas</button>
                </form>
            @endif
            <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/gpx') }}" enctype="multipart/form-data">
                @csrf
                <label>Arquivo GPX <input type="file" name="gpx" accept=".gpx,application/gpx+xml" required></label>
                <button type="submit">Importar GPX</button>
            </form>
            @if($temTracado && $podeGravar)
                <form method="post" action="{{ url('/transporte-escolar/rotas/'.$rota->id.'/ordem') }}">@csrf<button type="submit">Sugerir ordem pela trilha</button></form>
            @endif
            @foreach($escolas as $escola)
                @php $fora = \iEducar\Packages\BuritiTransporteEscolar\Support\Coordenada::noTerritorio($escola->latitude, $escola->longitude); @endphp
                <form method="post" action="{{ url('/transporte-escolar/escolas/'.$escola->cod_escola.'/coordenada') }}">
                    @csrf
                    <label>{{ $escola->nome }}
                        @if($fora)<small>{{ $fora }} Ela fica fora do mapa até ser corrigida.</small>@endif
                    </label>
                    <label>Latitude <input name="latitude" value="{{ $escola->latitude }}" required></label>
                    <label>Longitude <input name="longitude" value="{{ $escola->longitude }}" required></label>
                    <button type="submit">Atualizar escola</button>
                </form>
            @endforeach
        </section>
    @endif
    <style>
        .legenda-viagem { display: flex; gap: 8px; margin: 0 0 10px; }
        .legenda-viagem span { display: inline-block; border-radius: 999px; padding: 2px 10px; font-size: .82rem; font-weight: 700; }
        .marca-ida { background: #e7f1f6; color: #188ad1; }
        .marca-volta { background: #fff6e8; color: #b86e00; }
        .conteudo tr.viagem-ida td { background: #e7f1f6; }
        .conteudo tr.viagem-ida td:first-child { box-shadow: inset 4px 0 #188ad1; }
        .conteudo tr.viagem-volta td { background: #fff6e8; }
        .conteudo tr.viagem-volta td:first-child { box-shadow: inset 4px 0 #b86e00; }
    </style>
@endsection
