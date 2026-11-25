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
                <p><label>Nome <input name="nome" required placeholder="Transporte 2026"></label></p>
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
                @include('transporte-escolar::solicitacoes.recorte', [
                    'turmas' => $turmas,
                    'escolasIds' => null,
                    'modo' => 'qualquer',
                    'seriesMarcadas' => [],
                    'turmasMarcadas' => [],
                ])
                <button type="submit">Publicar vaga</button>
            </form>
        </dialog>
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
                        <td class="acoes">
                            <button type="button" class="icone ver" data-abre-modal="ver-grupo-{{ $grupo->id }}" aria-label="Ver agrupamento" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>
                            <button type="button" class="icone editar" data-abre-modal="editar-grupo-{{ $grupo->id }}" aria-label="Editar agrupamento" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                            @include('transporte-escolar::solicitacoes.situacao', ['alvo' => 'grupo', 'id' => $grupo->id, 'situacao' => $situacaoGrupo])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @foreach($grupos as $grupo)
                @php $pedidosGrupo = (int) $processos->where('grupo_id', $grupo->id)->sum(fn ($processo) => (int) ($usoProcesso[$processo->id]->pedidos ?? 0)); @endphp
                <dialog class="modal" id="editar-grupo-{{ $grupo->id }}">
                    <div class="modal-cabeca"><h2><i class="fa fa-pencil" aria-hidden="true"></i> Editar agrupamento</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                    <form method="post" action="{{ url('/transporte-escolar/solicitacoes/grupo/'.$grupo->id) }}">
                        @csrf
                        <p><label>Nome <input name="nome" required value="{{ $grupo->nome }}"></label></p>
                        <p><label>Ano letivo <input name="ano" type="number" required value="{{ $grupo->ano }}" @if($pedidosGrupo > 0) readonly @endif></label></p>
                        @if($pedidosGrupo > 0)
                            <p class="lead">O ano fica fixo porque já existe pedido da família em um processo deste agrupamento.</p>
                        @endif
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
                <thead><tr><th>Ano</th><th>Processo</th><th>Agrupamento</th><th>Início</th><th>Fim</th><th>Vagas</th><th>Ocupadas</th><th>Disponíveis</th><th>Espera</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                @foreach($processos as $processo)
                    @php
                        $situacaoProcesso = $processo->situacao ?? 'aberto';
                        $ofertasProcesso = $ofertas->where('processo_id', $processo->id);
                        $vagasProcesso = (int) $ofertasProcesso->sum('vagas');
                        $ocupadasProcesso = (int) $ofertasProcesso->sum(fn ($oferta) => (int) (optional($usoOferta->get($oferta->id))->ocupadas ?? 0));
                        $esperaProcesso = (int) $ofertasProcesso->sum(fn ($oferta) => (int) (optional($usoOferta->get($oferta->id))->espera ?? 0));
                    @endphp
                    <tr data-busca="{{ $processo->ano }} {{ $processo->nome }} {{ $processo->grupo }}">
                        <td>{{ $processo->ano }}</td>
                        <td>{{ $processo->nome }}</td>
                        <td>{{ $processo->grupo ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($processo->inicio)->format('d/m/Y') }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($processo->fim)->format('d/m/Y') }}</td>
                        <td>{{ $vagasProcesso }}</td>
                        <td>{{ $ocupadasProcesso }}</td>
                        <td>{{ max(0, $vagasProcesso - $ocupadasProcesso) }}</td>
                        <td>{{ $esperaProcesso > 0 ? $esperaProcesso : '—' }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::rotulo($situacaoProcesso) }}</td>
                        <td class="acoes">
                            <button type="button" class="icone editar" data-abre-modal="editar-processo-{{ $processo->id }}" aria-label="Editar processo" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                            @include('transporte-escolar::solicitacoes.situacao', ['alvo' => 'processo', 'id' => $processo->id, 'situacao' => $situacaoProcesso])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @foreach($processos as $processo)
            <dialog class="modal" id="editar-processo-{{ $processo->id }}">
                <div class="modal-cabeca"><h2><i class="fa fa-pencil" aria-hidden="true"></i> Editar processo</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/solicitacoes/processo/'.$processo->id) }}">
                    @csrf
                    <p><label>Nome <input name="nome" required value="{{ $processo->nome }}"></label></p>
                    <p><label>Ano letivo <input name="ano" type="number" required value="{{ $processo->ano }}" @if((int) ($usoProcesso[$processo->id]->pedidos ?? 0) > 0) readonly @endif></label></p>
                    @if((int) ($usoProcesso[$processo->id]->pedidos ?? 0) > 0)
                        <p class="lead">O ano fica fixo porque já existe pedido da família neste processo.</p>
                    @endif
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
                    <p><label>Documentos obrigatórios
                        <textarea name="documentos_exigidos" rows="4" placeholder="Um documento por linha. Em branco, a conferência só pede a confirmação.">{{ implode("\n", \iEducar\Packages\BuritiTransporteEscolar\Support\HistoricoPedido::exigidos($processo->documentos_exigidos ?? null)) }}</textarea>
                    </label></p>
                    <button type="submit">Gravar</button>
                </form>
            </dialog>
        @endforeach
        @endif
        @if($ofertas->isNotEmpty())
            <h2>Vagas publicadas</h2>
            <div class="tabela-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Processo</th><th>Escola</th><th>Rota</th><th>Turno</th><th>Vagas</th><th>Ocupadas</th><th>Disponíveis</th><th>Espera</th><th>Idade</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                @foreach($ofertas as $oferta)
                    @php
                        $situacaoOferta = $oferta->situacao ?? 'aberto';
                        $ocupadasOferta = (int) (optional($usoOferta->get($oferta->id))->ocupadas ?? 0);
                        $esperaOferta = (int) (optional($usoOferta->get($oferta->id))->espera ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $oferta->processo }}</td>
                        <td>{{ $oferta->escola }}</td>
                        <td>{{ $oferta->rota }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$oferta->turno] ?? $oferta->turno }}</td>
                        <td>{{ $oferta->vagas }}</td>
                        <td>{{ $ocupadasOferta }}</td>
                        <td>{{ max(0, (int) $oferta->vagas - $ocupadasOferta) }}</td>
                        <td>{{ $esperaOferta > 0 ? $esperaOferta : '—' }}</td>
                        <td>{{ $oferta->idade_min === null && $oferta->idade_max === null ? 'Qualquer' : ($oferta->idade_min ?? '…') . ' a ' . ($oferta->idade_max ?? '…') }}</td>
                        <td>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::rotulo($situacaoOferta) }}</td>
                        <td class="acoes">
                            <button type="button" class="icone ver" data-abre-modal="ver-oferta-{{ $oferta->id }}" aria-label="Ver vaga" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>
                            <button type="button" class="icone editar" data-abre-modal="editar-oferta-{{ $oferta->id }}" aria-label="Editar vaga" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                            @if($situacaoOferta !== 'fechado')
                                <button type="button" class="icone cancelar" data-abre-modal="fechar-oferta-{{ $oferta->id }}" aria-label="Fechar vagas" title="Fechar vagas"><i class="fa fa-lock" aria-hidden="true"></i></button>
                            @endif
                            @include('transporte-escolar::solicitacoes.situacao', ['alvo' => 'oferta', 'id' => $oferta->id, 'situacao' => $situacaoOferta])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @foreach($ofertas as $oferta)
                @php
                    $uso = $usoOferta[$oferta->id] ?? null;
                    $pedidosOferta = (int) ($uso->pedidos ?? 0);
                    $deferidosOferta = (int) ($uso->deferidos ?? 0);
                    $seriesMarcadas = \iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta::lista($oferta->series);
                    $turmasMarcadas = \iEducar\Packages\BuritiTransporteEscolar\Support\RegraOferta::lista($oferta->turmas);
                @endphp
                <dialog class="modal" id="editar-oferta-{{ $oferta->id }}">
                    <div class="modal-cabeca"><h2><i class="fa fa-pencil" aria-hidden="true"></i> Editar vaga</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                    <form method="post" action="{{ url('/transporte-escolar/solicitacoes/oferta/'.$oferta->id) }}">
                        @csrf
                        @if($pedidosOferta > 0)
                            <p class="lead">Já existe pedido nesta vaga. Escola, rota e turno permanecem.</p>
                        @else
                            <p><label>Processo
                                <select name="processo_id">
                                    @foreach($processos as $processo)
                                        <option value="{{ $processo->id }}" data-ano="{{ $processo->ano }}" @selected((int) $oferta->processo_id === (int) $processo->id)>{{ $processo->ano }} · {{ $processo->nome }}</option>
                                    @endforeach
                                </select>
                            </label></p>
                            <input type="hidden" name="rota_id" value="{{ $oferta->rota_id }}">
                            <input type="hidden" name="ref_cod_escola" value="{{ $oferta->ref_cod_escola }}">
                            <p><label>Turno
                                <select name="turno">
                                    @foreach(\iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO as $codigo => $rotulo)
                                        <option value="{{ $codigo }}" @selected($oferta->turno === $codigo)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </label></p>
                        @endif
                        <p><label>Vagas <input name="vagas" type="number" min="1" value="{{ $oferta->vagas }}" required></label></p>
                        @if($deferidosOferta > 0)
                            <p class="lead">Há pedido aceito. Idade, série e turma permanecem.</p>
                            <input type="hidden" name="idade_min" value="{{ $oferta->idade_min }}">
                            <input type="hidden" name="idade_max" value="{{ $oferta->idade_max }}">
                            @foreach($seriesMarcadas as $serieId)<input type="hidden" name="series[]" value="{{ $serieId }}">@endforeach
                            @foreach($turmasMarcadas as $turmaId)<input type="hidden" name="turmas[]" value="{{ $turmaId }}">@endforeach
                        @else
                            <div class="linha">
                                <label>Idade mínima <input name="idade_min" type="number" min="0" max="30" value="{{ $oferta->idade_min }}"></label>
                                <label>Idade máxima <input name="idade_max" type="number" min="0" max="30" value="{{ $oferta->idade_max }}"></label>
                            </div>
                            @if($turmasMarcadas !== [] && $seriesMarcadas !== [])
                                <p class="lead">Esta vaga restringia série e turma juntas. A turma já define a série, então a escolha fica só nas turmas marcadas.</p>
                            @endif
                            @include('transporte-escolar::solicitacoes.recorte', [
                                'turmas' => $turmas,
                                'escolasIds' => $escolasRota[$oferta->rota_id] ?? [(int) $oferta->ref_cod_escola],
                                'modo' => $turmasMarcadas !== [] ? 'turma' : ($seriesMarcadas !== [] ? 'serie' : 'qualquer'),
                                'seriesMarcadas' => $seriesMarcadas,
                                'turmasMarcadas' => $turmasMarcadas,
                                'rotaId' => $oferta->rota_id,
                                'ano' => $oferta->ano,
                            ])
                        @endif
                        <button type="submit"><i class="fa fa-check" aria-hidden="true"></i> Gravar vaga</button>
                    </form>
                </dialog>
                @if(($oferta->situacao ?? 'aberto') !== 'fechado')
                    <dialog class="modal" id="fechar-oferta-{{ $oferta->id }}">
                        <div class="modal-cabeca"><h2>Fechar vagas · {{ $oferta->rota }}</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                        <form method="post" action="{{ url('/transporte-escolar/solicitacoes/oferta/'.$oferta->id.'/situacao') }}">
                            @csrf
                            <input type="hidden" name="acao" value="fechar">
                            <p class="lead">Os pedidos aprovados desta vaga, ainda sem conferência, vão para a lista de espera. Quem já está na rota permanece.</p>
                            <p>{{ $deferidosOferta === 1 ? '1 pedido aprovado será movido.' : $deferidosOferta . ' pedidos aprovados serão movidos.' }}</p>
                            <button type="submit">Fechar vagas</button>
                        </form>
                    </dialog>
                @endif
            @endforeach
        @endif
        <style>
            .recorte-modos { display: grid; gap: 8px; margin: 8px 0 12px; }
            .recorte-lista { max-height: 240px; overflow: auto; display: grid; gap: 6px; margin-top: 8px; }
            .recorte-grupo { margin: 10px 0 0; font-size: .78rem; font-weight: 700; color: var(--suave); }
            dialog.modal.pedido { width: min(980px, calc(100% - 32px)); }
            .resumo { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px 18px; margin: 0; background: #fff; border: 1px solid var(--linha); border-radius: 8px; padding: 14px 16px; }
            .resumo > div { display: grid; gap: 2px; min-width: 0; }
            .resumo .largo { grid-column: 1 / -1; }
            .resumo dt { margin: 0; font-size: .7rem; color: var(--suave); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
            .resumo dd { margin: 0; font-weight: 600; }
            .resumo .pontos { display: flex; flex-wrap: wrap; gap: 6px 14px; align-items: baseline; }
            .resumo .pontos p { margin: 0; }
            .modal-corpo > form.alocar { display: grid; gap: 12px; min-height: auto; height: auto; overflow: visible; flex: none; padding: 14px 16px; background: #fff; border: 1px solid var(--linha); border-radius: 8px; }
            .modal-corpo > form.alocar h3 { margin: 0; font-size: .95rem; }
            .modal-corpo > form.alocar .acao { display: flex; justify-content: flex-end; margin: 0; }
            .historico { display: grid; gap: 8px; }
            .historico h3 { margin: 0; font-size: .95rem; }
            .avisos { display: grid; gap: 4px; margin: 0; }
            .avisos p { margin: 0; }
            .avisos.ok { color: #17663f; }
            .avisos.alerta { color: #9b2c2c; }
            .linha-tempo { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .linha-tempo li { background: #fff; border: 1px solid var(--linha); border-left: 3px solid #188ad1; border-radius: 8px; padding: 8px 10px; display: grid; gap: 2px; }
            .linha-tempo span { color: var(--suave); font-size: .82rem; }
            @media (max-width: 860px) {
                .resumo, .linha-tempo { grid-template-columns: 1fr; }
            }
        </style>
        <script>
            window.escolasDaRota = @json($escolasRota);
            function anoDoRecorte(bloco) {
                var form = bloco.closest('form');
                var processo = form.querySelector('[name="processo_id"]');
                if (processo && processo.options && processo.selectedIndex >= 0) {
                    var ano = processo.options[processo.selectedIndex].getAttribute('data-ano');
                    if (ano) {
                        return ano;
                    }
                }
                return bloco.getAttribute('data-ano') || '';
            }
            function escolasDoRecorte(bloco) {
                var rota = bloco.getAttribute('data-rota');
                if (!rota) {
                    var campo = bloco.closest('form').querySelector('[name="rota_escola"]');
                    rota = campo ? (campo.value || '').split('|')[0] : '';
                }
                var lista = (window.escolasDaRota && window.escolasDaRota[rota]) || [];
                return lista.map(String);
            }
            function aplicarRecorte(bloco) {
                var marcado = bloco.querySelector('input[name="recorte"]:checked');
                var modo = marcado ? marcado.value : 'qualquer';
                var ano = anoDoRecorte(bloco);
                var escolas = escolasDoRecorte(bloco);
                bloco.querySelectorAll('[data-painel-recorte]').forEach(function (painel) {
                    var ativo = painel.getAttribute('data-painel-recorte') === modo;
                    painel.hidden = !ativo;
                    painel.querySelectorAll('input').forEach(function (campo) { campo.disabled = !ativo; });
                });
                bloco.querySelectorAll('[data-turma]').forEach(function (item) {
                    var cabe = item.getAttribute('data-ano') === ano && escolas.indexOf(item.getAttribute('data-escola')) !== -1;
                    item.hidden = !cabe;
                    if (!cabe) {
                        var campo = item.querySelector('input');
                        if (campo) {
                            campo.checked = false;
                        }
                    }
                });
                bloco.querySelectorAll('[data-grupo]').forEach(function (grupo) {
                    grupo.hidden = !grupo.querySelector('[data-turma]:not([hidden])');
                });
                bloco.querySelectorAll('[data-serie-opcao]').forEach(function (item) {
                    var serie = item.getAttribute('data-serie');
                    var cabe = false;
                    bloco.querySelectorAll('[data-turma]').forEach(function (turma) {
                        if (!cabe && turma.getAttribute('data-serie') === serie && turma.getAttribute('data-ano') === ano && escolas.indexOf(turma.getAttribute('data-escola')) !== -1) {
                            cabe = true;
                        }
                    });
                    item.hidden = !cabe;
                    if (!cabe) {
                        var campo = item.querySelector('input');
                        if (campo) {
                            campo.checked = false;
                        }
                    }
                });
                bloco.querySelectorAll('[data-recorte-vazio]').forEach(function (aviso) {
                    var painel = aviso.closest('[data-painel-recorte]');
                    var tem = painel && painel.querySelector('[data-turma]:not([hidden]), [data-serie-opcao]:not([hidden])');
                    aviso.hidden = !!tem;
                });
            }
            document.querySelectorAll('[data-recorte]').forEach(function (bloco) {
                bloco.closest('form').addEventListener('change', function () { aplicarRecorte(bloco); });
                aplicarRecorte(bloco);
            });
            var formOferta = document.querySelector('form[action$="/oferta"]');
            if (formOferta) {
                formOferta.addEventListener('submit', function () {
                    var partes = (document.getElementById('rota_escola').value || '').split('|');
                    document.getElementById('rota_id').value = partes[0] || '';
                    document.getElementById('ref_cod_escola').value = partes[1] || '';
                });
            }
        </script>
    @endif
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar protocolo, aluno ou escola"></label>
        </div>
        <div class="tabela-wrap">
    <table>
        <thead>
            <tr><th>Protocolo</th><th>Aluno</th><th>Escola</th><th>Situação</th><th>Ações</th></tr>
        </thead>
        <tbody>
            @forelse($pedidos as $pedido)
                <tr data-busca="{{ $pedido->protocolo }} {{ $pedido->nome_aluno }} {{ $pedido->nome_responsavel }} {{ $pedido->escola_informada }} {{ $pedido->rota }} {{ $pedido->situacao }}">
                    <td>{{ $pedido->protocolo }}</td>
                    <td>{{ $pedido->nome_aluno }}</td>
                    <td>{{ $pedido->escola_informada }}</td>
                    <td>
                        @if(!empty($pedido->documentos_em))
                            Documentação conferida. Aluno na rota {{ $pedido->rota_vinculada ?: 'informada' }}.
                        @else
                            {{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}
                        @endif
                    </td>
                    <td class="acoes">
                        @php
                            $segue = \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::recebePedido($pedido->situacao_processo ?? null, $pedido->situacao_grupo ?? null, $pedido->situacao_oferta ?? null);
                            $ofertaPedido = $ofertas->firstWhere('id', $pedido->oferta_id);
                            $semVaga = $ofertaPedido && !\iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::cabe((int) $ofertaPedido->vagas, (int) (optional($usoOferta->get($pedido->oferta_id))->ocupadas ?? 0));
                        @endphp
                        <button type="button" class="icone ver" data-abre-modal="pedido-{{ $pedido->id }}" aria-label="Ver protocolo" title="Ver protocolo"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        <a class="icone imprimir" href="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/pdf') }}" aria-label="Imprimir" title="Imprimir"><i class="fa fa-print" aria-hidden="true"></i></a>
                        @if($rede && $pedido->situacao === 'deferida')
                            <button type="button" class="icone editar" data-abre-modal="pedido-{{ $pedido->id }}" aria-label="Conferir documentação" title="Conferir documentação"><i class="fa fa-file-text-o" aria-hidden="true"></i></button>
                        @endif
                        @if($segue && $pedido->situacao === 'recebida')
                            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                                @csrf
                                <button class="icone encaminhar" name="acao" value="encaminhar" aria-label="Encaminhar" title="Encaminhar"><i class="fa fa-share" aria-hidden="true"></i></button>
                            </form>
                        @endif
                        @if($segue && $rede && $semVaga && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                                @csrf
                                <button class="icone editar" name="acao" value="esperar" aria-label="Lista de espera" title="Lista de espera"><i class="fa fa-hourglass-half" aria-hidden="true"></i></button>
                            </form>
                        @endif
                        @if($segue && $rede && $pedido->situacao === 'espera')
                            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                                @csrf
                                <button class="icone aceitar" name="acao" value="deferir" aria-label="Chamar da lista" title="Chamar da lista" @disabled($semVaga)><i class="fa fa-level-up" aria-hidden="true"></i></button>
                            </form>
                        @endif
                        @if($rede && $pedido->situacao === 'espera')
                            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                                @csrf
                                <button class="icone cancelar" name="acao" value="indeferir" aria-label="Retirar da lista" title="Retirar da lista"><i class="fa fa-sign-out" aria-hidden="true"></i></button>
                            </form>
                        @endif
                        @if($segue && $rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                            <button type="button" class="icone aceitar" data-abre-modal="aprovar-{{ $pedido->id }}" aria-label="Aceitar" title="Aceitar"><i class="fa fa-check" aria-hidden="true"></i></button>
                        @endif
                        @if($rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                            <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                                @csrf
                                <button class="icone cancelar" name="acao" value="indeferir" aria-label="Recusar" title="Recusar"><i class="fa fa-times" aria-hidden="true"></i></button>
                            </form>
                        @endif
                    </td>
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
        <dialog class="modal pedido" id="pedido-{{ $pedido->id }}">
            <div class="modal-cabeca">
                <h2>{{ $pedido->protocolo }} · {{ $pedido->nome_aluno }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-corpo">
                <dl class="resumo">
                    <div class="largo"><dt>Situação</dt><dd>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($pedido->situacao) }}</dd></div>
                    <div><dt>Pedido em</dt><dd>{{ \Illuminate\Support\Carbon::parse($pedido->pedido_em)->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Nascimento</dt><dd>{{ \Illuminate\Support\Carbon::parse($pedido->nascimento)->format('d/m/Y') }}</dd></div>
                    <div><dt>Telefone</dt><dd>{{ $telefone }}</dd></div>
                    <div><dt>Responsável</dt><dd>{{ $pedido->nome_responsavel }}</dd></div>
                    <div><dt>Escola</dt><dd>{{ $pedido->escola_informada }}</dd></div>
                    <div><dt>Turno</dt><dd>{{ $turno }}</dd></div>
                    <div><dt>Série</dt><dd>{{ $pedido->nm_serie ?: '—' }}</dd></div>
                    <div><dt>Turma</dt><dd>{{ $pedido->nm_turma ?: '—' }}</dd></div>
                    <div><dt>Processo</dt><dd>{{ $pedido->processo ?: '—' }}</dd></div>
                    <div><dt>Rota pedida</dt><dd>{{ $pedido->rota ?: '—' }}</dd></div>
                    @if(!empty($pedido->documentos_em))
                        <div><dt>Rota do aluno</dt><dd>{{ $pedido->rota_vinculada ?: 'informada' }}</dd></div>
                    @endif
                    <div class="largo"><dt>Endereço</dt><dd>{{ $pedido->endereco }}</dd></div>
                    <div class="largo" data-proximos="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/proximos') }}">
                        <dt>Pontos próximos</dt>
                        <dd class="pontos">
                            <p data-lista-pontos>Calculando a distância…</p>
                            <a href="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/mapa') }}" target="_blank" rel="noopener">Abrir mapa</a>
                        </dd>
                    </div>
                    @if($pedido->motivo_situacao)
                        <div class="largo"><dt>Observação</dt><dd>{{ $pedido->motivo_situacao }}</dd></div>
                    @endif
                </dl>
                @if($pedido->situacao === 'recebida' || ($rede && in_array($pedido->situacao, ['recebida', 'em_analise', 'espera'], true)))
                    @php
                        $segue = \iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::recebePedido($pedido->situacao_processo ?? null, $pedido->situacao_grupo ?? null, $pedido->situacao_oferta ?? null);
                        $ofertaPedido = $ofertas->firstWhere('id', $pedido->oferta_id);
                        $semVaga = $ofertaPedido && !\iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga::cabe((int) $ofertaPedido->vagas, (int) (optional($usoOferta->get($pedido->oferta_id))->ocupadas ?? 0));
                    @endphp
                    <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                        @csrf
                        <label>Observação da decisão <input name="motivo" maxlength="255" placeholder="Opcional. A família vê este texto ao acompanhar o protocolo."></label>
                        @if(!$segue)
                            <p class="lead">Este processo ou agrupamento está pausado. Dá para recusar o pedido, mas não encaminhar nem aceitar.</p>
                        @endif
                        <div class="acao">
                            @if($segue && $pedido->situacao === 'recebida')
                                <button name="acao" value="encaminhar">Encaminhar</button>
                            @endif
                            @if($segue && $rede && $semVaga && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                                <button name="acao" value="esperar">Lista de espera</button>
                            @endif
                            @if($segue && $rede && $pedido->situacao === 'espera' && !$semVaga)
                                <button name="acao" value="deferir">Chamar da lista</button>
                            @endif
                            @if($segue && $rede && $pedido->situacao === 'espera' && $semVaga)
                                <p class="lead">Ainda não há vaga para chamar este aluno.</p>
                            @endif
                            @if($segue && $rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
                                <button type="button" data-abre-modal="aprovar-{{ $pedido->id }}">Aceitar</button>
                            @endif
                            @if($rede && in_array($pedido->situacao, ['recebida', 'em_analise', 'espera'], true))
                                <button name="acao" value="indeferir">{{ $pedido->situacao === 'espera' ? 'Retirar da lista' : 'Recusar' }}</button>
                            @endif
                        </div>
                    </form>
                @endif
                @if($rede && $pedido->situacao === 'deferida')
                    @php
                        $processoPedido = $processos->firstWhere('id', $pedido->processo_id);
                        $exigidos = \iEducar\Packages\BuritiTransporteEscolar\Support\HistoricoPedido::exigidos($processoPedido->documentos_exigidos ?? null);
                    @endphp
                    <form class="alocar" method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/vincular') }}" data-conferencia data-url="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id.'/matriculas') }}">
                        @csrf
                        <input type="hidden" name="aluno_id" value="">
                        <input type="hidden" name="matricula_id" value="">
                        <input type="hidden" name="turno" value="">
                        @if((int) session('conferir') === (int) $pedido->id && session('erro'))
                            <p class="flash erro">{{ session('erro') }}</p>
                        @endif
                        <h3>Conferir documentação e alocar na rota</h3>
                        <div class="linha">
                            <label>Rota
                                <select name="rota_id" required>
                                    @foreach($rotas->unique('id') as $rota)
                                        @if((int) $rota->ano === (int) $pedido->ano)
                                            <option value="{{ $rota->id }}" data-periodo="{{ $rota->periodo }}" data-periodo-rotulo="{{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$rota->periodo] ?? $rota->periodo }}" @selected((int) $pedido->rota_id === (int) $rota->id)>{{ $rota->ano }} · {{ $rota->descricao }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </label>
                            <label>Turno da rota <input data-turno-rotulo value="—" readonly></label>
                        </div>
                        <p class="lead">A matrícula é feita no i-Educar. Pode ficar em branco aqui: o aluno entra na rota e o aviso permanece em Alunos na rota. Turno da turma diferente do da rota também permanece como aviso.</p>
                        <div class="linha">
                            <label>Nome, matrícula ou CPF <input data-q value="{{ $pedido->nome_aluno }}"></label>
                            <label>Matrícula ativa
                                <select data-escolha>
                                    <option value="">Sem matrícula por enquanto</option>
                                </select>
                            </label>
                        </div>
                        <p><button type="button" class="botao claro" data-buscar>Buscar matrícula</button></p>
                        <div data-avisos class="avisos"></div>
                        @if($exigidos === [])
                            <label class="opcao"><input type="checkbox" name="documentos" value="1"> Documentação conferida. Este processo não tem lista obrigatória.</label>
                        @else
                            <div class="opcoes">
                                @foreach($exigidos as $indice => $documento)
                                    <label class="opcao"><input type="checkbox" name="exigido[]" value="{{ $indice }}"> {{ $documento }}</label>
                                @endforeach
                            </div>
                        @endif
                        <div class="acao"><button type="submit">Conferir e incluir na rota</button></div>
                    </form>
                @endif
                @php
                    $gravados = isset($historicos) ? $historicos->get($pedido->id, collect())->all() : [];
                    $eventos = \iEducar\Packages\BuritiTransporteEscolar\Support\HistoricoPedido::linhas($pedido, array_values($gravados));
                @endphp
                <section class="historico">
                    <h3>Histórico</h3>
                    <p class="lead">Agora: {{ \iEducar\Packages\BuritiTransporteEscolar\Support\HistoricoPedido::lugar($pedido) }}</p>
                    <ol class="linha-tempo">
                        @foreach($eventos as $evento)
                            <li>
                                <strong>{{ \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido::frase($evento->situacao) }}</strong>
                                <span>{{ \Illuminate\Support\Carbon::parse($evento->em)->format('d/m/Y H:i') }} · {{ $evento->ator }}</span>
                                <span>{{ $evento->lugar }}</span>
                                @if(!empty($evento->detalhe))<span>{{ $evento->detalhe }}</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        </dialog>
        @if($rede && in_array($pedido->situacao, ['recebida', 'em_analise'], true))
            @php
                $ofertaAprovacao = $ofertas->firstWhere('id', $pedido->oferta_id);
                $vagasAprovacao = $ofertaAprovacao ? (int) $ofertaAprovacao->vagas : null;
                $ocupadasAprovacao = (int) (optional($usoOferta->get($pedido->oferta_id))->ocupadas ?? 0);
                $disponiveisAprovacao = $vagasAprovacao === null ? null : max(0, $vagasAprovacao - $ocupadasAprovacao);
            @endphp
            <dialog class="modal" id="aprovar-{{ $pedido->id }}">
                <div class="modal-cabeca"><h2>Aprovar {{ $pedido->protocolo }}</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/solicitacoes/'.$pedido->id) }}">
                    @csrf
                    @if($disponiveisAprovacao === null)
                        <p class="lead">Este pedido não está ligado a uma vaga publicada.</p>
                        <button name="acao" value="deferir">Confirmar aceite</button>
                    @elseif($disponiveisAprovacao > 0)
                        <p class="lead">Há {{ $disponiveisAprovacao }} {{ $disponiveisAprovacao === 1 ? 'vaga disponível' : 'vagas disponíveis' }} de {{ $vagasAprovacao }} nesta rota.</p>
                        <button name="acao" value="deferir">Confirmar aceite</button>
                    @else
                        <p class="lead">A linha está lotada: {{ $vagasAprovacao }} vagas e {{ $ocupadasAprovacao }} ocupadas. O aluno entra na lista de espera.</p>
                        <label class="opcao"><input type="checkbox" name="ciente" value="1" required> Estou ciente de que não há vaga e o pedido vai para a lista de espera.</label>
                        <button name="acao" value="esperar">Colocar na lista de espera</button>
                    @endif
                </form>
            </dialog>
        @endif
    @endforeach
    <script>
        document.querySelectorAll('[data-conferencia]').forEach(function (bloco) {
            var avisos = bloco.querySelector('[data-avisos]');
            var escolha = bloco.querySelector('[data-escolha]');
            var aluno = bloco.querySelector('[name=aluno_id]');
            var matricula = bloco.querySelector('[name=matricula_id]');
            var rota = bloco.querySelector('[name=rota_id]');
            var turno = bloco.querySelector('[name=turno]');
            var turnoRotulo = bloco.querySelector('[data-turno-rotulo]');
            var achados = [];
            function aplicarTurno() {
                var opcao = rota.options[rota.selectedIndex];
                var periodo = opcao ? (opcao.getAttribute('data-periodo') || '') : '';
                turno.value = periodo;
                turnoRotulo.value = opcao ? (opcao.getAttribute('data-periodo-rotulo') || periodo || '—') : '—';
            }
            function endereco(extra) {
                return bloco.getAttribute('data-url') + '?' + extra + '&rota_id=' + encodeURIComponent(rota.value || '') + '&turno=' + encodeURIComponent(turno.value || '');
            }
            function pintar(item) {
                aluno.value = item.id || '';
                matricula.value = item.matricula || '';
                avisos.innerHTML = '';
                avisos.className = 'avisos ' + (item.ok ? 'ok' : 'alerta');
                (item.avisos || []).forEach(function (texto) {
                    var p = document.createElement('p');
                    p.textContent = texto;
                    avisos.appendChild(p);
                });
            }
            bloco.querySelector('[data-buscar]').addEventListener('click', function () {
                fetch(endereco('q=' + encodeURIComponent(bloco.querySelector('[data-q]').value || '')), { headers: { 'Accept': 'application/json' } })
                    .then(function (resposta) { return resposta.json(); })
                    .then(function (dados) {
                        achados = dados.alunos || [];
                        escolha.innerHTML = '<option value="">Nenhuma matrícula escolhida</option>';
                        aluno.value = '';
                        matricula.value = '';
                        avisos.innerHTML = '';
                        if (!achados.length) {
                            avisos.className = 'avisos alerta';
                            avisos.textContent = 'Nenhuma matrícula ativa encontrada neste ano.';
                            return;
                        }
                        achados.forEach(function (item, indice) {
                            var opcao = document.createElement('option');
                            opcao.value = String(indice);
                            opcao.textContent = item.rotulo;
                            escolha.appendChild(opcao);
                        });
                    });
            });
            escolha.addEventListener('change', function () {
                var item = achados[Number(escolha.value)];
                if (!item) {
                    aluno.value = '';
                    matricula.value = '';
                    avisos.innerHTML = '';
                    return;
                }
                pintar(item);
            });
            function atualizar() {
                if (!aluno.value) return;
                fetch(endereco('aluno_id=' + encodeURIComponent(aluno.value) + '&matricula_id=' + encodeURIComponent(matricula.value || '')), { headers: { 'Accept': 'application/json' } })
                    .then(function (resposta) { return resposta.json(); })
                    .then(pintar);
            }
            rota.addEventListener('change', function () {
                aplicarTurno();
                atualizar();
            });
            aplicarTurno();
        });
        @if(session('conferir'))
        var pedidoAberto = document.getElementById('pedido-{{ (int) session('conferir') }}');
        if (pedidoAberto && pedidoAberto.showModal) pedidoAberto.showModal();
        @endif
    </script>
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
