@if(($paginas ?? 1) > 1 || ($total ?? 0) > 0)
    <nav class="paginas" aria-label="Páginas">
        @if($pagina > 1)
            <a href="{{ $url }}?{{ http_build_query(array_merge($consulta, ['pagina' => $pagina - 1])) }}">Anterior</a>
        @endif
        <span>{{ $pagina }} de {{ $paginas }} · {{ $total }} {{ $total === 1 ? 'registro' : 'registros' }}</span>
        @if($pagina < $paginas)
            <a href="{{ $url }}?{{ http_build_query(array_merge($consulta, ['pagina' => $pagina + 1])) }}">Próxima</a>
        @endif
    </nav>
@endif
