@forelse($vinculo->responsaveis as $item)
    <span>{{ $item['papel'] }}: {{ $item['nome'] }}@if($item['telefone'] !== '') · {{ $item['telefone'] }}@endif</span>
@empty
    @if($somenteIeducar ?? false)
        <span>Nenhum responsável no i-Educar</span>
    @endif
@endforelse
@if(!($somenteIeducar ?? false))
    <span>Transporte: {{ $vinculo->transporte_nome !== '' ? $vinculo->transporte_nome : 'não informado' }}@if($vinculo->transporte_telefone !== '') · {{ $vinculo->transporte_telefone }}@endif</span>
@endif
