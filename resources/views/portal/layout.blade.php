<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Transporte escolar')</title>
    <style>
        :root {
            --verde: #47728f;
            --verde-escuro: #3d657f;
            --verde-claro: #e7f1f6;
            --ambar: #188ad1;
            --ambar-claro: #fff4d8;
            --fundo: #f4f7f4;
            --tinta: #173028;
            --suave: #4e6358;
            --linha: #d3e0d7;
            --erro: #9b2c2c;
            --erro-fundo: #fdeceb;
            --ok: #17663f;
            --ok-fundo: #e5f6ec;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: var(--fundo); color: var(--tinta); line-height: 1.5; }
        header { background: var(--verde); color: #fff; }
        .brasao { width: 48px; height: 48px; object-fit: contain; background: #fff; border-radius: 8px; padding: 3px; }
        .faixa { height: 8px; background: var(--ambar); }
        .topo { display: flex; justify-content: space-between; gap: 16px; align-items: center; max-width: 1100px; margin: 0 auto; padding: 16px 20px; }
        .marca { display: flex; gap: 12px; align-items: center; color: #fff; text-decoration: none; }
        .marca strong { display: block; font-size: 1.05rem; }
        .marca small { display: block; font-weight: 400; opacity: .85; }
        .icone { width: 28px; height: 28px; display: inline-flex; }
        .icone svg { width: 28px; height: 28px; }
        .marca .icone { width: 42px; height: 42px; background: var(--ambar); color: var(--verde-escuro); border-radius: 12px; align-items: center; justify-content: center; }
        .marca .icone svg { width: 26px; height: 26px; }
        nav { display: flex; gap: 8px; }
        nav a { color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.35); border-radius: 999px; padding: 8px 14px; display: inline-flex; gap: 8px; align-items: center; }
        nav a .icone, nav a .icone svg { width: 18px; height: 18px; }
        main { max-width: 1100px; margin: 0 auto; padding: 28px 20px 48px; }
        h1 { margin: 0 0 8px; font-size: 1.9rem; line-height: 1.2; }
        .lead { margin: 0 0 20px; font-size: 1.08rem; color: var(--suave); }
        .cartao { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 20px; }
        .passos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 0 0 20px; }
        .passo { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 16px; }
        .passo .icone { width: 36px; height: 36px; border-radius: 10px; background: var(--verde-claro); color: var(--verde); align-items: center; justify-content: center; margin-bottom: 8px; }
        .passo .icone svg { width: 22px; height: 22px; }
        .passo strong { display: block; margin-bottom: 4px; }
        .passo p { margin: 0; color: var(--suave); }
        .aviso { background: var(--ambar-claro); border-radius: 16px; padding: 16px 18px; margin-top: 16px; }
        .aviso strong { color: var(--ambar); }
        label { display: block; margin: 16px 0 6px; font-weight: 700; }
        .dica { margin: 0 0 4px; color: var(--suave); font-size: .92rem; }
        input, select { width: 100%; padding: 12px; border: 1px solid var(--linha); border-radius: 10px; font-size: 1rem; background: #fff; color: var(--tinta); }
        button, .botao { display: inline-flex; gap: 8px; align-items: center; background: var(--verde); color: #fff; border: 0; border-radius: 10px; padding: 12px 18px; font-size: 1rem; text-decoration: none; cursor: pointer; }
        .botao .icone, .botao .icone svg, button .icone, button .icone svg { width: 18px; height: 18px; }
        .botao.claro { background: #fff; color: var(--verde); border: 1px solid var(--verde); }
        .botao.perigo { background: #fff; color: var(--erro); border: 1px solid #e7b4b0; }
        .acoes { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
        .erro, .ok { border-radius: 12px; padding: 12px 14px; margin-bottom: 14px; }
        .erro { background: var(--erro-fundo); color: var(--erro); }
        .erro p { margin: 0 0 4px; }
        .ok { background: var(--ok-fundo); color: var(--ok); }
        .protocolo { font-size: 1.45rem; letter-spacing: .04em; font-weight: 800; margin: 8px 0; }
        .selo { display: inline-block; border-radius: 999px; padding: 4px 10px; font-size: .85rem; font-weight: 700; background: var(--verde-claro); color: var(--verde); }
        .selo.deferida { background: var(--ok-fundo); color: var(--ok); }
        .selo.indeferida, .selo.cancelada { background: var(--erro-fundo); color: var(--erro); }
        .selo.em_analise { background: var(--ambar-claro); color: var(--ambar); }
        footer { background: #fff; border-top: 1px solid var(--linha); }
        .rodape { max-width: 1100px; margin: 0 auto; padding: 18px 20px 28px; color: var(--suave); display: grid; gap: 6px; }
        .mapa { width: 100%; height: 280px; border: 0; border-radius: 12px; margin-top: 12px; }
        @media (max-width: 720px) {
            .passos { grid-template-columns: 1fr; }
            .topo { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header>
        <div class="faixa"></div>
        <div class="topo">
            <a class="marca" href="{{ url('/transporte-escolar/familia') }}">
                @if(!empty($logoMunicipio))
                    <img class="brasao" src="{{ $logoMunicipio }}" alt="Logomarca de {{ $municipio }}">
                @endif
                <span class="icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="14" height="9" rx="2"/><path d="M17 9h3l1 3v3h-4"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/></svg>
                </span>
                <span><strong>{{ $municipio ?? 'Transporte escolar' }}</strong><small>Pedido da família</small></span>
            </a>
            <nav>
                <a href="{{ url('/transporte-escolar/familia/pedir') }}">
                    <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg></span>
                    Pedir
                </a>
                <a href="{{ url('/transporte-escolar/familia/protocolo') }}">
                    <span class="icone" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></span>
                    Acompanhar
                </a>
            </nav>
        </div>
    </header>
    <main>
        @yield('portal')
    </main>
    <footer>
        <div class="rodape">
            <strong>O que esta página faz</strong>
            <span>O andamento do pedido fica na página Acompanhar. A prefeitura não pede senha e não pede dados do aluno por rede social.</span>
            <span>Se alguém pedir senha, foto de documento ou pagamento por WhatsApp, Instagram ou outra rede, não envie.</span>
        </div>
    </footer>
</body>
</html>
