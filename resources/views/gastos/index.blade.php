@extends('transporte-escolar::layout')

@section('transporte')
    @php
        $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
        $itens = ['oleo' => 'Óleo', 'filtro' => 'Filtro', 'pneu' => 'Pneu', 'freio' => 'Freio', 'revisao' => 'Revisão'];
    @endphp
    <h1><i class="fa fa-money" aria-hidden="true"></i> Gastos de {{ $meses[$mes] }} de {{ $ano }}</h1>
    <p class="lead">O mês soma o bilhete já abastecido, a ordem concluída e o gasto do art. 13. Pedido de combustível e de serviço ficam em Bilhetes e Ordens, com protocolo.</p>
    <form method="get" class="acao">
        <label>Mês
            <select name="mes">
                @foreach($meses as $numero => $nome)
                    <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nome }}</option>
                @endforeach
            </select>
        </label>
        <label>Ano <input name="ano" value="{{ $ano }}" size="6"></label>
        <button type="submit">Ver mês</button>
    </form>
    <div class="numeros">
        <article><span>PNATE</span><strong>R$ {{ number_format($custo['pnate'], 2, ',', '.') }}</strong></article>
        <article><span>Recurso próprio</span><strong>R$ {{ number_format($custo['proprio'], 2, ',', '.') }}</strong></article>
        <article><span>Outras fontes</span><strong>R$ {{ number_format($custo['outros'], 2, ',', '.') }}</strong></article>
    </div>
    @if($alerta)
        <p class="flash erro">{{ $alerta }}</p>
    @endif
    @if($vencidos->isNotEmpty())
        <section class="card-avisos" style="margin-bottom:16px;">
            <h2><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Licenciamento vencido</h2>
            <ul>
                @foreach($vencidos as $veiculo)
                    <li><i class="fa fa-bus" aria-hidden="true"></i><span>{{ $veiculo->placa ?: $veiculo->descricao }} venceu em {{ \Illuminate\Support\Carbon::parse($veiculo->licenciamento_vencimento)->format('d/m/Y') }}.</span></li>
                @endforeach
            </ul>
        </section>
    @endif
    <div class="barra-dados">
        <button type="button" class="botao" data-abre-modal="lancar-gasto"><i class="fa fa-plus" aria-hidden="true"></i> Lançar gasto</button>
        <button type="button" class="botao claro" data-abre-modal="regras-custo">Regras do custo</button>
        <a class="botao claro" href="{{ url('/transporte-escolar/bilhetes') }}">Bilhetes</a>
        <a class="botao claro" href="{{ url('/transporte-escolar/ordens') }}">Ordens de serviço</a>
    </div>

    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar na composição do mês"></label>
        </div>
        <h2>Composição do mês</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Item</th><th>Fonte</th><th>Valor</th></tr></thead>
                <tbody>
                @forelse($custo['linhas'] as $linha)
                    <tr data-busca="{{ $linha['rotulo'] }} {{ $fontes[$linha['fonte']] ?? $linha['fonte'] }}">
                        <td>{{ $categorias[$linha['rotulo']] ?? $linha['rotulo'] }}</td>
                        <td>{{ $fontes[$linha['fonte']] ?? $linha['fonte'] }}</td>
                        <td>R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr data-vazio><td colspan="3" class="vazio">Nada entrou no custo deste mês.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <h2>Gastos do art. 13</h2>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Data</th><th>Categoria</th><th>Descrição</th><th>Fonte</th><th>Valor</th><th></th></tr></thead>
            <tbody>
            @forelse($gastos as $gasto)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($gasto->data)->format('d/m/Y') }}</td>
                    <td>{{ $categorias[$gasto->categoria] ?? $gasto->categoria }}</td>
                    <td>{{ $gasto->descricao ?: ($gasto->placa ?: '—') }}</td>
                    <td>{{ $fontes[$gasto->fonte] ?? $gasto->fonte }}</td>
                    <td>R$ {{ number_format((float) $gasto->valor, 2, ',', '.') }}</td>
                    <td>@if($gasto->comprovante_id)<a href="{{ url('/transporte-escolar/gastos/comprovante/'.$gasto->comprovante_id) }}">Comprovante</a>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vazio">Nenhum gasto lançado neste mês.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="barra-dados">
        <h2 style="margin:0;">Contratos</h2>
        <button type="button" class="botao claro" data-abre-modal="novo-contrato">Novo contrato</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Empresa</th><th>Descrição</th><th>Valor mensal</th><th>Início</th><th>Fim</th></tr></thead>
            <tbody>
            @forelse($contratos as $contrato)
                <tr>
                    <td>{{ $contrato->empresa }}</td>
                    <td>{{ $contrato->descricao }}</td>
                    <td>R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($contrato->inicio)->format('d/m/Y') }}</td>
                    <td>{{ $contrato->fim ? \Illuminate\Support\Carbon::parse($contrato->fim)->format('d/m/Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="vazio">Nenhum contrato terceirizado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="barra-dados">
        <h2 style="margin:0;">Plano preventivo</h2>
        <button type="button" class="botao claro" data-abre-modal="novo-plano">Incluir item</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Veículo</th><th>Item</th><th>A cada km</th><th>A cada dias</th></tr></thead>
            <tbody>
            @forelse($planos as $plano)
                <tr>
                    <td>{{ $plano->placa ?: $plano->descricao }}</td>
                    <td>{{ $itens[$plano->item] ?? $plano->item }}</td>
                    <td>{{ $plano->a_cada_km ?: '—' }}</td>
                    <td>{{ $plano->a_cada_dias ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="vazio">Nenhum plano preventivo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="barra-dados">
        <h2 style="margin:0;">Parcelas informadas em {{ $ano }}</h2>
        <button type="button" class="botao claro" data-abre-modal="nova-parcela">Informar parcela</button>
    </div>
    <div class="tabela-wrap">
        <table>
            <thead><tr><th>Mês</th><th>Fonte</th><th>Valor</th><th>Saldo em 31/12</th></tr></thead>
            <tbody>
            @forelse($parcelas as $parcela)
                <tr>
                    <td>{{ $meses[(int) $parcela->mes] ?? $parcela->mes }}</td>
                    <td>{{ $fontes[$parcela->fonte] ?? $parcela->fonte }}</td>
                    <td>R$ {{ number_format((float) $parcela->valor, 2, ',', '.') }}</td>
                    <td>{{ $parcela->saldo_informado === null ? '—' : 'R$ ' . number_format((float) $parcela->saldo_informado, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="vazio">Nenhuma parcela informada neste ano. O valor não substitui o extrato do banco.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <dialog class="modal" id="lancar-gasto">
        <div class="modal-cabeca"><h2>Lançar gasto do art. 13</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" enctype="multipart/form-data" action="{{ url('/transporte-escolar/gastos') }}">
            @csrf
            <label>Data <input type="date" name="data" required value="{{ date('Y-m-d') }}"></label>
            <label>Categoria
                <select name="categoria">@foreach($categorias as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label>
            <label>Fonte
                <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label>
            <label>Veículo
                <select name="veiculo_id"><option value="">Sem veículo</option>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select>
            </label>
            <label>Valor <input name="valor" inputmode="decimal" required></label>
            <label>Descrição <input name="descricao"></label>
            @include('buriti-ui::campo-anexo', [
                'campo' => 'comprovante',
                'rotulo' => 'Comprovante (nota ou foto)',
                'tituloAcao' => 'Soltar o comprovante aqui',
                'ajuda' => 'Foto da nota fiscal ou PDF — como no WhatsApp.',
                'accept' => 'image/*,.pdf,application/pdf',
                'icone' => 'fa-camera',
            ])
            <button type="submit">Lançar</button>
        </form>
    </dialog>
    <dialog class="modal" id="regras-custo">
        <div class="modal-cabeca"><h2>Regras do custo</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" action="{{ url('/transporte-escolar/gastos/geocerca') }}">
            @csrf
            <label><input type="checkbox" name="geocerca" value="1" @checked($geocerca)> Marcar embarque fora da cerca do ponto</label>
            <label>Raio em metros <input name="raio_metros" value="{{ $raio }}"></label>
            <button type="submit">Gravar cerca</button>
        </form>
        <form method="post" action="{{ url('/transporte-escolar/gastos/aquaviario') }}">
            @csrf
            <label><input type="checkbox" name="aquaviario" value="1" @checked($aquaviario)> O município tem transporte aquaviário</label>
            <button type="submit">Gravar</button>
        </form>
        <form method="post" action="{{ url('/transporte-escolar/gastos/rateio') }}">
            @csrf
            <label><input type="radio" name="rateio" value="km" @checked($rateio !== 'alunos')> Ratear salário pelo km</label>
            <label><input type="radio" name="rateio" value="alunos" @checked($rateio === 'alunos')> Ratear pelos alunos embarcados</label>
            <button type="submit">Gravar rateio</button>
        </form>
    </dialog>
    <dialog class="modal" id="novo-contrato">
        <div class="modal-cabeca"><h2>Contrato terceirizado</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" action="{{ url('/transporte-escolar/gastos/contrato') }}">
            @csrf
            <label>Empresa
                <select name="empresa_id" required>@foreach($empresas as $empresa)<option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>@endforeach</select>
            </label>
            <label>Descrição <input name="descricao" required></label>
            <label>Valor mensal <input name="valor_mensal" required></label>
            <div class="linha">
                <label>Início <input type="date" name="inicio" required></label>
                <label>Fim <input type="date" name="fim"></label>
            </div>
            <button type="submit">Gravar contrato</button>
        </form>
    </dialog>
    <dialog class="modal" id="novo-plano">
        <div class="modal-cabeca"><h2>Item do plano preventivo</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" action="{{ url('/transporte-escolar/gastos/plano') }}">
            @csrf
            <label>Veículo
                <select name="veiculo_id">@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select>
            </label>
            <label>Item
                <select name="item">@foreach($itens as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label>
            <div class="linha">
                <label>A cada km <input name="a_cada_km"></label>
                <label>A cada dias <input name="a_cada_dias"></label>
            </div>
            <button type="submit">Gravar plano</button>
        </form>
    </dialog>
    <dialog class="modal" id="nova-parcela">
        <div class="modal-cabeca"><h2>Parcela informada</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" action="{{ url('/transporte-escolar/gastos/parcela') }}">
            @csrf
            <div class="linha">
                <label>Ano <input name="ano" value="{{ $ano }}"></label>
                <label>Mês <input name="mes" value="{{ $mes }}"></label>
            </div>
            <label>Fonte
                <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
            </label>
            <label>Valor da parcela <input name="valor" required></label>
            <label>Saldo em 31/12, se for dezembro <input name="saldo_informado"></label>
            <button type="submit">Informar</button>
        </form>
    </dialog>
@endsection
