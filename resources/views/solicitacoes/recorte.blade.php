@php
    $escolasIds = $escolasIds ?? null;
    $modo = $modo ?? 'qualquer';
    $seriesMarcadas = $seriesMarcadas ?? [];
    $turmasMarcadas = $turmasMarcadas ?? [];
    $lista = $turmas->filter(function ($turma) use ($escolasIds) {
        return $escolasIds === null || in_array((int) $turma->ref_ref_cod_escola, $escolasIds, true);
    })->values();
    $seriesOpcoes = $lista->unique('ref_ref_cod_serie')->sortBy(fn ($serie) => mb_strtolower((string) ($serie->nm_curso . $serie->nm_serie)))->values();
    $grupos = $lista->groupBy(fn ($turma) => $turma->ref_ref_cod_escola . '-' . $turma->ref_ref_cod_serie);
@endphp
<div class="recorte" data-recorte @if(!empty($rotaId)) data-rota="{{ $rotaId }}" @endif @if(!empty($ano)) data-ano="{{ $ano }}" @endif>
    <p class="lead" style="margin:0;">A restrição de série ou turma só vale se a rede precisar. Sem ela, qualquer aluno das escolas desta rota pode pedir, no ano do processo.</p>
    <div class="recorte-modos">
        <label class="opcao"><input type="radio" name="recorte" value="qualquer" @checked($modo === 'qualquer')> Qualquer série e turma</label>
        <label class="opcao"><input type="radio" name="recorte" value="serie" @checked($modo === 'serie')> Só algumas séries</label>
        <label class="opcao"><input type="radio" name="recorte" value="turma" @checked($modo === 'turma')> Só algumas turmas</label>
    </div>
    <div data-painel-recorte="serie" @if($modo !== 'serie') hidden @endif>
        <p class="lead" style="margin:0;">Marque as séries que podem pedir. A família ainda escolhe a turma dentro da série.</p>
        <div class="recorte-lista">
            @foreach($seriesOpcoes as $serie)
                <label class="opcao" data-serie-opcao data-serie="{{ $serie->ref_ref_cod_serie }}">
                    <input type="checkbox" name="series[]" value="{{ $serie->ref_ref_cod_serie }}" @checked(in_array((int) $serie->ref_ref_cod_serie, $seriesMarcadas, true)) @disabled($modo !== 'serie')>
                    <span>{{ $serie->nm_curso ? $serie->nm_curso . ' · ' : '' }}{{ $serie->nm_serie }}</span>
                </label>
            @endforeach
        </div>
        <p class="lead recorte-vazio" data-recorte-vazio hidden>Não há turma ativa neste ano nas escolas desta rota.</p>
    </div>
    <div data-painel-recorte="turma" @if($modo !== 'turma') hidden @endif>
        <p class="lead" style="margin:0;">Só entram turmas do ano do processo, nas escolas atendidas por esta rota.</p>
        <div class="recorte-lista">
            @foreach($grupos as $grupo)
                @php $primeira = $grupo->first(); @endphp
                <div data-grupo>
                    <p class="recorte-grupo">{{ $primeira->escola }} · {{ $primeira->nm_curso ? $primeira->nm_curso . ' · ' : '' }}{{ $primeira->nm_serie }}</p>
                    @foreach($grupo as $turma)
                        <label class="opcao" data-turma data-ano="{{ $turma->ano }}" data-escola="{{ $turma->ref_ref_cod_escola }}" data-serie="{{ $turma->ref_ref_cod_serie }}">
                            <input type="checkbox" name="turmas[]" value="{{ $turma->cod_turma }}" @checked(in_array((int) $turma->cod_turma, $turmasMarcadas, true)) @disabled($modo !== 'turma')>
                            <span>{{ $turma->nm_turma }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
        <p class="lead recorte-vazio" data-recorte-vazio hidden>Não há turma ativa neste ano nas escolas desta rota.</p>
    </div>
</div>
