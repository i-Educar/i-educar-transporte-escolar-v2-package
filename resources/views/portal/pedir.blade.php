@extends('transporte-escolar::portal.layout')

@section('titulo', 'Pedir transporte escolar')

@section('portal')
    <h1>Pedido de transporte</h1>
    <p class="lead">Escolha o agrupamento da secretaria. A lista de escola e rota mostra só o que cabe na idade, na série e na turma, e que ainda tem lugar.</p>
    @if($ofertas->isEmpty())
        <div class="aviso"><strong>Nenhum processo aberto.</strong> A secretaria ainda não publicou ano, escola e rota com vaga. Volte quando o período de pedidos começar.</div>
    @else
    @if($erros)
        <div class="erro">
            @foreach($erros as $erro)<p>{{ $erro }}</p>@endforeach
        </div>
    @endif
    <form class="cartao" method="post" action="{{ url('/transporte-escolar/familia/pedir') }}">
        @csrf
        <label for="nome_aluno">Nome do aluno</label>
        <p class="dica">Como está no documento. Não precisa ser o nome de quem está preenchendo.</p>
        <input id="nome_aluno" name="nome_aluno" value="{{ $velho['nome_aluno'] ?? '' }}" required>
        <label for="nascimento">Data de nascimento</label>
        <p class="dica">Serve para consultar o protocolo depois. Guarde a mesma data que informar aqui.</p>
        <input id="nascimento" name="nascimento" type="date" value="{{ $velho['nascimento'] ?? '' }}" required>
        <label for="nome_responsavel">Quem pede</label>
        <p class="dica">Mãe, pai ou outro responsável que a secretaria pode procurar.</p>
        <input id="nome_responsavel" name="nome_responsavel" value="{{ $velho['nome_responsavel'] ?? '' }}" required>
        <label for="telefone">Telefone com DDD</label>
        <p class="dica">Telefone com DDD, para a secretaria ligar se precisar confirmar o endereço de saída.</p>
        <input id="telefone" name="telefone" inputmode="tel" value="{{ $velho['telefone'] ?? '' }}" required>
        <label for="endereco">Endereço de onde o aluno sai</label>
        <p class="dica">Rua, número e bairro da casa ou do ponto em que o aluno espera o ônibus.</p>
        <input id="endereco" name="endereco" value="{{ $velho['endereco'] ?? '' }}" required>
        @php
            $gruposFamilia = $ofertas->filter(static fn ($oferta) => $oferta->grupo_id !== null)->unique('grupo_id');
        @endphp
        @if($gruposFamilia->isNotEmpty())
            <label for="grupo_id">Agrupamento</label>
            <p class="dica">A secretaria junta vários processos neste nome. Escolha o agrupamento do ano em que o aluno vai estudar.</p>
            <select id="grupo_id">
                <option value="">Todos os processos abertos</option>
                @foreach($gruposFamilia as $grupo)
                    <option value="{{ $grupo->grupo_id }}">{{ $grupo->ano }} · {{ $grupo->grupo }}</option>
                @endforeach
            </select>
        @endif
        @if($series->isNotEmpty())
            <label for="serie_id">Série</label>
            <p class="dica">Só aparecem as séries que a secretaria liberou em alguma vaga.</p>
            <select id="serie_id" name="serie_id">
                <option value="">Escolha a série</option>
                @foreach($series as $serie)
                    <option value="{{ $serie->cod_serie }}" @selected((string) ($velho['serie_id'] ?? '') === (string) $serie->cod_serie)>{{ $serie->nm_serie }}</option>
                @endforeach
            </select>
        @endif
        @if($turmas->isNotEmpty())
            <label for="turma_id">Turma</label>
            <p class="dica">A turma só entra se a vaga escolhida a exigir.</p>
            <select id="turma_id" name="turma_id">
                <option value="">Escolha a turma</option>
                @foreach($turmas as $turma)
                    <option value="{{ $turma->cod_turma }}" data-escola="{{ $turma->ref_ref_cod_escola }}" @selected((string) ($velho['turma_id'] ?? '') === (string) $turma->cod_turma)>{{ $turma->nm_turma }}</option>
                @endforeach
            </select>
        @endif
        <label for="oferta_id">Processo, escola e rota</label>
        <p class="dica">A idade é contada em 31 de março do ano letivo. A rota da manhã não atende o pedido da tarde.</p>
        <select id="oferta_id" name="oferta_id" required>
            <option value="">Escolha o processo, a escola e a rota</option>
            @foreach($ofertas as $oferta)
                <option value="{{ $oferta->id }}"
                    data-grupo="{{ $oferta->grupo_id }}"
                    data-ano="{{ $oferta->ano }}"
                    data-min="{{ $oferta->idade_min }}"
                    data-max="{{ $oferta->idade_max }}"
                    data-series="{{ implode(',', \iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta::lista($oferta->series)) }}"
                    data-turmas="{{ implode(',', \iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta::lista($oferta->turmas)) }}"
                    data-escola="{{ $oferta->ref_cod_escola }}"
                    @selected((string) ($velho['oferta_id'] ?? '') === (string) $oferta->id)>
                    {{ $oferta->grupo ? $oferta->grupo . ' · ' : '' }}{{ $oferta->processo }} · {{ $oferta->escola }} · {{ $oferta->rota }} · {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$oferta->turno] ?? $oferta->turno }} · {{ $oferta->livres }} {{ $oferta->livres === 1 ? 'vaga' : 'vagas' }}
                </option>
            @endforeach
        </select>
        <script>
            (function () {
                var nascimento = document.getElementById('nascimento');
                var grupo = document.getElementById('grupo_id');
                var serie = document.getElementById('serie_id');
                var turma = document.getElementById('turma_id');
                var oferta = document.getElementById('oferta_id');
                function idade(data, ano) {
                    if (!data || !ano) return null;
                    var corte = new Date(ano, 2, 31);
                    var nasc = new Date(data + 'T00:00:00');
                    if (Number.isNaN(nasc.getTime()) || nasc > corte) return null;
                    var anos = corte.getFullYear() - nasc.getFullYear();
                    var aniversario = new Date(corte.getFullYear(), nasc.getMonth(), nasc.getDate());
                    if (aniversario > corte) anos -= 1;
                    return anos;
                }
                function cabeLista(valor, lista) {
                    if (!lista) return true;
                    return lista.split(',').indexOf(valor) !== -1;
                }
                function filtra() {
                    Array.prototype.forEach.call(oferta.options, function (opcao, indice) {
                        if (indice === 0) return;
                        var ano = parseInt(opcao.getAttribute('data-ano'), 10);
                        var idadeAluno = idade(nascimento.value, ano);
                        var minima = opcao.getAttribute('data-min');
                        var maxima = opcao.getAttribute('data-max');
                        var grupoOk = !grupo || !grupo.value || opcao.getAttribute('data-grupo') === grupo.value;
                        var idadeOk = (minima === '' || idadeAluno !== null && idadeAluno >= parseInt(minima, 10))
                            && (maxima === '' || idadeAluno !== null && idadeAluno <= parseInt(maxima, 10));
                        if (minima === '' && maxima === '') idadeOk = true;
                        var serieOk = cabeLista(serie ? serie.value : '', opcao.getAttribute('data-series'));
                        var turmaOk = cabeLista(turma ? turma.value : '', opcao.getAttribute('data-turmas'));
                        opcao.hidden = !(grupoOk && idadeOk && serieOk && turmaOk);
                        if (opcao.hidden && opcao.selected) opcao.selected = false;
                    });
                    if (turma) {
                        var escolhida = oferta.options[oferta.selectedIndex];
                        var escola = escolhida ? escolhida.getAttribute('data-escola') : '';
                        Array.prototype.forEach.call(turma.options, function (opcao, indice) {
                            if (indice === 0) return;
                            opcao.hidden = escola !== '' && opcao.getAttribute('data-escola') !== escola;
                        });
                    }
                }
                [nascimento, grupo, serie, turma, oferta].forEach(function (campo) {
                    if (campo) campo.addEventListener('change', filtra);
                });
                filtra();
            })();
        </script>
        <div class="acoes">
            <button type="submit">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                Enviar pedido
            </button>
        </div>
    </form>
    @endif
@endsection
