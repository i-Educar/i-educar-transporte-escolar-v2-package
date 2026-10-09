@php $item = $item ?? null; @endphp
<p><label>Tipo
    <select name="tipo" required>
        @foreach($tipos as $codigo => $rotulo)
            <option value="{{ $codigo }}" @selected($item && $item->tipo === $codigo)>{{ $rotulo }}</option>
        @endforeach
    </select>
</label></p>
<div class="linha">
    <label>Nome <input name="nome" value="{{ $item->nome ?? '' }}" required></label>
    <label>Unidade <input name="unidade" value="{{ $item->unidade ?? 'un' }}" required></label>
</div>
<p><label>Estoque mínimo <input name="minimo" type="number" min="0" step="0.001" value="{{ $item->minimo ?? 0 }}" required></label></p>
