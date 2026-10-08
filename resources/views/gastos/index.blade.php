@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Gastos · {{ $mes }}/{{ $ano }}</h1>
    <p>PNATE: {{ number_format($custo['pnate'], 2, ',', '.') }} · Recurso próprio: {{ number_format($custo['proprio'], 2, ',', '.') }} · Salário rateado no próprio: {{ number_format($custo['salario'], 2, ',', '.') }}</p>
    @if($alerta) <p>{{ $alerta }}</p> @endif
    @if($vencidos->isNotEmpty())
        <p>Licença vencida, conferir antes de pagar:</p>
        <ul>@foreach($vencidos as $veiculo)<li>{{ $veiculo->placa ?: $veiculo->descricao }} · {{ $veiculo->licenciamento_vencimento }}</li>@endforeach</ul>
    @endif
    <form method="post" action="{{ url('/transporte-escolar/gastos/geocerca') }}">
        @csrf
        <label><input type="checkbox" name="geocerca" value="1" @checked($geocerca)> Marcar embarque fora da cerca do ponto</label>
        <input name="raio_metros" value="150" size="4"> metros
        <button type="submit">Gravar cerca</button>
    </form>
    <form method="post" action="{{ url('/transporte-escolar/gastos/aquaviario') }}">
        @csrf
        <label><input type="checkbox" name="aquaviario" value="1" @checked($aquaviario)> O município tem transporte aquaviário</label>
        <button type="submit">Gravar</button>
    </form>
    <h2>Gasto do art. 13</h2>
    <form method="post" enctype="multipart/form-data">
        @csrf
        <input type="date" name="data" required value="{{ date('Y-m-d') }}">
        <select name="categoria">@foreach($categorias as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
        <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
        <input name="valor" placeholder="Valor" required>
        <input name="descricao" placeholder="Descrição">
        <input type="file" name="comprovante">
        <button type="submit">Lançar</button>
    </form>
    <form method="post" action="{{ url('/transporte-escolar/gastos/rateio') }}">
        @csrf
        <label><input type="radio" name="rateio" value="km" @checked($rateio !== 'alunos')> Ratear salário pelo km</label>
        <label><input type="radio" name="rateio" value="alunos" @checked($rateio === 'alunos')> Ratear pelos alunos embarcados</label>
        <button type="submit">Gravar rateio</button>
    </form>
    <h2>Abastecimento</h2>
    <form method="post" action="{{ url('/transporte-escolar/gastos/abastecimento') }}">
        @csrf
        <select name="veiculo_id">@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select>
        <input type="date" name="data" value="{{ date('Y-m-d') }}" required>
        <input name="litros" placeholder="Litros" required>
        <input name="valor" placeholder="Valor" required>
        <input name="km" placeholder="Km">
        <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
        <button type="submit">Lançar</button>
    </form>
    <h2>Plano preventivo</h2>
    <ul>@foreach($planos as $plano)<li>{{ $plano->placa }} · {{ $plano->item }} a cada {{ $plano->a_cada_km }} km ou {{ $plano->a_cada_dias }} dias</li>@endforeach</ul>
    <form method="post" action="{{ url('/transporte-escolar/gastos/plano') }}">
        @csrf
        <select name="veiculo_id">@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select>
        <select name="item"><option value="oleo">Óleo</option><option value="filtro">Filtro</option><option value="pneu">Pneu</option><option value="freio">Freio</option><option value="revisao">Revisão</option></select>
        <input name="a_cada_km" placeholder="A cada km">
        <input name="a_cada_dias" placeholder="A cada dias">
        <button type="submit">Gravar plano</button>
    </form>
    <h2>Ordem de serviço</h2>
    <form method="post" action="{{ url('/transporte-escolar/gastos/ordem') }}">
        @csrf
        <select name="veiculo_id">@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select>
        <select name="fornecedor_id"><option value="">Fornecedor</option>@foreach($fornecedores as $fornecedor)<option value="{{ $fornecedor->id }}">{{ $fornecedor->nome }}</option>@endforeach</select>
        <input name="descricao" placeholder="Serviço" required>
        <input name="peca" placeholder="Peça">
        <input name="valor" placeholder="Valor" required>
        <input type="date" name="data" value="{{ date('Y-m-d') }}" required>
        <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
        <label><input type="checkbox" name="preventiva" value="1"> Preventiva</label>
        <button type="submit">Gravar ordem</button>
    </form>
    <h2>Contrato terceirizado</h2>
    <form method="post" action="{{ url('/transporte-escolar/gastos/contrato') }}">
        @csrf
        <select name="empresa_id">@foreach($empresas as $empresa)<option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>@endforeach</select>
        <input name="descricao" required>
        <input name="valor_mensal" placeholder="Valor mensal" required>
        <input type="date" name="inicio" required>
        <input type="date" name="fim">
        <button type="submit">Gravar contrato</button>
    </form>
    <h2>Parcela informada</h2>
    <form method="post" action="{{ url('/transporte-escolar/gastos/parcela') }}">
        @csrf
        <input name="ano" value="{{ $ano }}">
        <input name="mes" value="{{ $mes }}">
        <select name="fonte">@foreach($fontes as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select>
        <input name="valor" placeholder="Valor da parcela" required>
        <input name="saldo_informado" placeholder="Saldo em 31/12, se for dezembro">
        <button type="submit">Informar</button>
    </form>
@endsection
