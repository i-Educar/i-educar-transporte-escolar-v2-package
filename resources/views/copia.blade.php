@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Cópia do ano</h1>
    <p>Copia rotas e itinerários pelo código da rota, não pela ordem da lista. O aluno só vai junto se tiver matrícula ativa no ano de destino.</p>
    <form method="post">
        @csrf
        <label>Ano de origem <input name="ano_origem" required></label>
        <label>Ano de destino <input name="ano_destino" required></label>
        <button type="submit">Copiar</button>
    </form>
@endsection
