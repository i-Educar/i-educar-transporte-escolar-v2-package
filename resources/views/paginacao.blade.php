@php
    $pagina = (int) ($pagina ?? 1);
    $paginas = max(1, (int) ($paginas ?? 1));
    $total = (int) ($total ?? 0);
    $consulta = $consulta ?? [];
    $janela = 7;
    $fim = min($paginas, max($pagina + 3, $janela));
    $inicio = max(1, $fim - $janela + 1);
    $fim = min($paginas, $inicio + $janela - 1);
    $link = function (int $numero) use ($url, $consulta) {
        return $url . '?' . http_build_query(array_merge($consulta, ['pagina' => $numero]));
    };
@endphp
@if($total > 0)
    <nav class="paginas" aria-label="Páginas">
        @if($pagina > 1)
            <a href="{{ $link($pagina - 1) }}">Anterior</a>
        @else
            <span class="mudo">Anterior</span>
        @endif
        @for($numero = $inicio; $numero <= $fim; $numero++)
            @if($numero === $pagina)
                <span class="atual" aria-current="page">{{ $numero }}</span>
            @else
                <a href="{{ $link($numero) }}">{{ $numero }}</a>
            @endif
        @endfor
        @if($pagina < $paginas)
            <a href="{{ $link($pagina + 1) }}">Próxima</a>
        @else
            <span class="mudo">Próxima</span>
        @endif
        <span class="contagem">{{ $pagina }} de {{ $paginas }} · {{ $total }} {{ $total === 1 ? 'registro' : 'registros' }}</span>
    </nav>
@endif
