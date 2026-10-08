@extends('transporte-escolar::portal.layout')

@section('titulo', 'Pedir transporte escolar')

@section('portal')
    <h1>Pedido de transporte</h1>
    <p class="lead">O pedido entra num processo de vagas do ano letivo. A lista mostra só escola e rota que a rede publicou e que ainda têm lugar.</p>
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
        <label for="oferta_id">Processo, escola e rota</label>
        <p class="dica">O turno em que o aluno estuda. A rota da manhã não atende o pedido da tarde. A vaga é desta rota, neste ano.</p>
        <select id="oferta_id" name="oferta_id" required>
            <option value="">Escolha o processo, a escola e a rota</option>
            @foreach($ofertas as $oferta)
                <option value="{{ $oferta->id }}" @selected((string) ($velho['oferta_id'] ?? '') === (string) $oferta->id)>
                    {{ $oferta->ano }} · {{ $oferta->processo }} · {{ $oferta->escola }} · {{ $oferta->rota }} · {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::PERIODO[$oferta->turno] ?? $oferta->turno }} · {{ $oferta->livres }} {{ $oferta->livres === 1 ? 'vaga' : 'vagas' }}
                </option>
            @endforeach
        </select>
        <div class="acoes">
            <button type="submit">
                <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                Enviar pedido
            </button>
        </div>
    </form>
    @endif
@endsection
