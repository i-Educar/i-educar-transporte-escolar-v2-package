@extends('transporte-escolar::layout')

@section('transporte')
    <h1>SETE</h1>
    <p>A planilha entra de novo sem duplicar placa, CPF ou rota do mesmo ano. Pessoa que não existe no i-Educar vira divergência, não um cadastro novo.</p>
    @if($podeImportar)
        <form method="post" enctype="multipart/form-data">
            @csrf
            <label>Ano <input name="ano" value="{{ date('Y') }}" required></label>
            <input type="file" name="arquivo" required accept=".csv,.txt,.xlsx,.xls">
            <button type="submit">Importar</button>
        </form>
        <p>
            <a href="{{ url('/transporte-escolar/sete/exportar/alunos') }}">Exportar alunos</a> ·
            <a href="{{ url('/transporte-escolar/sete/exportar/veiculos') }}">veículos</a> ·
            <a href="{{ url('/transporte-escolar/sete/exportar/motoristas') }}">motoristas</a> ·
            <a href="{{ url('/transporte-escolar/sete/exportar/monitores') }}">monitores</a>
        </p>
    @endif
    <h2>Divergências e cargas</h2>
    <ul>
        @foreach($linhas as $linha)
            <li>{{ $linha->situacao }} · {{ $linha->entidade }} · linha {{ $linha->linha }} · {{ $linha->mensagem }}</li>
        @endforeach
    </ul>
@endsection
