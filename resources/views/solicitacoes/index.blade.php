@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-inbox" aria-hidden="true"></i> Pedidos da família</h1>
    <p class="lead">Primeiro agrupe os processos que a família vê juntos. Depois publique a vaga com idade, série, turma, escola e rota. O protocolo completo abre antes da decisão.</p>
    @if($rede)
        <div class="barra-dados">
            <button type="button" class="botao" data-abre-modal="grupo">Agrupar processos</button>
            <button type="button" class="botao claro" data-abre-modal="processo">Abrir processo</button>
            <button type="button" class="botao claro" data-abre-modal="setor">Setor de atendimento</button>
            @if($processos->isNotEmpty() && $rotas->isNotEmpty())
                <button type="button" class="botao claro" data-abre-modal="oferta">Publicar vaga</button>
            @endif
        </div>
        <dialog class="modal" id="setor">
            <div class="modal-cabeca"><h2>Setor de atendimento</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <p class="lead" style="padding:0 18px;">Estes dados aparecem para a família só quando o pedido é aceito.</p>
        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/atendimento') }}">
            @csrf
            <p><label>Nome do setor <input name="setor" value="{{ $atendimento['setor'] }}"></label></p>
            <p><label>Endereço <input name="endereco" value="{{ $atendimento['endereco'] }}" required></label></p>
            <p><label>Horário <input name="horario" value="{{ $atendimento['horario'] }}"></label></p>
            <p><label>Documentos que a família deve levar <textarea name="documentos" rows="3">{{ $atendimento['documentos'] }}</textarea></label></p>
            <p><label>Latitude <input name="latitude" value="{{ $atendimento['latitude'] }}"></label></p>
            <p><label>Longitude <input name="longitude" value="{{ $atendimento['longitude'] }}"></label></p>
            <button type="submit">Gravar atendimento</button>
        </form>
        </dialog>
        <dialog class="modal" id="grupo">
            <div class="modal-cabeca"><h2>Agrupar processos</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/grupo') }}">
                @csrf
                <p class="lead" style="padding:0 18px;">A família escolhe o agrupamento e, dentro dele, o processo que cabe na idade, na série e na turma.</p>
                <p><label>Nome <input name="nome" required placeholder="Transporte escolar 2026"></label></p>
                <p><label>Ano letivo <input name="ano" type="number" value="{{ date('Y') }}" required></label></p>
                <p><label>Início <input name="inicio" type="date" required></label></p>
                <p><label>Fim <input name="fim" type="date" required></label></p>
                @if($processos->isNotEmpty())
                    <fieldset>
                        <legend>Processos deste ano que entram no agrupamento</legend>
                        @foreach($processos as $processo)
                            <label><input type="checkbox" name="processos[]" value="{{ $processo->id }}"> {{ $processo->ano }} · {{ $processo->nome }}</label>
                        @endforeach
                    </fieldset>
                @endif
                <button type="submit">Abrir agrupamento</button>
            </form>
        </dialog>
        <dialog class="modal" id="processo">
            <div class="modal-cabeca"><h2>Processo de vagas</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/processo') }}">
            @csrf
            <p><label>Nome <input name="nome" required placeholder="Transporte 2026"></label></p>
            <p><label>Ano letivo <input name="ano" type="number" value="{{ date('Y') }}" required></label></p>
            <p><label>Início <input name="inicio" type="date" required></label></p>
            <p><label>Fim <input name="fim" type="date" required></label></p>
            @if($grupos->isNotEmpty())
                <p><label>Agrupamento
                    <select name="grupo_id">
                        <option value="">Sem agrupamento</option>
                        @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $grupo->ano }} · {{ $grupo->nome }}</option>
                        @endforeach
                    </select>
                </label></p>
            @endif
            <button type="submit">Abrir processo</button>
        </form>
        </dialog>
        @if($processos->isNotEmpty() && $rotas->isNotEmpty())
            <dialog class="modal" id="oferta">
            <div class="modal-cabeca"><h2>Publicar vaga</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/oferta') }}">
                @csrf
                <p><label>Processo
                    <select name="processo_id" required>
                        @foreach($processos as $processo)
                            <option value="{{ $processo->id }}" data-ano="{{ $processo->ano }}">{{ $processo->ano }} · {{ $processo->nome }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p><label>Escola e rota
                    <select name="rota_escola" id="rota_escola" required>
                        @foreach($rotas as $rota)
                            <option value="{{ $rota->id }}|{{ $rota->cod_escola }}" data-turno="{{ $rota->periodo }}">{{ $rota->ano }} · {{ $rota->escola }} · {{ $rota->descricao }}</option>
                        @endforeach
                    </select>
                </label></p>
                <input type="hidden" name="rota_id" id="rota_id">
                <input type="hidden" name="ref_cod_escola" id="ref_cod_escola">
                <p><label>Turno
                    <select name="turno" required>
                        @foreach(\iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO as $codigo => $rotulo)
                            <option value="{{ $codigo }}">{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p><label>Vagas <input name="vagas" type="number" min="1" value="1" required></label></p>
                <div class="linha">
                    <label>Idade mínima em 31 de março <input name="idade_min" type="number" min="0" max="30" placeholder="Qualquer"></label>
                    <label>Idade máxima em 31 de março <input name="idade_max" type="number" min="0" max="30" placeholder="Qualquer"></label>
                </div>
                <p><label>Séries que podem pedir
                    <select name="series[]" multiple size="6">
                        @foreach($series as $serie)
                            <option value="{{ $serie->cod_serie }}">{{ $serie->nm_serie }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p class="lead" style="margin:0;">Sem série marcada, qualquer série desta escola pode pedir.</p>
                <p><label>Turmas que podem pedir
                    <select name="turmas[]" multiple size="6">
                        @foreach($turmas as $turma)
                            <option value="{{ $turma->cod_turma }}" data-escola="{{ $turma->ref_ref_cod_escola }}" data-ano="{{ $turma->ano }}">{{ $turma->ano }} · {{ $turma->nm_serie }} · {{ $turma->nm_turma }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p class="lead" style="margin:0;">Sem turma marcada, qualquer turma da escola e do ano pode pedir.</p>
                <button type="submit">Publicar vaga</button>
            </form>
        </dialog>
            <script>
                var formOferta = document.querySelector('form[action$="/oferta"]');
                function filtraTurmas() {
                    var partes = document.getElementById('rota_escola').value.split('|');
                    var escola = partes[1] || '';
                    var processo = formOferta.querySelector('[name="processo_id"]');
                    var ano = processo.options[processo.selectedIndex].getAttribute('data-ano') || '';
                    formOferta.querySelectorAll('[name="turmas[]"] option').forEach(function (opcao) {
                        var cabe = opcao.getAttribute('data-escola') === escola && opcao.getAttribute('data-ano') === ano;
                        opcao.hidden = !cabe;
                        if (!cabe) {
                            opcao.selected = false;
                        }
                    });
                }
                formOferta.addEventListener('change', filtraTurmas);
                formOferta.addEventListener('submit', function () {
                    var partes = document.getElementById('rota_escola').value.split('|');
                    document.getElementById('rota_id').value = partes[0];
                    document.getElementById('ref_cod_escola').value = partes[1];
                });
                filtraTurmas();
            </script>
        @endif
        @if($grupos->isNotEmpty())
            <h2>Agrupamentos da família</h2>
            <div class="tabela-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Ano</th><th>Agrupamento</th><th>Início</th><th>Fim</th><th>Situação</th><th>Processos</th><th></th></tr></thead>
                <tbody>
                @foreach($grupos as $grupo)
                    @php $situacaoGrupo = $grupo->situacao ?? 'aberto'; @endphp
                    <tr>
                        <td>{{ $grupo->ano }}</td>
                        <td>{{ $grupo->nome }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($grupo->inicio)->format('d/m/Y') }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($grupo->fim)->format('d/m/Y') }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::rotulo($situacaoGrupo) }}</td>
                        <td>{{ $processos->where('grupo_id', $grupo->id)->pluck('nome')->implode(', ') ?: '—' }}</td>
                        <td>
                            <button type="button" class="botao claro" data-abre-modal="ver-grupo-{{ $grupo->id }}">Ver</button>
                            <button type="button" class="botao claro" data-abre-modal="editar-grupo-{{ $grupo->id }}">Editar</button>
                            @include('transporte-escolar::solicitacoes.situacao', ['alvo' => 'grupo', 'id' => $grupo->id, 'situacao' => $situacaoGrupo])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @foreach($grupos as $grupo)
                <dialog class="modal" id="editar-grupo-{{ $grupo->id }}">
                    <div class="modal-cabeca"><h2>Editar agrupamento</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                    <form method="post" action="{{ url('/transporte-escolar/solicitacoes/grupo/'.$grupo->id) }}">
                        @csrf
                        <p><label>Nome <input name="nome" required value="{{ $grupo->nome }}"></label></p>
                        <p><label>Ano letivo <input name="ano" type="number" required value="{{ $grupo->ano }}"></label></p>
                        <p><label>Início <input name="inicio" type="date" required value="{{ \Illuminate\Support\Carbon::parse($grupo->inicio)->format('Y-m-d') }}"></label></p>
                        <p><label>Fim <input name="fim" type="date" required value="{{ \Illuminate\Support\Carbon::parse($grupo->fim)->format('Y-m-d') }}"></label></p>
                        <button type="submit">Gravar</button>
                    </form>
                </dialog>
            @endforeach
        @endif
        @if($processos->isNotEmpty())
            <h2>Processos</h2>
            <div class="tabela-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Ano</th><th>Processo</th><th>Agrupamento</th><th>Início</th><th>Fim</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                @foreach($processos as $processo)
                    @php $situacaoProcesso = $processo->situacao ?? 'aberto'; @endphp
                    <tr data-busca="{{ $processo->ano }} {{ $processo->nome }} {{ $processo->grupo }}">
                        <td>{{ $processo->ano }}</td>
                        <td>{{ $processo->nome }}</td>
                        <td>{{ $processo->grupo ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($processo->inicio)->format('d/m/Y') }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($processo->fim)->format('d/m/Y') }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::rotulo($situacaoProcesso) }}</td>
                        <td>
                            <button type="button" class="botao claro" data-abre-modal="editar-processo-{{ $processo->id }}">Editar</button>
                            @include('transporte-escolar::solicitacoes.situacao', ['alvo' => 'processo', 'id' => $processo->id, 'situacao' => $situacaoProcesso])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @foreach($processos as $processo)
            <dialog class="modal" id="editar-processo-{{ $processo->id }}">
                <div class="modal-cabeca"><h2>Editar processo</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/solicitacoes/processo/'.$processo->id) }}">
                    @csrf
                    <p><label>Nome <input name="nome" required value="{{ $processo->nome }}"></label></p>
                    <p><label>Ano letivo <input name="ano" type="number" required value="{{ $processo->ano }}"></label></p>
                    <p><label>Início <input name="inicio" type="date" required value="{{ \Illuminate\Support\Carbon::parse($processo->inicio)->format('Y-m-d') }}"></label></p>
                    <p><label>Fim <input name="fim" type="date" required value="{{ \Illuminate\Support\Carbon::parse($processo->fim)->format('Y-m-d') }}"></label></p>
                    @if($grupos->isNotEmpty())
                        <p><label>Agrupamento
                            <select name="grupo_id">
                                <option value="">Sem agrupamento</option>
                                @foreach($grupos as $grupoOpcao)
                                    <option value="{{ $grupoOpcao->id }}" @selected((int) $processo->grupo_id === (int) $grupoOpcao->id)>{{ $grupoOpcao->ano }} · {{ $grupoOpcao->nome }}</option>
                                @endforeach
                            </select>
                        </label></p>
                    @endif
                    <button type="submit">Gravar</button>
                </form>
            </dialog>
        @endforeach
        @endif
        @if($ofertas->isNotEmpty())
            <h2>Vagas publicadas</h2>
            <div class="tabela-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Processo</th><th>Escola</th><th>Rota</th><th>Turno</th><th>Vagas</th><th>Idade</th><th></th></tr></thead>
                <tbody>
                @foreach($ofertas as $oferta)
                    <tr>
                        <td>{{ $oferta->processo }}</td>
                        <td>{{ $oferta->escola }}</td>
                        <td>{{ $oferta->rota }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$oferta->turno] ?? $oferta->turno }}</td>
                        <td>{{ $oferta->vagas }}</td>
                        <td>{{ $oferta->idade_min === null && $oferta->idade_max === null ? 'Qualquer' : ($oferta->idade_min ?? '…') . ' a ' . ($oferta->idade_max ?? '…') }}</td>
                        <td><button type="button" class="botao claro" data-abre-modal="ver-oferta-{{ $oferta->id }}">Ver</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    @endif
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar protocolo, aluno ou escola"></label>
        </div>
        <div class="tabela-wrap">
    <table>
        <thead>
            <tr><th>Protocolo</th><th>Aluno</th><th>Escola</th><th>Situação</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($pedidos as $pedido)
                <tr data-busca="{{ $pedido->protocolo }} {{ $pedido->nome_aluno }} {{ $pedido->nome_responsavel }} {{ $pedido->escola_informada }} {{ $pedido->rota }} {{ $pedido->situacao }}">
                    <td>{{ $pedido->protocolo }}</td>
                    <td>{{ $pedido->nome_aluno }}</td>
                    <td>{{ $pedido->escola_informada }}</td>
                    <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</td>
                    <td><button type="button" class="botao" data-abre-modal="pedido-{{ $pedido->id }}">Ver protocolo</button></td>
                </tr>
            @empty
                <tr data-vazio><td colspan="5" class="vazio">Nenhum pedido nesta visão.</td></tr>
            @endforelse
        </tbody>
    </table>
        </div>
    </div>
    @foreach($pedidos as $pedido)
        @php
            $telefone = preg_replace('/\D+/', '', (string) $pedido->telefone);
            $telefone = strlen($telefone) === 11
                ? sprintf('(%s) %s-%s', substr($telefone, 0, 2), substr($telefone, 2, 5), substr($telefone, 7))
                : (strlen($telefone) === 10
                    ? sprintf('(%s) %s-%s', substr($telefone, 0, 2), substr($telefone, 2, 4), substr($telefone, 6))
                    : $pedido->telefone);
            $turno = \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$pedido->turno] ?? $pedido->turno;
        @endphp
        <dialog class="modal" id="pedido-{{ $pedido->id }}">
            <div class="modal-cabeca">
                <h2>{{ $pedido->protocolo }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-corpo">
                <dl class="ficha">
                    <div><dt>Situação</dt><dd>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</dd></div>
                    <div><dt>Pedido em</dt><dd>{{ \Illuminate\Support\Carbon::parse($pedido->pedido_em)->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Aluno</dt><dd>{{ $pedido->nome_aluno }}</dd></div>
                    <div><dt>Nascimento</dt><dd>{{ \Illuminate\Support\Carbon::parse($pedido->nascimento)->format('d/m/Y') }}</dd></div>
                    <div><dt>Responsável</dt><dd>{{ $pedido->nome_responsavel }}</dd></div>
                    <div><dt>Telefone</dt><dd>{{ $telefone }}</dd></div>
                    <div class="largo"><dt>Endereço informado</dt><dd>{{ $pedido->endereco }}</dd></div>
                    <div class="largo" data-proximos="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/proximos') }}">
                        <dt>Pontos próximos</dt>
                        <dd>
                            <p class="lead" style="margin:0;">A consulta usa só o endereço. Nome, telefone e nascimento ficam no i-Educar. A distância aparece ao abrir esta ficha.</p>
                            <p data-lista-pontos>Calculando a distância até os pontos cadastrados…</p>
                            <a href="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/mapa') }}" target="_blank" rel="noopener">Abrir mapa detalhado</a>
                        </dd>
                    </div>
                    <div><dt>Escola</dt><dd>{{ $pedido->escola_informada }}</dd></div>
                    <div><dt>Turno</dt><dd>{{ $turno }}</dd></div>
                    <div><dt>Processo</dt><dd>{{ $pedido->processo ?: '—' }}</dd></div>
                    <div><dt>Rota</dt><dd>{{ $pedido->rota ?: '—' }}</dd></div>
                    <div><dt>Série</dt><dd>{{ $pedido->nm_serie ?: '—' }}</dd></div>
                    <div><dt>Turma</dt><dd>{{ $pedido->nm_turma ?: '—' }}</dd></div>
                    @if($pedido->motivo_situacao)
                        <div class="largo"><dt>Observação da última decisão</dt><dd>{{ $pedido->motivo_situacao }}</dd></div>
                    @endif
                </dl>
                @if($pedido->situacao === 'recebida' || ($rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true)))
                    <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                        @csrf
                        <label>Observação da decisão <input name="motivo" maxlength="255" placeholder="Opcional. A família vê este texto ao acompanhar o protocolo."></label>
                        <div class="acao">
                            @if($pedido->situacao === 'recebida')
                                <button name="acao" value="encaminhar">Encaminhar</button>
                            @endif
                            @if($rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                                <button name="acao" value="deferir">Aceitar</button>
                                <button name="acao" value="indeferir">Recusar</button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </dialog>
    @endforeach
    @if($rede)
        @foreach($grupos as $grupo)
            <dialog class="modal" id="ver-grupo-{{ $grupo->id }}">
                <div class="modal-cabeca"><h2>{{ $grupo->nome }}</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <div class="modal-corpo">
                    <dl class="ficha">
                        <div><dt>Ano</dt><dd>{{ $grupo->ano }}</dd></div>
                        <div><dt>Período</dt><dd>{{ \Illuminate\Support\Carbon::parse($grupo->inicio)->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($grupo->fim)->format('d/m/Y') }}</dd></div>
                        <div class="largo"><dt>Processos que a família vê juntos</dt><dd>{{ $processos->where('grupo_id', $grupo->id)->pluck('nome')->implode(', ') ?: 'Nenhum processo ligado ainda.' }}</dd></div>
                    </dl>
                </div>
            </dialog>
        @endforeach
        @foreach($ofertas as $oferta)
            <dialog class="modal" id="ver-oferta-{{ $oferta->id }}">
                <div class="modal-cabeca"><h2>{{ $oferta->escola }} · {{ $oferta->rota }}</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <div class="modal-corpo">
                    <dl class="ficha">
                        <div><dt>Processo</dt><dd>{{ $oferta->ano }} · {{ $oferta->processo }}</dd></div>
                        <div><dt>Turno</dt><dd>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$oferta->turno] ?? $oferta->turno }}</dd></div>
                        <div><dt>Vagas</dt><dd>{{ $oferta->vagas }}</dd></div>
                        <div><dt>Idade em 31 de março</dt><dd>{{ $oferta->idade_min === null && $oferta->idade_max === null ? 'Qualquer' : ($oferta->idade_min ?? 'sem mínimo') . ' a ' . ($oferta->idade_max ?? 'sem máximo') }}</dd></div>
                        <div class="largo"><dt>Séries</dt><dd>{{ $oferta->series_nomes === [] ? 'Qualquer série' : implode(', ', $oferta->series_nomes) }}</dd></div>
                        <div class="largo"><dt>Turmas</dt><dd>{{ $oferta->turmas_nomes === [] ? 'Qualquer turma da escola neste ano' : implode(', ', $oferta->turmas_nomes) }}</dd></div>
                    </dl>
                </div>
            </dialog>
        @endforeach
    @endif
@endsection
