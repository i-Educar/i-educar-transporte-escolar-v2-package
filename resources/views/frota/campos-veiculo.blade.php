@php $veiculo = $veiculo ?? null; @endphp
<div class="linha">
    <label>Descrição <input name="descricao" value="{{ $veiculo->descricao ?? '' }}" required></label>
    <label>Placa <input name="placa" value="{{ $veiculo->placa ?? '' }}" maxlength="10"></label>
</div>
<div class="linha">
    <label>RENAVAM <input name="renavam" value="{{ $veiculo->renavam ?? '' }}"></label>
    <label>Passageiros <input name="passageiros" value="{{ $veiculo->passageiros ?? '' }}" required></label>
</div>
<label>Tipo no Censo
    <select name="tipo_censo" required>
        @foreach($codigos::modaisCenso() as $modal => $codigosModal)
            <optgroup label="{{ $modal }}">
                @foreach($codigosModal as $codigo)
                    <option value="{{ $codigo }}" @selected($veiculo && (int) $veiculo->tipo_censo === $codigo)>{{ $codigos::opcaoCenso($codigo) }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</label>
<div class="linha">
    <label>Malha
        <select name="malha">
            @foreach($codigos::MALHA as $codigo => $rotulo)
                <option value="{{ $codigo }}" @selected(($veiculo->malha ?? 'N') === $codigo)>{{ $rotulo }}</option>
            @endforeach
        </select>
    </label>
    <label>Consumo km/l <input name="consumo_medio_km_l" value="{{ $veiculo->consumo_medio_km_l ?? '' }}"></label>
</div>
<div class="linha">
    <label>Pneus <input name="qtd_pneus" value="{{ $veiculo->qtd_pneus ?? '' }}"></label>
    <label>IPVA <input type="date" name="ipva_vencimento" value="{{ $veiculo->ipva_vencimento ?? '' }}"></label>
</div>
<div class="linha">
    <label>Seguro <input type="date" name="seguro_vencimento" value="{{ $veiculo->seguro_vencimento ?? '' }}"></label>
    <label>Licenciamento <input type="date" name="licenciamento_vencimento" value="{{ $veiculo->licenciamento_vencimento ?? '' }}"></label>
</div>
<div class="linha">
    <label>Vistoria <input type="date" name="vistoria_vencimento" value="{{ $veiculo->vistoria_vencimento ?? '' }}"></label>
</div>
<div class="opcoes">
    <label class="opcao"><input type="checkbox" name="adaptado_pcd" value="1" @checked($veiculo->adaptado_pcd ?? false)> Adaptado para PCD</label>
    <label class="opcao"><input type="checkbox" name="caminho_da_escola" value="1" @checked($veiculo->caminho_da_escola ?? false)> Caminho da Escola</label>
    @if($veiculo)
        <label class="opcao"><input type="checkbox" name="ativo" value="1" @checked($veiculo->ativo)> Ativo</label>
    @endif
</div>
