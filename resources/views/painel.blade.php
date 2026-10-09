@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $rotulos = ['pedido' => 'Pedidos', 'prazo' => 'Prazos', 'lotacao' => 'Lotação', 'servico' => 'Serviços'];
        $contagem = array_count_values(array_column($avisos, 'tom'));
        $fontes = ['pnate' => 'PNATE', 'proprio' => 'Recurso próprio', 'outros' => 'Outras fontes'];
        $total = ($custo['pnate'] ?? 0) + ($custo['proprio'] ?? 0) + ($custo['outros'] ?? 0);
    @endphp
    <style>
        .painel-tela { display: grid; gap: 12px; }
        .painel-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 0; align-items: stretch; }
        .kpi-coluna { display: flex; flex-direction: column; min-width: 0; position: relative; overflow: hidden; background: linear-gradient(180deg, color-mix(in srgb, var(--cor, var(--azul)) 9%, #fff), #fff 88px); border: 1px solid color-mix(in srgb, var(--cor, var(--azul)) 28%, #d5e2ea); border-radius: 14px; padding: 16px 16px 14px; box-shadow: 0 8px 22px rgba(21, 34, 43, .04); }
        .kpi-coluna header { display: flex; align-items: center; gap: 8px; margin: 0; color: var(--suave); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; line-height: 1.25; }
        .kpi-coluna header i { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; flex: none; color: var(--cor, var(--azul)); background: #fff; border: 1px solid color-mix(in srgb, var(--cor, var(--azul)) 22%, #e6edf2); box-shadow: 0 4px 10px color-mix(in srgb, var(--cor, var(--azul)) 16%, transparent); }
        .kpi-coluna .geral { margin: 14px 0 0; font-size: 2rem; font-weight: 800; letter-spacing: -.04em; line-height: .95; color: var(--tinta); font-variant-numeric: tabular-nums; }
        .kpi-coluna .geral small { display: block; margin-top: 6px; color: var(--cor, var(--azul)); font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .kpi-coluna ul { list-style: none; display: grid; gap: 6px; margin: 14px 0 0; padding: 0; overflow: auto; max-height: 220px; }
        .kpi-coluna li { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 10px; align-items: center; padding: 8px 10px; border: 1px solid color-mix(in srgb, var(--cor, var(--azul)) 18%, #e3ebf0); border-radius: 8px; background: color-mix(in srgb, var(--cor, var(--azul)) 7%, #fff); font-size: .76rem; }
        .kpi-coluna li span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--suave); }
        .kpi-coluna li strong { justify-self: end; color: var(--tinta); font-weight: 800; font-variant-numeric: tabular-nums; }
        .kpi-faixa { grid-column: 1 / -1; height: 4px; border-radius: 99px; background: #eef2f5; overflow: hidden; }
        .kpi-faixa i { display: block; height: 100%; border-radius: 99px; background: var(--cor, var(--azul)); }
        .painel-miolo { display: grid; grid-template-columns: minmax(0, 1fr) 320px; grid-template-areas: "mapa fila" "contas fila"; gap: 12px; align-items: stretch; }
        .painel-mapa, .painel-contas { margin: 0; }
        .painel-mapa { grid-area: mapa; display: flex; flex-direction: column; gap: 8px; }
        .painel-mapa h2, .painel-contas h2 { margin: 0; }
        .painel-mapa .lead { margin: 0; }
        #mapa-painel { height: 420px; border: 1px solid var(--linha); border-radius: 4px; }
        .camadas-mapa, .legenda-mapa { display: flex; flex-wrap: wrap; gap: 6px; margin: 0; }
        .camadas-mapa label, .legenda-mapa button { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--linha); background: #fff; color: var(--tinta); border-radius: 4px; padding: 4px 8px; font: inherit; font-size: .78rem; cursor: pointer; }
        .legenda-mapa { max-height: 96px; overflow: auto; padding: 2px; }
        .legenda-mapa button.ativa { border-color: var(--azul); background: var(--azul-claro); }
        .legenda-mapa i.faixa { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
        .dados-rota { margin: 0; border: 1px solid var(--linha); border-left: 4px solid var(--azul); border-radius: 4px; background: #f7f9fa; padding: 10px 12px; }
        .dados-rota[hidden] { display: none; }
        .dados-rota h3 { margin: 0 0 6px; font-size: .95rem; }
        .dados-rota dl { display: grid; grid-template-columns: 110px 1fr; gap: 4px 8px; margin: 0; }
        .dados-rota dt { color: var(--suave); }
        .dados-rota dd { margin: 0; }
        .painel-contas { grid-area: contas; display: grid; gap: 10px; }
        .painel-contas .kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0; }
        .painel-contas .kpi { position: relative; min-height: 0; }
        .colunas-gasto { display: grid; grid-template-columns: repeat(auto-fit, minmax(84px, 1fr)); gap: 8px; align-items: end; margin: 0; padding: 12px; background: #f7f9fa; border: 1px solid var(--linha); border-radius: 4px; }
        .coluna-gasto { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 6px; min-width: 0; min-height: 168px; }
        .coluna-gasto > strong { font-size: .72rem; color: var(--azul-escuro); text-align: center; line-height: 1.2; }
        .coluna-gasto .haste { width: 22px; height: 96px; display: flex; align-items: end; background: #e7eef2; border-radius: 4px; overflow: hidden; }
        .coluna-gasto .haste i { display: block; width: 100%; min-height: 4px; background: #5c4d7a; border-radius: 4px 4px 0 0; }
        .coluna-gasto > em { font-style: normal; font-size: .72rem; line-height: 1.2; color: var(--suave); text-align: center; }
        .painel-contas .dica-gasto { display: none; position: absolute; z-index: 6; left: 0; top: calc(100% + 6px); width: max-content; max-width: 260px; background: #15222b; color: #fff; border-radius: 4px; padding: 8px 10px; font-size: .75rem; box-shadow: 0 8px 18px rgba(21, 34, 43, .2); }
        .coluna-gasto .dica-gasto { left: 50%; top: auto; bottom: calc(100% + 6px); transform: translateX(-50%); }
        .painel-contas .kpi:hover .dica-gasto, .painel-contas .kpi:focus-within .dica-gasto, .coluna-gasto:hover .dica-gasto, .coluna-gasto:focus-within .dica-gasto { display: block; }
        .dica-gasto strong { display: block; margin: 0 0 4px; color: #fff; }
        .dica-gasto ul { list-style: none; margin: 0; padding: 0; }
        .dica-gasto li { display: flex; justify-content: space-between; gap: 12px; padding: 2px 0; border: 0; background: transparent; }
        .painel-fila { grid-area: fila; display: flex; flex-direction: column; margin: 0; min-height: 0; max-height: 100%; overflow: hidden; }
        .painel-fila ul { overflow: auto; min-height: 0; }
        .painel-fila li { align-items: center; }
        .painel-fila li > a { margin-left: auto; }
        .painel-fila .botao { padding: 4px 8px; font-size: .75rem; }
        .marca-mapa { background: transparent; border: 0; }
        .marca-mapa span { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; color: #fff; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,.35); font-size: 11px; }
        @media (max-width: 900px) {
            .painel-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .painel-kpis { grid-template-columns: 1fr; }
        }
        @media (max-width: 1100px) {
            .painel-miolo { grid-template-columns: 1fr; grid-template-areas: "mapa" "fila" "contas"; }
            .painel-fila { max-height: 420px; }
        }
    </style>
    <h1><i class="fa fa-th-large" aria-hidden="true"></i> Painel</h1>
    <p class="lead">{{ $visaoRotulo }} · {{ $meses[$mes] }} de {{ $ano }}. Cada cor no mapa é uma rota. A fila fica ao lado até a tarefa ser baixada.</p>
    <div class="painel-tela">
        <div class="painel-kpis">
            @foreach($cartoes as $cartao)
                @php
                    $cores = ['azul' => '#47728f', 'destaque' => '#188ad1', 'verde' => '#0f6b4c', 'ambar' => '#b86e00', 'roxo' => '#5c4d7a', 'alerta' => '#9b2c2c'];
                @endphp
                <article class="kpi-coluna" style="--cor: {{ $cores[$cartao['tom']] ?? '#1f4e66' }}">
                    <header><i class="fa {{ $cartao['icone'] }}" aria-hidden="true"></i> {{ $cartao['rotulo'] }}</header>
                    <p class="geral">{{ $cartao['valor'] }} <small>Total</small></p>
                    <ul>
                        @forelse($cartao['itens'] as $item)
                            @php
                                $texto = trim((string) $item['valor']);
                                $parte = preg_match('/^\d+(?:[.,]\d+)?$/', $texto) === 1
                                    ? (float) str_replace(',', '.', $texto)
                                    : null;
                                $tetoCard = is_numeric($cartao['valor']) ? (float) $cartao['valor'] : 0;
                                $largura = $parte !== null && $tetoCard > 0
                                    ? min(100, max(4, (int) round($parte / $tetoCard * 100)))
                                    : null;
                            @endphp
                            <li>
                                <span>{{ $item['rotulo'] }}</span>
                                <strong>{{ $item['valor'] }}</strong>
                                @if($largura !== null)
                                    <span class="kpi-faixa" aria-hidden="true"><i style="width: {{ $largura }}%"></i></span>
                                @endif
                            </li>
                        @empty
                            <li><span>Nenhum item neste recorte</span><strong>0</strong></li>
                        @endforelse
                    </ul>
                </article>
            @endforeach
        </div>
        <div class="painel-miolo">
            <section class="bloco painel-mapa">
                <h2>Rotas no mapa</h2>
                <p class="lead">O prédio verde é a escola. Ida e volta com os mesmos pontos viram um traçado só. Clique na rota para ver tempo, distância, alunos, veículos e motoristas.</p>
                <div class="camadas-mapa">
                    <label><input type="checkbox" data-camada="rotas" checked> Rotas</label>
                    <label><input type="checkbox" data-camada="escolas" checked> Escolas</label>
                    <label><input type="checkbox" data-camada="detalhes" checked> Detalhes</label>
                    <label><input type="checkbox" data-camada="dados" checked> Dados</label>
                </div>
                <div id="mapa-painel"></div>
                <article class="dados-rota" id="dados-rota">
                    <h3 id="dados-rota-nome">Selecione uma rota</h3>
                    <dl>
                        <dt>Tempo</dt><dd id="dados-rota-tempo">—</dd>
                        <dt>Distância</dt><dd id="dados-rota-km">—</dd>
                        <dt>Alunos</dt><dd id="dados-rota-alunos">—</dd>
                        <dt>Veículos</dt><dd id="dados-rota-veiculos">—</dd>
                        <dt>Motoristas</dt><dd id="dados-rota-motoristas">—</dd>
                        <dt>Traçado</dt><dd id="dados-rota-tracado">—</dd>
                    </dl>
                </article>
                <div class="legenda-mapa" id="legenda-mapa">
                    <button type="button" data-rota="escolas"><i class="faixa" style="background:#0f6b4c"></i> Escolas</button>
                    @foreach($mapa['rotas'] as $rota)
                        @continue($rota['pontos'] === [])
                        <button type="button" data-rota="{{ $rota['id'] }}"><i class="faixa" style="background:{{ $rota['cor'] }}"></i> {{ $rota['nome'] }} · {{ $rota['alunos'] }} alunos</button>
                    @endforeach
                </div>
            </section>
            <aside class="fila painel-fila" aria-labelledby="titulo-avisos">
                <header>
                    <h2 id="titulo-avisos">Fila de atenção</h2>
                    <a href="{{ url('/transporte-escolar/notificacoes') }}">Caixa</a>
                </header>
                @if($avisos !== [])
                    <div class="indicadores">
                        @foreach($rotulos as $tom => $rotulo)
                            @if(($contagem[$tom] ?? 0) > 0)
                                <span class="{{ $tom }}"><i></i>{{ $contagem[$tom] }} {{ $rotulo }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if($avisos === [])
                    <p class="vazio">Nenhuma pendência em aberto.</p>
                @else
                    <ul>
                        @foreach($avisos as $aviso)
                            <li class="{{ $aviso->tom }}">
                                <i class="fa {{ $aviso->icone }}" aria-hidden="true"></i>
                                <span><strong>{{ $aviso->titulo }}</strong><br>{{ $aviso->texto }}</span>
                                <a href="{{ url($aviso->url) }}">Abrir</a>
                                @if($aviso->lida_em)
                                    <span class="selo ok">Lida</span>
                                @else
                                    <form method="post" action="{{ url('/transporte-escolar/notificacoes/'.$aviso->id.'/lida') }}">
                                        @csrf
                                        <button type="submit" class="botao claro">Marcar lida</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>
            @if($podeCusto && $custo)
                <section class="bloco painel-contas">
                    <h2>Gastos de {{ $meses[$mes] }} <small>R$ {{ number_format((float) $total, 2, ',', '.') }} no mês</small></h2>
                    <div class="kpis">
                        @foreach($fontes as $codigo => $rotulo)
                            <article class="kpi {{ $codigo === 'pnate' ? 'roxo' : ($codigo === 'proprio' ? 'destaque' : 'ambar') }}" tabindex="0">
                                <span><strong>R$ {{ number_format((float) $custo[$codigo], 2, ',', '.') }}</strong><em>{{ $rotulo }}</em></span>
                                <div class="dica-gasto" role="tooltip">
                                    <strong>{{ $rotulo }}</strong>
                                    @if(($porFonte[$codigo] ?? []) === [])
                                        <span>Nenhum lançamento nesta fonte.</span>
                                    @else
                                        <ul>
                                            @foreach($porFonte[$codigo] as $nome => $valorFonte)
                                                <li><span>{{ $nome }}</span><strong>R$ {{ number_format((float) $valorFonte, 2, ',', '.') }}</strong></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if($categorias === [])
                        <p class="vazio">Nenhum gasto entrou neste mês.</p>
                    @else
                        <div class="colunas-gasto">
                            @foreach($categorias as $categoria)
                                <div class="coluna-gasto" tabindex="0">
                                    <strong>R$ {{ number_format($categoria['valor'], 2, ',', '.') }}</strong>
                                    <span class="haste"><i style="height: {{ max(8, (int) round($categoria['valor'] / $teto * 100)) }}%"></i></span>
                                    <em>{{ $categoria['rotulo'] }}</em>
                                    <div class="dica-gasto" role="tooltip">
                                        <strong>{{ $categoria['rotulo'] }} · R$ {{ number_format($categoria['valor'], 2, ',', '.') }}</strong>
                                        <ul>
                                            @foreach($categoria['fontes'] as $fonte => $valorFonte)
                                                <li><span>{{ $fontes[$fonte] ?? $fonte }}</span><strong>R$ {{ number_format((float) $valorFonte, 2, ',', '.') }}</strong></li>
                                            @endforeach
                                            <li><span>Parte do mês</span><strong>{{ $total > 0 ? number_format($categoria['valor'] / $total * 100, 1, ',', '.') : '0,0' }}%</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            var dados = @json($mapa);
            var mapa = L.map('mapa-painel').setView([-14.2, -51.9], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
            var grupos = {};
            var camadaRotas = L.featureGroup().addTo(mapa);
            var camadaEscolas = L.featureGroup().addTo(mapa);
            var camadaDetalhes = L.featureGroup().addTo(mapa);

            function icone(cor, simbolo) {
                return L.divIcon({
                    className: 'marca-mapa',
                    html: '<span style="background:' + cor + '"><i class="fa ' + simbolo + '"></i></span>',
                    iconSize: [22, 22],
                    iconAnchor: [11, 11]
                });
            }

            function chavePontos(pontos) {
                return pontos.map(function (ponto) { return ponto.lat.toFixed(5) + ',' + ponto.lng.toFixed(5); }).join('|');
            }

            function mesmoTracado(ida, volta) {
                if (!ida.length || ida.length !== volta.length) {
                    return false;
                }
                var direto = chavePontos(ida) === chavePontos(volta);
                var reverso = chavePontos(ida) === chavePontos(volta.slice().reverse());

                return direto || reverso;
            }

            function lista(itens, vazio) {
                return itens.length ? itens.join(', ') : vazio;
            }

            function abrir(rota, frase) {
                document.getElementById('dados-rota-nome').textContent = rota.nome;
                document.getElementById('dados-rota-tempo').textContent = rota.tempo === null ? 'Sem tempo estimado' : rota.tempo + ' min';
                document.getElementById('dados-rota-km').textContent = Number(rota.km).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' km';
                document.getElementById('dados-rota-alunos').textContent = String(rota.alunos);
                document.getElementById('dados-rota-veiculos').textContent = lista(rota.veiculos, 'Nenhum veículo no itinerário');
                document.getElementById('dados-rota-motoristas').textContent = lista(rota.motoristas, 'Nenhum motorista no veículo');
                document.getElementById('dados-rota-tracado').textContent = frase;
                document.querySelector('[data-camada="dados"]').checked = true;
                aplicarCamadas();
                document.querySelectorAll('#legenda-mapa button').forEach(function (item) { item.classList.remove('ativa'); });
                var botao = document.querySelector('#legenda-mapa button[data-rota="' + rota.id + '"]');
                if (botao) {
                    botao.classList.add('ativa');
                }
            }

            dados.escolas.forEach(function (escola) {
                L.marker([escola.lat, escola.lng], { icon: icone('#0f6b4c', 'fa-university') }).bindPopup(escola.nome).addTo(camadaEscolas);
            });

            dados.rotas.forEach(function (rota) {
                if (!rota.pontos.length) {
                    return;
                }
                var ida = rota.pontos.filter(function (ponto) { return ponto.tipo === 'I'; });
                var volta = rota.pontos.filter(function (ponto) { return ponto.tipo === 'V'; });
                var outros = rota.pontos.filter(function (ponto) { return ponto.tipo !== 'I' && ponto.tipo !== 'V'; });
                var trechos = [];
                var frase = 'Sem pontos de ida ou volta';

                if (ida.length && volta.length && mesmoTracado(ida, volta)) {
                    trechos.push({ pontos: ida, tracejado: false, simbolo: 'fa-exchange' });
                    frase = 'Ida e volta no mesmo traçado';
                } else {
                    if (ida.length) {
                        trechos.push({ pontos: ida, tracejado: false, simbolo: 'fa-arrow-right' });
                    }
                    if (volta.length) {
                        trechos.push({ pontos: volta, tracejado: true, simbolo: 'fa-arrow-left' });
                    }
                    frase = ida.length && volta.length ? 'Ida e volta com pontos diferentes' : (volta.length ? 'Só a volta' : 'Só a ida');
                }
                if (outros.length) {
                    trechos.push({ pontos: outros, tracejado: false, simbolo: 'fa-map-marker' });
                }

                var linhas = L.featureGroup().addTo(camadaRotas);
                var pontos = L.featureGroup().addTo(camadaDetalhes);
                var vistos = {};
                trechos.forEach(function (trecho) {
                    if (trecho.pontos.length >= 2) {
                        L.polyline(trecho.pontos.map(function (ponto) { return [ponto.lat, ponto.lng]; }), {
                            color: rota.cor,
                            weight: 4,
                            opacity: 0.9,
                            dashArray: trecho.tracejado ? '8 6' : null
                        }).on('click', function () { abrir(rota, frase); }).addTo(linhas);
                    }
                    trecho.pontos.forEach(function (ponto) {
                        var chave = ponto.lat.toFixed(5) + ',' + ponto.lng.toFixed(5);
                        if (vistos[chave]) {
                            return;
                        }
                        vistos[chave] = true;
                        L.marker([ponto.lat, ponto.lng], { icon: icone(rota.cor, trecho.simbolo) })
                            .bindPopup('<strong>' + rota.nome + '</strong><br>' + ponto.nome)
                            .on('click', function () { abrir(rota, frase); })
                            .addTo(pontos);
                    });
                });
                grupos[rota.id] = L.featureGroup([linhas, pontos]);
                rota.frase = frase;
            });

            function aplicarCamadas() {
                document.querySelectorAll('[data-camada]').forEach(function (campo) {
                    var ligado = campo.checked;
                    if (campo.getAttribute('data-camada') === 'rotas') {
                        if (ligado) { camadaRotas.addTo(mapa); } else { mapa.removeLayer(camadaRotas); }
                    }
                    if (campo.getAttribute('data-camada') === 'escolas') {
                        if (ligado) { camadaEscolas.addTo(mapa); } else { mapa.removeLayer(camadaEscolas); }
                    }
                    if (campo.getAttribute('data-camada') === 'detalhes') {
                        if (ligado) { camadaDetalhes.addTo(mapa); } else { mapa.removeLayer(camadaDetalhes); }
                    }
                    if (campo.getAttribute('data-camada') === 'dados') {
                        document.getElementById('dados-rota').hidden = !ligado;
                        document.getElementById('legenda-mapa').hidden = !ligado;
                    }
                });
            }

            document.querySelectorAll('[data-camada]').forEach(function (campo) {
                campo.addEventListener('change', aplicarCamadas);
            });

            var tudo = L.featureGroup([camadaRotas, camadaEscolas, camadaDetalhes]);
            if (tudo.getLayers().length) {
                mapa.fitBounds(tudo.getBounds(), { padding: [24, 24] });
            }
            document.getElementById('legenda-mapa').addEventListener('click', function (evento) {
                var botao = evento.target.closest('button');
                if (!botao) {
                    return;
                }
                var chave = botao.getAttribute('data-rota');
                var grupo = chave === 'escolas' ? camadaEscolas : grupos[chave];
                var rota = dados.rotas.find(function (item) { return String(item.id) === chave; });
                if (rota) {
                    abrir(rota, rota.frase);
                }
                document.querySelectorAll('#legenda-mapa button').forEach(function (item) { item.classList.remove('ativa'); });
                botao.classList.add('ativa');
                if (grupo && grupo.getLayers().length) {
                    mapa.fitBounds(grupo.getBounds(), { padding: [28, 28] });
                }
            });
        })();
    </script>
@endsection
