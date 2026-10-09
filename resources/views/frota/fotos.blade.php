@if(($lista ?? []) !== [])
    <div class="galeria-foto {{ ($carteirinha ?? false) ? 'carteirinha' : '' }}">
        @foreach($lista as $foto)
            <img src="{{ url('/transporte-escolar/fotos/'.$foto->id) }}" alt="{{ ($carteirinha ?? false) ? 'Carteirinha' : 'Foto do veículo' }}">
        @endforeach
    </div>
@endif
