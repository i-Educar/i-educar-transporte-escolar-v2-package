@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-check-square-o" aria-hidden="true"></i> Conformidade da frota</h1>
    <p class="lead">A autorização do DETRAN ou da CIRETRAN, o licenciamento e o curso do motorista precisam estar vigentes para abrir uma viagem. O checklist guarda a evidência do veículo escolar.</p>
    <div data-painel>
        <div class="barra-dados">
            <form method="get" class="acao">
                <label>Veículo
                    <select name="veiculo" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($veiculos as $veiculo)
                            <option value="{{ $veiculo->id }}" @selected($veiculoId === (int) $veiculo->id)>{{ $veiculo->placa ?: $veiculo->descricao }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="nova-autorizacao"><i class="fa fa-plus" aria-hidden="true"></i> Autorização</button>
                <button type="button" class="botao claro" data-abre-modal="novo-checklist"><i class="fa fa-plus" aria-hidden="true"></i> Checklist</button>
            @endif
        </div>
        <h2>Autorizações</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Veículo</th><th>Órgão</th><th>Número</th><th>Validade</th><th>Anexo</th><th></th></tr></thead>
                <tbody>
                    @forelse($autorizacoes as $linha)
                        @php $tom = $linha->validade < $hoje ? 'alerta' : ($linha->validade <= $limite ? 'prazo' : 'ok'); @endphp
                        <tr data-busca="{{ $linha->placa }} {{ $linha->numero }}">
                            <td>{{ $linha->placa ?: $linha->descricao }}</td>
                            <td>{{ $linha->orgao === 'ciretran' ? 'CIRETRAN' : 'DETRAN' }}</td>
                            <td>{{ $linha->numero }}</td>
                            <td><span class="selo {{ $tom }}">{{ \Illuminate\Support\Carbon::parse($linha->validade)->format('d/m/Y') }}</span></td>
                            <td>@if($linha->anexo_caminho)<a href="{{ url('/transporte-escolar/conformidade/anexo/autorizacao/'.$linha->id) }}">{{ $linha->anexo_nome }}</a>@else — @endif</td>
                            <td class="acoes">@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-autorizacao-{{ $linha->id }}" aria-label="Editar autorização" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="6" class="vazio">Nenhuma autorização neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <h2>Checklist CONTRAN</h2>
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Veículo</th><th>Item</th><th>Situação</th><th>Observação</th><th>Anexo</th></tr></thead>
                <tbody>
                    @forelse($checklists as $linha)
                        <tr data-busca="{{ $linha->placa }} {{ $itens[$linha->item] ?? $linha->item }}">
                            <td>{{ $linha->placa }}</td>
                            <td>{{ $itens[$linha->item] ?? $linha->item }}</td>
                            <td><span class="selo {{ $linha->conforme ? 'ok' : 'alerta' }}">{{ $linha->conforme ? 'Conforme' : 'Pendente' }}</span></td>
                            <td>{{ $linha->observacao ?: '—' }}</td>
                            <td>@if($linha->anexo_caminho)<a href="{{ url('/transporte-escolar/conformidade/anexo/checklist/'.$linha->id) }}">{{ $linha->anexo_nome }}</a>@else — @endif</td>
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhum item de checklist neste filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($podeGravar)
        <dialog class="modal" id="nova-autorizacao">
            <div class="modal-cabeca"><h2>Nova autorização</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/conformidade/autorizacao') }}" enctype="multipart/form-data">
                @csrf
                <p><label>Veículo <select name="veiculo_id" required>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label></p>
                <p><label>Órgão <select name="orgao"><option value="detran">DETRAN</option><option value="ciretran">CIRETRAN</option></select></label></p>
                <div class="linha"><label>Número <input name="numero" required></label><label>Validade <input type="date" name="validade" required></label></div>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'anexo',
                    'rotulo' => 'Foto ou PDF do documento',
                    'tituloAcao' => 'Soltar o arquivo aqui',
                    'ajuda' => 'Como anexar no WhatsApp — toque ou arraste.',
                    'accept' => 'image/*,.pdf,application/pdf',
                    'icone' => 'fa-camera',
                ])
                <button type="submit">Gravar</button>
            </form>
        </dialog>
        <dialog class="modal" id="novo-checklist">
            <div class="modal-cabeca"><h2>Checklist</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
            <form method="post" action="{{ url('/transporte-escolar/conformidade/checklist') }}" enctype="multipart/form-data">
                @csrf
                <p><label>Veículo <select name="veiculo_id" required>@foreach($veiculos as $veiculo)<option value="{{ $veiculo->id }}">{{ $veiculo->placa ?: $veiculo->descricao }}</option>@endforeach</select></label></p>
                <p><label>Item <select name="item">@foreach($itens as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label></p>
                <p><label class="opcao"><input type="checkbox" name="conforme" value="1"> Conforme</label></p>
                <p><label>Observação <input name="observacao"></label></p>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'anexo',
                    'rotulo' => 'Foto do item (opcional)',
                    'tituloAcao' => 'Soltar a foto aqui',
                    'ajuda' => 'Tire a foto no celular e envie aqui.',
                    'accept' => 'image/*,.pdf,application/pdf',
                    'icone' => 'fa-camera',
                ])
                <button type="submit">Gravar</button>
            </form>
        </dialog>
        @foreach($autorizacoes as $linha)
            <dialog class="modal" id="editar-autorizacao-{{ $linha->id }}">
                <div class="modal-cabeca"><h2>Editar autorização</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/conformidade/autorizacao/'.$linha->id) }}" enctype="multipart/form-data">
                    @csrf
                    <p><label>Órgão <select name="orgao"><option value="detran" @selected($linha->orgao === 'detran')>DETRAN</option><option value="ciretran" @selected($linha->orgao === 'ciretran')>CIRETRAN</option></select></label></p>
                    <div class="linha"><label>Número <input name="numero" value="{{ $linha->numero }}" required></label><label>Validade <input type="date" name="validade" value="{{ $linha->validade }}" required></label></div>
                    @include('buriti-ui::campo-anexo', [
                        'campo' => 'anexo',
                        'rotulo' => 'Trocar o arquivo anexado',
                        'tituloAcao' => 'Soltar o novo arquivo aqui',
                        'ajuda' => 'Só se precisar substituir o documento atual.',
                        'accept' => 'image/*,.pdf,application/pdf',
                        'icone' => 'fa-exchange',
                    ])
                    <button type="submit">Gravar</button>
                </form>
            </dialog>
        @endforeach
    @endif
@endsection
