@php $item = $item ?? null; @endphp
<p><label>Tipo
    <select name="tipo" required onchange="var unidade=this.form.unidade; if(unidade && !unidade.dataset.manual){ unidade.value=this.selectedOptions[0].dataset.unidade; }">
        @foreach($tipos as $codigo => $rotulo)
            <option value="{{ $codigo }}" data-unidade="{{ \iEducar\Packages\BuritiTransporteEscolar\Support\EstoqueSaldo::unidadePadrao($codigo) }}" @selected($item && $item->tipo === $codigo)>{{ $rotulo }}</option>
        @endforeach
    </select>
</label></p>
<div class="linha">
    <label>Nome <input name="nome" value="{{ $item->nome ?? '' }}" required maxlength="120" placeholder="Pneu 275/80 R22.5"></label>
    <label>Unidade <input name="unidade" value="{{ $item->unidade ?? 'un' }}" required maxlength="20" oninput="this.dataset.manual='1'"></label>
</div>
<p><label>Estoque mínimo <input name="minimo" type="number" min="0" step="0.001" value="{{ $item->minimo ?? 0 }}" required></label></p>
