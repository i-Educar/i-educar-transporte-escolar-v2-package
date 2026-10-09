@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
        $base = $csvBase . '?ano=' . $ano . '&mes=' . $mes;
        $hoje = now()->toDateString();
        $limite = now()->addDays(30)->toDateString();
        $cobertura = $alunos > 0 ? (int) round($embarcados / $alunos * 100) : 0;
        $cnhVencida = $motoristas->filter(fn ($item) => $item->vencimento_cnh && \Illuminate\Support\Carbon::parse($item->vencimento_cnh)->toDateString() < $hoje)->count();
        $cnhPrazo = $motoristas->filter(function ($item) use ($hoje, $limite) {
            if (!$item->vencimento_cnh) {
                return false;
            }
            $vence = \Illuminate\Support\Carbon::parse($item->vencimento_cnh)->toDateString();

            return $vence >= $hoje && $vence <= $limite;
        })->count();
        $consumoAlerta = collect($consumo)->where('alerta', true)->count();
        $divergencias = $censoSemRota->count() + $rotaSemCenso->count();
        $totalMes = $podeCusto && $custo ? $custo['pnate'] + $custo['proprio'] + $custo['outros'] : 0;
        $rotasRurais = collect($porRota)->where('tipo', 'Rural');
        $alunosRurais = (int) $rotasRurais->sum('alunos');
        $kmRural = round((float) $rotasRurais->sum('realizado'), 1);
        $licencasVencidas = $frota->filter(fn ($veiculo) => $veiculo->licenciamento_vencimento && \Illuminate\Support\Carbon::parse($veiculo->licenciamento_vencimento)->toDateString() < $hoje)->count();
        $opcoes = [
            ['id' => 'indicadores', 'nome' => 'Indicadores', 'frase' => 'Cobertura, preventiva e km do mês', 'icone' => 'fa-bar-chart', 'cor' => '#1f4e66', 'planilha' => 'resumo', 'arquivo' => 'Resumo'],
            ['id' => 'rotas', 'nome' => 'Rotas', 'frase' => 'Lista, embarque e zona rural', 'icone' => 'fa-road', 'cor' => '#188ad1', 'planilha' => 'rotas', 'arquivo' => 'Rotas'],
            ['id' => 'motoristas', 'nome' => 'Motoristas', 'frase' => 'CNH vencida e a vencer', 'icone' => 'fa-id-card', 'cor' => '#b86e00', 'planilha' => 'cnh', 'arquivo' => 'CNH'],
            ['id' => 'frota', 'nome' => 'Frota', 'frase' => 'Licenciamento de cada veículo', 'icone' => 'fa-bus', 'cor' => '#5c4d7a', 'planilha' => 'frota', 'arquivo' => 'Frota'],
            ['id' => 'consumo', 'nome' => 'Consumo', 'frase' => 'Km por litro e litros por 100 km', 'icone' => 'fa-tint', 'cor' => '#0f6b4c', 'planilha' => 'consumo', 'arquivo' => 'Consumo'],
            ['id' => 'censo', 'nome' => 'Censo', 'frase' => 'Ficha e rota que não batem', 'icone' => 'fa-exchange', 'cor' => '#9b2c2c', 'planilha' => 'censo', 'arquivo' => 'Censo'],
            ['id' => 'cruzamento', 'nome' => 'Escola e zona', 'frase' => 'Escola, turno e zona da rota', 'icone' => 'fa-th', 'cor' => '#0e7490', 'planilha' => null, 'arquivo' => null],
            ['id' => 'vagas-rota', 'nome' => 'Vagas', 'frase' => 'Lugares publicados e alunos', 'icone' => 'fa-ticket', 'cor' => '#1f6f8b', 'planilha' => null, 'arquivo' => null],
        ];
        if ($podeCusto) {
            $opcoes[] = ['id' => 'custo', 'nome' => 'Custo do mês', 'frase' => 'PNATE, próprio e outras fontes', 'icone' => 'fa-money', 'cor' => '#5c4d7a', 'planilha' => 'categoria', 'arquivo' => 'Por categoria'];
        }
    @endphp
    <h1><i class="fa fa-bar-chart" aria-hidden="true"></i> Relatórios · {{ $ano }}</h1>
    <p class="lead">Uma leitura só, no recorte deste perfil. O conselho vê a mesma tela, sem gravar e sem calcular o Ideges.</p>

    <section class="painel-grupo">
        <form method="get" class="acao">
            <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
            <label>Mês do custo
                <select name="mes">
                    @foreach($meses as $numero => $nome)
                        <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nome }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Atualizar</button>
        </form>
    </section>

    <div class="opcoes-relatorio">
        @foreach($opcoes as $opcao)
            <article class="opcao-relatorio" style="--cor: {{ $opcao['cor'] }}">
                <span class="marca-opcao"><i class="fa {{ $opcao['icone'] }}" aria-hidden="true"></i></span>
                <strong>{{ $opcao['nome'] }}</strong>
                <p>{{ $opcao['frase'] }}</p>
                <div class="acao">
                    <a class="botao claro" href="#{{ $opcao['id'] }}">Ver</a>
                    @if($opcao['planilha'])
                        <a class="botao claro" href="{{ $base }}&planilha={{ $opcao['planilha'] }}"><i class="fa fa-download" aria-hidden="true"></i> {{ $opcao['arquivo'] }}</a>
                    @endif
                    @if($opcao['id'] === 'custo')
                        <a class="botao claro" href="{{ $base }}&planilha=custo"><i class="fa fa-download" aria-hidden="true"></i> Lançamentos</a>
                        @if($podeDossie)
                            <a class="botao" href="{{ url('/transporte-escolar/relatorios/dossie?ano='.$ano.'&mes='.$mes) }}">Dossiê</a>
                        @endif
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @include('transporte-escolar::relatorios.graficos')

    <section class="painel-grupo">
        <h2><span class="marca-grupo verde"><i class="fa fa-users" aria-hidden="true"></i></span> Atendimento no ano</h2>
        <p class="lead">Quem está na lista da rota e quem já embarcou em alguma viagem.</p>
        <div class="kpis">
            <a class="kpi verde" href="#rotas"><span class="marca-kpi"><i class="fa fa-road" aria-hidden="true"></i></span><span><strong>{{ $rotas->count() }}</strong><em>Rotas</em></span></a>
            <a class="kpi destaque" href="#rotas"><span class="marca-kpi"><i class="fa fa-list-ol" aria-hidden="true"></i></span><span><strong>{{ $alunos }}</strong><em>Alunos na lista</em></span></a>
            <a class="kpi verde" href="#rotas"><span class="marca-kpi"><i class="fa fa-sign-in" aria-hidden="true"></i></span><span><strong>{{ $embarcados }}</strong><em>Com embarque</em></span></a>
            <a class="kpi azul" href="#rotas"><span class="marca-kpi"><i class="fa fa-clock-o" aria-hidden="true"></i></span><span><strong>{{ $viagens }}</strong><em>Viagens · {{ $cobertura }}% embarcaram</em></span></a>
            <a class="kpi ambar" href="#rotas"><span class="marca-kpi"><i class="fa fa-tree" aria-hidden="true"></i></span><span><strong>{{ $alunosRurais }}</strong><em>Em rota rural · {{ number_format($kmRural, 1, ',', '.') }} km</em></span></a>
        </div>
    </section>

    <section class="painel-grupo">
        <h2><span class="marca-grupo destaque"><i class="fa fa-map-o" aria-hidden="true"></i></span> Percurso</h2>
        <p class="lead">O planejado soma o km cadastrado das rotas. O realizado soma o odômetro das viagens.</p>
        <div class="kpis">
            <article class="kpi destaque"><span class="marca-kpi"><i class="fa fa-map-marker" aria-hidden="true"></i></span><span><strong>{{ number_format($km['total'], 1, ',', '.') }} km</strong><em>Planejado</em></span></article>
            <article class="kpi verde"><span class="marca-kpi"><i class="fa fa-tachometer" aria-hidden="true"></i></span><span><strong>{{ number_format($realizado['total'], 1, ',', '.') }} km</strong><em>Realizado</em></span></article>
            <article class="kpi azul"><span class="marca-kpi"><i class="fa fa-hourglass-half" aria-hidden="true"></i></span><span><strong>{{ number_format($tempo['media'], 0, ',', '.') }} min</strong><em>Tempo médio da rota</em></span></article>
            <article class="kpi ambar"><span class="marca-kpi"><i class="fa fa-arrows-h" aria-hidden="true"></i></span><span><strong>{{ number_format($tempo['minimo'], 0, ',', '.') }}–{{ number_format($tempo['maximo'], 0, ',', '.') }}</strong><em>Menor e maior tempo, em min</em></span></article>
        </div>
    </section>

    @if($podeCusto && $custo)
        <section class="painel-grupo" id="custo">
            <h2><span class="marca-grupo roxo"><i class="fa fa-money" aria-hidden="true"></i></span> Custo de {{ $meses[$mes] }}</h2>
            <p class="lead">R$ {{ number_format($custoAluno, 2, ',', '.') }} por aluno da lista e R$ {{ number_format($custoKm, 2, ',', '.') }} por km realizado no ano. O dossiê não envia a prestação.</p>
            @if($alertaSaldo)
                <p class="flash erro">{{ $alertaSaldo }}</p>
            @endif
            <div class="kpis">
                <article class="kpi roxo"><span class="marca-kpi"><i class="fa fa-university" aria-hidden="true"></i></span><span><strong>R$ {{ number_format($custo['pnate'], 2, ',', '.') }}</strong><em>PNATE</em></span></article>
                <article class="kpi ambar"><span class="marca-kpi"><i class="fa fa-bank" aria-hidden="true"></i></span><span><strong>R$ {{ number_format($custo['proprio'], 2, ',', '.') }}</strong><em>Recurso próprio</em></span></article>
                <article class="kpi azul"><span class="marca-kpi"><i class="fa fa-briefcase" aria-hidden="true"></i></span><span><strong>R$ {{ number_format($custo['outros'], 2, ',', '.') }}</strong><em>Outras fontes</em></span></article>
                <article class="kpi destaque"><span class="marca-kpi"><i class="fa fa-calculator" aria-hidden="true"></i></span><span><strong>R$ {{ number_format($totalMes, 2, ',', '.') }}</strong><em>Total do mês</em></span></article>
            </div>
        </section>
    @endif

    <section class="painel-grupo">
        <h2><span class="marca-grupo alerta"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span> Pontos de atenção</h2>
        <p class="lead">CNH, licenciamento, consumo, veículo parado e divergência entre a ficha e a rota.</p>
        <div class="kpis">
            <a class="kpi {{ $cnhVencida > 0 ? 'alerta' : 'verde' }}" href="#motoristas"><span class="marca-kpi"><i class="fa fa-id-card" aria-hidden="true"></i></span><span><strong>{{ $cnhVencida }}</strong><em>CNH vencida · {{ $cnhPrazo }} a vencer</em></span></a>
            <a class="kpi {{ $vencimentos->isNotEmpty() ? 'ambar' : 'verde' }}" href="#licenciamento"><span class="marca-kpi"><i class="fa fa-calendar" aria-hidden="true"></i></span><span><strong>{{ $vencimentos->count() }}</strong><em>Licenças em 30 dias</em></span></a>
            <a class="kpi {{ $consumoAlerta > 0 ? 'alerta' : 'verde' }}" href="#consumo"><span class="marca-kpi"><i class="fa fa-tint" aria-hidden="true"></i></span><span><strong>{{ $consumoAlerta }}</strong><em>Consumo abaixo do cadastro</em></span></a>
            <a class="kpi {{ $parados->isNotEmpty() || $divergencias > 0 ? 'ambar' : 'verde' }}" href="#parados"><span class="marca-kpi"><i class="fa fa-pause" aria-hidden="true"></i></span><span><strong>{{ $parados->count() }}</strong><em>Parados · {{ $divergencias }} no censo</em></span></a>
        </div>
    </section>

    <section class="painel-grupo" id="rotas" data-painel>
        <div class="barra-dados">
            <h2><i class="fa fa-road" aria-hidden="true"></i> Rotas</h2>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar rota"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Rota</th><th>Tipo</th><th>Km planejado</th><th>Km realizado</th><th>Na lista</th><th>Embarcaram</th></tr></thead>
                <tbody>
                    @forelse($porRota as $rota)
                        <tr data-busca="{{ $rota['descricao'] }} {{ $rota['tipo'] }}">
                            <td>{{ $rota['descricao'] }}</td>
                            <td><span class="selo">{{ $rota['tipo'] }}</span></td>
                            <td>{{ number_format($rota['planejado'], 1, ',', '.') }}</td>
                            <td>{{ number_format($rota['realizado'], 1, ',', '.') }}</td>
                            <td>{{ $rota['alunos'] }}</td>
                            <td>{{ $rota['embarcados'] }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhuma rota neste ano.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($podeCusto && $custo)
        <section class="painel-grupo" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-balance-scale" aria-hidden="true"></i> Categorias de {{ $meses[$mes] }}</h2>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Categoria</th><th>Fonte</th><th>Valor</th></tr></thead>
                    <tbody>
                        @forelse($categorias as $linha)
                            <tr>
                                <td>{{ $linha['rotulo'] }}</td>
                                <td><span class="selo">{{ $fontes[$linha['fonte']] ?? $linha['fonte'] }}</span></td>
                                <td>R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="3" class="vazio">Nada entrou no custo deste mês.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="painel-grupo" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-list" aria-hidden="true"></i> Lançamentos de {{ $meses[$mes] }}</h2>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Lançamento</th><th>Fonte</th><th>Valor</th></tr></thead>
                    <tbody>
                        @forelse($custo['linhas'] as $linha)
                            <tr>
                                <td>{{ $linha['rotulo'] }}</td>
                                <td><span class="selo">{{ $fontes[$linha['fonte']] ?? $linha['fonte'] }}</span></td>
                                <td>R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="3" class="vazio">Nada entrou no custo deste mês.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="grade-relatorio">
        <section class="painel-grupo" id="motoristas" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-id-card" aria-hidden="true"></i> Motoristas</h2>
                <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar nome ou CNH"></label>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Nome</th><th>CNH</th><th>Vencimento</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse($motoristas as $motorista)
                            @php
                                $vence = $motorista->vencimento_cnh ? \Illuminate\Support\Carbon::parse($motorista->vencimento_cnh)->toDateString() : null;
                                $selo = $vence === null ? 'Sem data' : ($vence < $hoje ? 'Vencida' : ($vence <= $limite ? 'A vencer' : 'Em dia'));
                            @endphp
                            <tr data-busca="{{ $motorista->nome }} {{ $motorista->cnh }}">
                                <td>{{ $motorista->nome }}</td>
                                <td>{{ $motorista->cnh ?: '—' }}</td>
                                <td>{{ $vence ? \Illuminate\Support\Carbon::parse($vence)->format('d/m/Y') : '—' }}</td>
                                <td><span class="selo {{ $selo === 'Em dia' ? 'ok' : ($selo === 'Vencida' ? 'alerta' : 'prazo') }}">{{ $selo }}</span></td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="4" class="vazio">Nenhum motorista neste recorte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="painel-grupo" id="frota" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-bus" aria-hidden="true"></i> Frota ativa</h2>
                <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar placa"></label>
            </div>
            <p class="lead">{{ $licencasVencidas }} com licenciamento vencido. A lista de 30 dias continua ao lado.</p>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Veículo</th><th>Licenciamento</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse($frota as $veiculo)
                            @php $vence = $veiculo->licenciamento_vencimento ? \Illuminate\Support\Carbon::parse($veiculo->licenciamento_vencimento)->toDateString() : null; @endphp
                            <tr data-busca="{{ $veiculo->placa }} {{ $veiculo->descricao }}">
                                <td>{{ $veiculo->placa ?: $veiculo->descricao }}</td>
                                <td>{{ $vence ? \Illuminate\Support\Carbon::parse($vence)->format('d/m/Y') : '—' }}</td>
                                <td><span class="selo {{ $vence === null ? '' : ($vence < $hoje ? 'alerta' : ($vence <= $limite ? 'prazo' : 'ok')) }}">{{ $vence === null ? 'Sem data' : ($vence < $hoje ? 'Vencido' : ($vence <= $limite ? 'A vencer' : 'Em dia')) }}</span></td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="3" class="vazio">Nenhum veículo ativo neste recorte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="painel-grupo" id="licenciamento" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-calendar" aria-hidden="true"></i> Licenciamento em 30 dias</h2>
                <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar placa"></label>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Veículo</th><th>Vencimento</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse($vencimentos as $veiculo)
                            @php $vence = \Illuminate\Support\Carbon::parse($veiculo->licenciamento_vencimento)->toDateString(); @endphp
                            <tr data-busca="{{ $veiculo->placa }} {{ $veiculo->descricao }}">
                                <td>{{ $veiculo->placa ?: $veiculo->descricao }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($vence)->format('d/m/Y') }}</td>
                                <td><span class="selo {{ $vence < $hoje ? 'alerta' : 'prazo' }}">{{ $vence < $hoje ? 'Vencido' : 'A vencer' }}</span></td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="3" class="vazio">Nenhum licenciamento vence nos próximos 30 dias.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="painel-grupo" id="consumo" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-tint" aria-hidden="true"></i> Consumo no ano</h2>
                <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar veículo"></label>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Veículo</th><th>Real</th><th>Médio cadastrado</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse($consumo as $item)
                            <tr data-busca="{{ $item['placa'] }}">
                                <td>{{ $item['placa'] }}</td>
                                <td>{{ $item['real'] === null ? '—' : number_format($item['real'], 1, ',', '.') . ' km/l' }}</td>
                                <td>{{ $item['medio'] === null ? '—' : number_format($item['medio'], 1, ',', '.') . ' km/l' }}</td>
                                <td><span class="selo {{ $item['alerta'] ? 'alerta' : 'ok' }}">{{ $item['alerta'] ? 'Abaixo do cadastro' : 'Dentro do cadastro' }}</span></td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="4" class="vazio">Nenhum abastecimento confirmado neste ano.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="painel-grupo" id="parados" data-painel>
            <div class="barra-dados">
                <h2><i class="fa fa-pause" aria-hidden="true"></i> Sem viagem há 30 dias</h2>
                <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar veículo"></label>
            </div>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Placa</th><th>Descrição</th></tr></thead>
                    <tbody>
                        @forelse($parados as $veiculo)
                            <tr data-busca="{{ $veiculo->placa }} {{ $veiculo->descricao }}">
                                <td>{{ $veiculo->placa ?: '—' }}</td>
                                <td>{{ $veiculo->descricao ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr data-vazio><td colspan="2" class="vazio">Nenhum veículo ativo ficou parado neste recorte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="painel-grupo" id="censo" data-painel>
        <div class="barra-dados">
            <h2><i class="fa fa-exchange" aria-hidden="true"></i> Censo e rota</h2>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar aluno"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Aluno</th><th>Divergência</th></tr></thead>
                <tbody>
                    @foreach($censoSemRota as $aluno)
                        <tr data-busca="{{ $aluno->nome }}"><td>{{ $aluno->nome }}</td><td><span class="selo alerta">Ficha com transporte e sem rota</span></td></tr>
                    @endforeach
                    @foreach($rotaSemCenso as $aluno)
                        <tr data-busca="{{ $aluno->nome }}"><td>{{ $aluno->nome }}</td><td><span class="selo prazo">Na rota sem transporte na ficha</span></td></tr>
                    @endforeach
                    @if($censoSemRota->isEmpty() && $rotaSemCenso->isEmpty())
                        <tr data-vazio><td colspan="2" class="vazio">Nenhuma divergência entre a ficha e a rota.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
@endsection
