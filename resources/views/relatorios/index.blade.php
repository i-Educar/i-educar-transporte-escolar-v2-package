@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Relatórios · {{ $ano }}</h1>
    <p><a href="{{ url('/transporte-escolar/relatorios/csv?ano='.$ano) }}">Baixar planilha</a>
        @if($podeCusto) · <a href="{{ url('/transporte-escolar/relatorios/dossie?ano='.$ano) }}">Dossiê do mês para o SiGPC</a> @endif
    </p>
    <h2>Quilometragem informada</h2>
    <p>Menor {{ $km['minimo'] }} · média {{ $km['media'] }} · maior {{ $km['maximo'] }} · total {{ $km['total'] }}</p>
    <h2>Tempo estimado (minutos)</h2>
    <p>Menor {{ $tempo['minimo'] }} · média {{ $tempo['media'] }} · maior {{ $tempo['maximo'] }} · total {{ $tempo['total'] }}</p>
    <h2>Km realizado nas viagens</h2>
    <p>Menor {{ $realizado['minimo'] }} · média {{ $realizado['media'] }} · maior {{ $realizado['maximo'] }} · total {{ $realizado['total'] }}</p>
    <h2>Custo do mês corrente</h2>
    @if($podeCusto && $custo)
        <p>PNATE {{ number_format($custo['pnate'], 2, ',', '.') }} · próprio {{ number_format($custo['proprio'], 2, ',', '.') }} · outras fontes {{ number_format($custo['outros'], 2, ',', '.') }}</p>
        <p>Por aluno da lista: {{ number_format($custoAluno, 2, ',', '.') }} · por km realizado no ano: {{ number_format($custoKm, 2, ',', '.') }}</p>
    @endif
    <p>Previsto e realizado: {{ $alunos }} alunos na lista · {{ $embarcados }} com embarque registrado.</p>
    <h2>Motoristas</h2>
    <ul>@foreach($motoristas as $motorista)<li>{{ $motorista->nome }} · CNH {{ $motorista->cnh }} · vence {{ $motorista->vencimento_cnh }}</li>@endforeach</ul>
    <h2>Licenças a vencer</h2>
    <ul>@foreach($vencimentos as $veiculo)<li>{{ $veiculo->placa ?: $veiculo->descricao }} · {{ $veiculo->licenciamento_vencimento }}</li>@endforeach</ul>
    <h2>Censo</h2>
    <p>Ficha com transporte e sem rota:</p>
    <ul>@foreach($censoSemRota as $aluno)<li>{{ $aluno->nome }}</li>@endforeach</ul>
    <p>Rota sem o transporte marcado na ficha:</p>
    <ul>@foreach($rotaSemCenso as $aluno)<li>{{ $aluno->nome }}</li>@endforeach</ul>
    <h2>Por rota</h2>
    <ul>
        @foreach($porRota as $rota)
            <li>{{ $rota['descricao'] }} · planejado {{ $rota['planejado'] }} km · realizado {{ $rota['realizado'] }} km · {{ $rota['alunos'] }} na lista · {{ $rota['embarcados'] }} embarcaram</li>
        @endforeach
    </ul>
    <h2>Consumo</h2>
    <ul>
        @foreach($consumo as $item)
            <li>{{ $item['placa'] }} · real {{ $item['real'] === null ? '—' : $item['real'] }} km/l · médio {{ $item['medio'] === null ? '—' : $item['medio'] }}@if($item['alerta']) · acima do cadastro @endif</li>
        @endforeach
    </ul>
    <h2>Frota sem viagem há 30 dias</h2>
    <ul>@foreach($parados as $veiculo)<li>{{ $veiculo->placa ?: $veiculo->descricao }}</li>@endforeach</ul>
    <h2>Rotas</h2>
    <ul>@foreach($rotas as $rota)<li>{{ $rota->descricao }} · {{ $rota->tipo_rota }} · {{ (float) $rota->km_pav + (float) $rota->km_npav }} km</li>@endforeach</ul>
@endsection
