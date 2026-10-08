@extends('transporte-escolar::layout')

@section('transporte')
    <h1>Uso offline</h1>
    <p>O navegador guarda a rota, os alunos e a fila. Ao abrir com rede, a descarga sobe os eventos. A janela das 05:00 e das 12:30 só abre em dia letivo do calendário da escola; se o ano ainda não tem calendário, vale dia de semana. O que continua no celular aparece para a secretaria, por motorista e por dia.</p>
    <p>Fila neste navegador: <strong id="fila-local">0</strong>. Última descarga: <strong id="ultima-descarga">{{ $dispositivo->ultima_descarga_em ?? 'ainda não houve' }}</strong></p>
    <button type="button" id="descarregar">Descarregar agora</button>
    <h2>Registrar na fila local</h2>
    <form id="evento-local">
        <input name="tipo" value="embarque" required>
        <input name="viagem_id" placeholder="Viagem" required>
        <input name="aluno_id" placeholder="Aluno" required>
        <button type="submit">Guardar sem enviar</button>
    </form>
    <pre id="pacote" style="white-space:pre-wrap"></pre>
    @if($pendentes->isNotEmpty())
        <h2>Ainda no celular</h2>
        <ul>
            @foreach($pendentes as $item)
                <li>{{ $item->motorista ?: $item->nome }} · {{ \Illuminate\Support\Carbon::parse($item->dia)->format('d/m/Y') }} · {{ $item->quantidade }} evento(s)</li>
            @endforeach
        </ul>
    @endif
    @if($atrasados->isNotEmpty())
        <h2>Aparelhos</h2>
        <ul>
            @foreach($atrasados as $item)
                <li>{{ $item->nome }} · última descarga {{ $item->ultima_descarga_em ?? 'nunca' }}</li>
            @endforeach
        </ul>
    @endif
    <script>
        const chaveFila = 'transporte-v2-fila';
        const chavePacote = 'transporte-v2-pacote';
        const filaEl = document.getElementById('fila-local');
        const lerFila = () => JSON.parse(localStorage.getItem(chaveFila) || '[]');
        const mostrar = () => { filaEl.textContent = String(lerFila().length); };
        mostrar();
        const pacoteGuardado = localStorage.getItem(chavePacote);
        if (pacoteGuardado) document.getElementById('pacote').textContent = pacoteGuardado;
        document.getElementById('evento-local').addEventListener('submit', (evento) => {
            evento.preventDefault();
            const dados = Object.fromEntries(new FormData(evento.target).entries());
            dados.uuid = crypto.randomUUID();
            dados.ocorrido_em = new Date().toISOString();
            const fila = lerFila();
            fila.push(dados);
            localStorage.setItem(chaveFila, JSON.stringify(fila));
            mostrar();
            evento.target.reset();
            avisarPendente();
        });
        async function avisarPendente() {
            if (!navigator.onLine) return;
            const dias = {};
            lerFila().forEach((item) => {
                const dia = String(item.ocorrido_em || '').slice(0, 10) || new Date().toISOString().slice(0, 10);
                dias[dia] = (dias[dia] || 0) + 1;
            });
            await fetch(@json(url('/transporte-escolar/offline/pendente')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                body: JSON.stringify({ dias })
            });
        }
        async function descarregar() {
            if (!navigator.onLine) return;
            const resposta = await fetch(@json(url('/transporte-escolar/offline/fila')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                body: JSON.stringify({ ano: {{ $ano }}, eventos: lerFila() })
            });
            if (!resposta.ok) return;
            const corpo = await resposta.json();
            localStorage.setItem(chavePacote, JSON.stringify(corpo.pacote));
            localStorage.setItem(chaveFila, '[]');
            document.getElementById('pacote').textContent = JSON.stringify(corpo.pacote, null, 2);
            document.getElementById('ultima-descarga').textContent = corpo.descarga_em;
            mostrar();
            avisarPendente();
        }
        document.getElementById('descarregar').addEventListener('click', descarregar);
        const diaLetivo = @json($diaLetivo);
        if (navigator.onLine) {
            avisarPendente();
            descarregar();
        }
        setInterval(() => {
            const momento = new Date();
            const fimDeSemana = momento.getDay() === 0 || momento.getDay() === 6;
            const bloqueado = diaLetivo === false || (diaLetivo === null && fimDeSemana);
            const agoraMin = momento.getHours() * 60 + momento.getMinutes();
            const janela = [5 * 60, 12 * 60 + 30].some((alvo) => Math.abs(agoraMin - alvo) <= 20);
            if (navigator.onLine && !bloqueado && janela) descarregar();
        }, 60000);
    </script>
@endsection
