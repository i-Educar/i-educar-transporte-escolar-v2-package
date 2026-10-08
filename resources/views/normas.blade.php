@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Normas de referência</h1>
    <ul>
        @foreach($normas as $norma)
            <li><a href="{{ $norma['url'] }}">{{ $norma['titulo'] }}</a> — {{ $norma['uso'] }}</li>
        @endforeach
    </ul>
@endsection
