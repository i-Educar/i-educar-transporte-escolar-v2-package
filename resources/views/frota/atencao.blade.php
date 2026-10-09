@if($avisos !== [])
    <div class="selos-atencao">
        @foreach($avisos as $aviso)
            <span class="selo {{ $aviso['tom'] }}">{{ $aviso['texto'] }}</span>
        @endforeach
    </div>
@endif
