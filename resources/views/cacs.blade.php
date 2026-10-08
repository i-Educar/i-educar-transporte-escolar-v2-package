@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
        $base = url('/transporte-escolar/cacs/csv') . '?ano=' . $ano . '&mes=' . $mes;
        $hoje = now()->toDateString();
    @endphp
    <h1><i class="fa fa-balance-scale" aria-hidden="true"></i> Painel do CACS · {{ $ano }}</h1>
    <p class="lead">Leitura da rede para o conselho. Não grava, não calcula o Ideges e não envia o parecer.</p>

    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
                <label>Mês do gasto
                    <select name="mes">
                        @foreach($meses as $numero => $nome)
                            <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Atualizar</button>
            </form>
            <a class="botao claro" href="{{ $base }}&planilha=resumo"><i class="fa fa-download" aria-hidden="true"></i> Resumo</a>
            <a class="botao claro" href="{{ $base }}&planilha=rotas"><i class="fa fa-download" aria-hidden="true"></i> Rotas</a>
            <a class="botao claro" href="{{ $base }}&planilha=frota"><i class="fa fa-download" aria-hidden="true"></i> Frota</a>
            <a class="botao claro" href="{{ $base }}&planilha=cnh"><i class="fa fa-download" aria-hidden="true"></i> CNH</a>
            <a class="botao claro" href="{{ $base }}&planilha=custo"><i class="fa fa-download" aria-hidden="true"></i> Gasto</a>
        </div>
        <div class="numeros gestao">
            <article class="ano"><span>Alunos em rota rural</span><strong>{{ $rurais }}</strong></article>
            <article class="rede"><span>Alunos na lista</span><strong>{{ $alunos }}</strong></article>
            <article class="frota"><span>Veículos ativos</span><strong>{{ $frota->count() }}</strong></article>
            <article class="conta"><span>Licenças vencidas</span><strong>{{ $vencidos }}</strong></article>
            <article class="conta"><span>CNH vencidas</span><strong>{{ $cnh }}</strong></article>
            <article class="viagem"><span>Km realizado</span><strong>{{ number_format($kmTotal, 1, ',', '.') }}</strong></article>
        </div>
    </div>

    <div data-painel>
        <div class="barra-dados">
            <h2>Rotas do ano</h2>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar rota"></label>
        </div>
        <p class="lead">O rural realizado no ano soma {{ number_format($kmRural, 1, ',', '.') }} km. A lista não traz nome de aluno.</p>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Rota</th><th>Tipo</th><th>Alunos</th><th>Km planejado</th><th>Km realizado</th></tr></thead>
                <tbody>
                    @forelse($rotas as $rota)
                        <tr data-busca="{{ $rota->descricao }} {{ $rota->rural ? 'Rural' : 'Urbana' }}">
                            <td>{{ $rota->descricao }}</td>
                            <td><span class="selo {{ $rota->rural ? 'prazo' : 'ok' }}">{{ $rota->rural ? 'Rural' : 'Urbana' }}</span></td>
                            <td>{{ $rota->alunos }}</td>
                            <td>{{ number_format((float) $rota->planejado, 1, ',', '.') }}</td>
                            <td>{{ number_format((float) $rota->realizado, 1, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhuma rota neste ano.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div data-painel>
        <div class="barra-dados">
            <h2>Gasto de {{ $meses[$mes] }} por categoria</h2>
        </div>
        @if($alertaSaldo)
            <p class="flash erro">{{ $alertaSaldo }}</p>
        @endif
        <div class="numeros">
            <article class="conta"><span>PNATE</span><strong>R$ {{ number_format($custo['pnate'], 2, ',', '.') }}</strong></article>
            <article class="frota"><span>Recurso próprio</span><strong>R$ {{ number_format($custo['proprio'], 2, ',', '.') }}</strong></article>
            <article class="rede"><span>Outras fontes</span><strong>R$ {{ number_format($custo['outros'], 2, ',', '.') }}</strong></article>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Categoria</th><th>Fonte</th><th>Valor</th></tr></thead>
                <tbody>
                    @forelse($categorias as $linha)
                        <tr>
                            <td>{{ $linha['rotulo'] }}</td>
                            <td>{{ $fontes[$linha['fonte']] ?? $linha['fonte'] }}</td>
                            <td>R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="3" class="vazio">Nada entrou no gasto deste mês.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div data-painel>
        <div class="barra-dados">
            <h2>Frota ativa</h2>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar placa"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Veículo</th><th>Licenciamento</th><th>Situação</th></tr></thead>
                <tbody>
                    @forelse($frota as $veiculo)
                        @php $vence = $veiculo->licenciamento_vencimento ? \Illuminate\Support\Carbon::parse($veiculo->licenciamento_vencimento)->toDateString() : null; @endphp
                        <tr data-busca="{{ $veiculo->placa }} {{ $veiculo->descricao }}">
                            <td>{{ $veiculo->placa ?: $veiculo->descricao }}</td>
                            <td>{{ $vence ? \Illuminate\Support\Carbon::parse($vence)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($vence === null)
                                    <span class="selo">Sem data</span>
                                @elseif($vence < $hoje)
                                    <span class="selo alerta">Vencido</span>
                                @elseif($vence <= now()->addDays(30)->toDateString())
                                    <span class="selo prazo">A vencer</span>
                                @else
                                    <span class="selo ok">Em dia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="3" class="vazio">Nenhum veículo ativo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div data-painel>
        <div class="barra-dados">
            <h2>CNH dos motoristas</h2>
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar nome ou CNH"></label>
        </div>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Nome</th><th>CNH</th><th>Vencimento</th><th>Situação</th></tr></thead>
                <tbody>
                    @forelse($motoristas as $motorista)
                        @php $vence = $motorista->vencimento_cnh ? \Illuminate\Support\Carbon::parse($motorista->vencimento_cnh)->toDateString() : null; @endphp
                        <tr data-busca="{{ $motorista->nome }} {{ $motorista->cnh }}">
                            <td>{{ $motorista->nome }}</td>
                            <td>{{ $motorista->cnh ?: '—' }}</td>
                            <td>{{ $vence ? \Illuminate\Support\Carbon::parse($vence)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($vence === null)
                                    <span class="selo">Sem data</span>
                                @elseif($vence < $hoje)
                                    <span class="selo alerta">Vencida</span>
                                @elseif($vence <= now()->addDays(30)->toDateString())
                                    <span class="selo prazo">A vencer</span>
                                @else
                                    <span class="selo ok">Em dia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="4" class="vazio">Nenhum motorista cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
