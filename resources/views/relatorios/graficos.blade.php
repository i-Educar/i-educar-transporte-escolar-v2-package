@php
    $mesesGrafico = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
    $fontesGrafico = $indicadores['fontes'];
    $tetoFonte = $fontesGrafico === null ? 0 : max($fontesGrafico['pnate'], $fontesGrafico['proprio'], $fontesGrafico['outros'], 0.01);
@endphp
<section class="painel-grupo" id="indicadores">
    <h2><i class="fa fa-bar-chart" aria-hidden="true"></i> Indicadores do ano</h2>
    <p class="lead">O planejado de cada mês soma o km cadastrado da rota, uma vez por viagem. O realizado soma o odômetro. A cobertura rural usa a zona do Censo. Não há cálculo do Ideges.</p>
    <div class="kpis">
        <article class="kpi verde"><span><strong>{{ $indicadores['cobertura']['percentual'] === null ? '—' : $indicadores['cobertura']['percentual'] . '%' }}</strong><em>Rurais na rota · {{ $indicadores['cobertura']['parte'] }} de {{ $indicadores['cobertura']['todo'] }}</em></span></article>
        <article class="kpi ambar"><span><strong>{{ $indicadores['censo']['sem_rota'] + $indicadores['censo']['sem_ficha'] }}</strong><em>Divergências do Censo · {{ $indicadores['censo']['sem_rota'] }} sem rota, {{ $indicadores['censo']['sem_ficha'] }} sem ficha</em></span></article>
        <article class="kpi azul"><span><strong>{{ $indicadores['preventiva']['percentual'] === null ? '—' : $indicadores['preventiva']['percentual'] . '%' }}</strong><em>Preventiva em dia · meta 90% · {{ $indicadores['preventiva']['parte'] }} de {{ $indicadores['preventiva']['todo'] }}</em></span></article>
        <article class="kpi destaque"><span><strong>{{ $indicadores['disponibilidade']['percentual'] === null ? '—' : $indicadores['disponibilidade']['percentual'] . '%' }}</strong><em>Dias com viagem · {{ $indicadores['disponibilidade']['parte'] }} de {{ $indicadores['disponibilidade']['todo'] }} letivos</em></span></article>
        <article class="kpi verde"><span><strong>{{ $indicadores['ocorrencias']['indice'] === null ? '—' : number_format($indicadores['ocorrencias']['indice'], 1, ',', '.') }}</strong><em>Ocorrências por 100 mil km · {{ $indicadores['ocorrencias']['quantidade'] }} no ano</em></span></article>
        @if($fontesGrafico !== null)
            <article class="kpi roxo"><span><strong>{{ $indicadores['custo_aluno'] === null ? '—' : 'R$ ' . number_format($indicadores['custo_aluno'], 2, ',', '.') }}</strong><em>PNATE do mês por aluno que embarcou</em></span></article>
        @endif
    </div>
    <h3>Km do mês</h3>
    <div class="grafico" aria-label="Quilometragem planejada e realizada por mês">
        @foreach($indicadores['serie'] as $numero => $ponto)
            <div class="grafico-linha">
                <span>{{ $mesesGrafico[$numero] }}</span>
                <div class="trilho" title="Planejado {{ number_format($ponto['planejado'], 1, ',', '.') }} km · realizado {{ number_format($ponto['realizado'], 1, ',', '.') }} km">
                    <i class="planejado" style="width: {{ $ponto['faixa_planejado'] }}%"></i>
                    <i class="realizado" style="width: {{ $ponto['faixa_realizado'] }}%"></i>
                </div>
            </div>
        @endforeach
    </div>
    <p class="legenda-grafico"><i class="planejado"></i> Planejado <i class="realizado"></i> Realizado no odômetro</p>
    @if($fontesGrafico !== null)
        <h3>Gasto de {{ $mesesGrafico[$mes] ?? '' }} por fonte</h3>
        <div class="grafico" aria-label="Gasto do mês por fonte de recurso">
            @foreach(['pnate' => 'PNATE', 'proprio' => 'Recurso próprio', 'outros' => 'Outras fontes'] as $chave => $rotulo)
                <div class="grafico-linha">
                    <span>{{ $rotulo }}</span>
                    <div class="trilho" title="R$ {{ number_format($fontesGrafico[$chave], 2, ',', '.') }}">
                        <i class="fonte-{{ $chave }}" style="width: {{ \iEducar\Packages\BuritiTransporteEscolar\Support\IndicadorRede::faixa($fontesGrafico[$chave], $tetoFonte) }}%"></i>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
<section class="painel-grupo" id="consumo-cem" data-painel>
    <div class="barra-dados">
        <h2><i class="fa fa-tint" aria-hidden="true"></i> Litros por 100 km</h2>
        <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar veículo"></label>
    </div>
    <p class="lead">A faixa compara o km por litro real com o consumo cadastrado no veículo.</p>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Veículo</th><th>Litros por 100 km</th><th>Média cadastrada</th><th>Situação</th></tr></thead>
            <tbody>
                @forelse($indicadores['consumo'] as $item)
                    <tr data-busca="{{ $item['placa'] }}">
                        <td>{{ $item['placa'] }}</td>
                        <td>{{ $item['litros'] === null ? '—' : number_format($item['litros'], 1, ',', '.') }}</td>
                        <td>{{ $item['medio'] === null ? '—' : number_format($item['medio'], 1, ',', '.') . ' km/l' }}</td>
                        <td><span class="selo {{ $item['alerta'] ? 'alerta' : 'ok' }}">{{ $item['alerta'] ? 'Acima do cadastro' : 'Dentro do cadastro' }}</span></td>
                    </tr>
                @empty
                    <tr data-vazio><td colspan="4" class="vazio">Ainda não há odômetro e abastecimento no mesmo veículo neste ano.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="painel-grupo" id="cruzamento" data-painel>
    <div class="barra-dados">
        <h2><i class="fa fa-th" aria-hidden="true"></i> Escola, rota, turno e zona</h2>
        <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar escola ou rota"></label>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Escola</th><th>Rota</th><th>Turno</th><th>Zona</th><th>Alunos</th></tr></thead>
            <tbody>
                @forelse($indicadores['cruzamento'] as $linha)
                    <tr data-busca="{{ $linha->escola }} {{ $linha->rota }} {{ $linha->turno }}">
                        <td>{{ $linha->escola }}</td>
                        <td>{{ $linha->rota }}</td>
                        <td>{{ $linha->turno ?: '—' }}</td>
                        <td>{{ (int) $linha->zona === 2 ? 'Rural' : 'Urbana' }}</td>
                        <td>{{ $linha->alunos }}</td>
                    </tr>
                @empty
                    <tr data-vazio><td colspan="5" class="vazio">Nenhum aluno vinculado nas rotas deste ano.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="painel-grupo" id="vagas-rota" data-painel>
    <div class="barra-dados">
        <h2><i class="fa fa-ticket" aria-hidden="true"></i> Vaga publicada e alunos na rota</h2>
        <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar rota"></label>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Rota</th><th>Turno</th><th>Lugares</th><th>Alunos na rota</th></tr></thead>
            <tbody>
                @forelse($indicadores['vagas'] as $vaga)
                    <tr data-busca="{{ $vaga->rota }} {{ $vaga->turno }}">
                        <td>{{ $vaga->rota }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$vaga->turno] ?? $vaga->turno }}</td>
                        <td>{{ $vaga->vagas }}</td>
                        <td>{{ $vaga->alunos }}</td>
                    </tr>
                @empty
                    <tr data-vazio><td colspan="4" class="vazio">Nenhuma vaga publicada nas rotas deste ano.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
