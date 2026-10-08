@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Transporte escolar</h1>
    <h2>Vencimentos em 30 dias</h2>
    <ul>
        @forelse($cnh as $item)
            <li>CNH de {{ $item->nome }} em {{ \Illuminate\Support\Carbon::parse($item->vencimento_cnh)->format('d/m/Y') }}</li>
        @empty
            <li>Nenhuma CNH nesse prazo.</li>
        @endforelse
        @foreach($veiculos as $veiculo)
            <li>Veículo {{ $veiculo->placa ?: $veiculo->descricao }} com documento a vencer.</li>
        @endforeach
    </ul>
    <h2>Lotação acima do veículo</h2>
    <ul>
        @forelse($lotacao as $item)
            <li>{{ $item->descricao }} ({{ $item->ano }}): {{ $item->alunos }} alunos para {{ $item->passageiros }} lugares @if($item->placa) na placa {{ $item->placa }} @endif</li>
        @empty
            <li>Nenhuma rota estourou a lotação.</li>
        @endforelse
    </ul>
@endsection
