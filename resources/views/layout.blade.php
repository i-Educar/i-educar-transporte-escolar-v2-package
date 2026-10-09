<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Transporte escolar')</title>
    <link rel="stylesheet" href="{{ url('/intranet/styles/font-awesome.css') }}">
    <style>
        :root {
            --azul: #1f4e66;
            --azul-escuro: #16384a;
            --azul-claro: #e7eef2;
            --destaque: #0e7490;
            --fundo: #e7ebef;
            --tinta: #1a242b;
            --suave: #5b6b76;
            --linha: #d3dbe2;
            --erro: #9b2c2c;
            --erro-fundo: #fdeceb;
            --ok: #17663f;
            --ok-fundo: #e5f6ec;
            --nav: #15222b;
            --nav-texto: #d5e0e6;
            --verde: var(--azul);
            --verde-escuro: var(--azul-escuro);
            --verde-claro: var(--azul-claro);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; font-size: 14px; background: var(--fundo); color: var(--tinta); line-height: 1.45; }
        .app { display: flex; min-height: 100vh; }
        .lateral { width: 248px; flex: none; background: var(--nav); color: var(--nav-texto); position: sticky; top: 0; height: 100vh; overflow: auto; }
        .marca { display: flex; gap: 10px; align-items: center; color: #fff; text-decoration: none; padding: 14px 14px 12px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .marca strong { display: block; font-size: .95rem; }
        .marca small { display: block; font-weight: 400; color: #8aa0ad; }
        .brasao { width: 36px; height: 36px; object-fit: contain; background: #fff; border-radius: 4px; padding: 2px; }
        .icone { width: 36px; height: 36px; background: var(--destaque); color: #fff; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .icone svg { width: 22px; height: 22px; }
        .modulo { padding: 8px 10px 2px; }
        .modulo-nome { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 8px; margin: 0; padding: 8px; background: transparent; border: 0; color: #8aa0ad; font: inherit; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; cursor: pointer; text-align: left; }
        .modulo-nome i { transition: transform .15s ease; }
        .modulo.aberto .modulo-nome i { transform: rotate(180deg); }
        .modulo-itens { display: none; }
        .modulo.aberto .modulo-itens { display: block; }
        .modulo a { display: flex; align-items: center; gap: 10px; color: var(--nav-texto); text-decoration: none; border-radius: 4px; padding: 7px 10px; }
        .modulo a i { width: 16px; text-align: center; }
        .modulo a:hover { background: rgba(255,255,255,.06); }
        .modulo a.ativo { background: var(--cor, var(--destaque)); color: #fff; font-weight: 700; }
        .miolo { flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .topo { position: sticky; top: 0; z-index: 20; min-height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 18px; background: #fff; border-bottom: 1px solid var(--linha); }
        .migalha { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0; color: var(--suave); font-size: 13px; }
        .migalha a { color: var(--azul); font-weight: 700; text-decoration: none; }
        .migalha strong { color: var(--tinta); }
        #busca-comandos { width: min(960px, calc(100% - 32px)); }
        .busca-grade { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: start; }
        .tema-card { border: 1px solid var(--linha); border-radius: 4px; background: #fff; overflow: hidden; border-top: 3px solid var(--cor, var(--azul)); }
        .tema-card header { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; background: #f7f9fa; }
        .tema-card header strong { display: block; color: var(--tinta); }
        .tema-card header small { display: block; color: var(--suave); font-weight: 500; }
        .tema-card header > i { width: 32px; height: 32px; border-radius: 4px; display: grid; place-items: center; color: #fff; background: var(--cor, var(--azul)); }
        .tema-card ul { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin: 0; padding: 10px; background: #fff; border: 0; }
        .tema-card a { display: flex; align-items: center; gap: 8px; min-height: 40px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 4px; color: var(--tinta); text-decoration: none; font-weight: 600; }
        .tema-card a i { width: 26px; height: 26px; border-radius: 4px; display: grid; place-items: center; color: var(--cor, var(--azul)); background: color-mix(in srgb, var(--cor, var(--azul)) 14%, #fff); flex: none; }
        .tema-card a:hover { border-color: var(--cor, var(--azul)); background: #f7f9fa; }
        @media (max-width: 720px) { .busca-grade, .tema-card ul { grid-template-columns: 1fr; } }
        .barra-acoes { display: flex; align-items: center; gap: 8px; }
        .hamburguer { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; background: #fff; color: var(--tinta); border: 1px solid var(--linha); border-radius: 4px; cursor: pointer; }
        .acao-topo { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 40px; height: 40px; padding: 0; border: 1px solid #e7f2ff; border-radius: 4px; background: #fff; color: #0072ff; line-height: 1; text-decoration: none; cursor: pointer; font-size: 16px; }
        .acao-topo:hover, .acao-topo[aria-expanded="true"] { background: #e7f2ff; color: #0b555d; }
        .acao-topo svg { display: block; width: 22px; height: 22px; }
        main { padding: 18px 20px 28px; flex: 1; }
        .flash { border-radius: 12px; padding: 12px 14px; margin: 0 0 16px; }
        .flash.ok { background: var(--ok-fundo); color: var(--ok); }
        .flash.erro { background: var(--erro-fundo); color: var(--erro); }
        .conteudo h1 { margin: 0 0 4px; font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: 8px; letter-spacing: 0; }
        .conteudo h1 i { color: var(--destaque); font-size: .9em; }
        .conteudo .lead { margin: 0 0 16px; color: var(--suave); }
        .conteudo h2 { margin: 28px 0 10px; font-size: 1.15rem; }
        .conteudo a { color: var(--azul); }
        .barra-dados { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin: 0 0 12px; }
        .barra-dados h2 { margin: 0; font-size: 1.05rem; }
        .filtro { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--linha); border-radius: 10px; padding: 0 12px; min-width: min(100%, 320px); flex: 1 1 240px; }
        .filtro i { color: var(--suave); }
        .filtro input { border: 0; padding: 10px 0; width: 100%; font: inherit; color: var(--tinta); background: transparent; outline: none; }
        .cabeca-tela { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: -18px -20px 16px; padding: 12px 12px 12px 20px; background: #fff; border-bottom: 1px solid var(--linha); border-left: 4px solid var(--cor-tela, var(--azul)); }
        .identidade-tela { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .marca-tela { width: 40px; height: 40px; border-radius: 4px; display: grid; place-items: center; flex: none; background: var(--cor-tela, var(--azul)); color: #fff; font-size: 16px; }
        .cabeca-tela h1 { margin: 0; font-size: 1.25rem; line-height: 1.2; }
        .cabeca-tela .lead { margin: 2px 0 0; }
        .acoes-tela { margin-left: auto; display: none; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: 8px; }
        .acoes-tela.com-acoes { display: flex; }
        .acoes-tela form.acao, .acoes-tela .acao { justify-content: flex-end; }
        .botao { display: inline-flex; align-items: center; gap: 8px; background: var(--cor-tela, var(--azul)); color: #fff; border: 0; border-radius: 4px; padding: 8px 12px; font: inherit; text-decoration: none; cursor: pointer; }
        .botao.claro { background: #fff; color: var(--cor-tela, var(--azul)); border: 1px solid color-mix(in srgb, var(--cor-tela, var(--azul)) 35%, var(--linha)); }
        .conteudo a.botao { color: #fff; }
        .conteudo a.botao.claro { color: var(--azul); }
        .tabela-wrap { overflow-x: auto; background: #fff; border: 1px solid var(--linha); border-radius: 4px; }
        .conteudo table { width: 100%; border-collapse: collapse; background: #fff; margin: 0; }
        .conteudo th, .conteudo td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--linha); vertical-align: middle; }
        .conteudo th { background: var(--azul-claro); font-size: .86rem; color: var(--azul-escuro); white-space: nowrap; }
        .conteudo tr:last-child td { border-bottom: 0; }
        .conteudo tbody tr:hover td { background: #f8fbfc; }
        .tom-prazo { color: #b86e00; }
        .tom-pedido { color: #188ad1; }
        .tom-lotacao { color: #9b2c2c; }
        .tom-servico { color: #0f6b4c; }
        .sino { position: relative; }
        .galeria-foto { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 14px; }
        .galeria-foto img { width: 132px; height: 96px; object-fit: cover; border-radius: 10px; border: 1px solid #e7f2ff; background: #f7f9fa; }
        .galeria-foto.carteirinha img { width: 108px; height: 136px; }
        .sino-contagem { position: absolute; top: 4px; right: 4px; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 999px; background: #f3c15b; color: #24323c; font-size: .68rem; font-weight: 800; line-height: 16px; }
        .gaveta { position: absolute; top: calc(100% + 8px); right: 0; width: min(380px, calc(100vw - 24px)); background: #fff; color: var(--tinta); border: 1px solid var(--linha); border-radius: 4px; box-shadow: 0 12px 28px rgba(21, 34, 43, .16); z-index: 50; }
        .gaveta header, .gaveta footer { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; }
        .gaveta header { border-bottom: 1px solid var(--linha); font-weight: 700; }
        .gaveta footer { border-top: 1px solid var(--linha); }
        .gaveta a { color: var(--azul); font-weight: 700; text-decoration: none; }
        .gaveta ul { list-style: none; margin: 0; padding: 6px; max-height: 320px; overflow: auto; }
        .gaveta li a { display: grid; grid-template-columns: 28px 1fr; gap: 8px; padding: 8px; border-radius: 8px; color: var(--tinta); font-weight: 600; }
        .gaveta li a:hover { background: var(--azul-claro); }
        .gaveta li small { display: block; color: var(--suave); font-weight: 500; }
        .toasts { position: fixed; right: 16px; bottom: 16px; z-index: 60; display: grid; gap: 8px; width: min(360px, calc(100vw - 32px)); }
        .toast { display: grid; grid-template-columns: 28px 1fr; gap: 8px; background: #fff; border: 1px solid var(--linha); border-left: 4px solid var(--destaque); border-radius: 12px; padding: 10px 12px; box-shadow: 0 10px 24px rgba(36, 50, 60, .12); }
        .toast.prazo { border-left-color: #b86e00; }
        .toast.pedido { border-left-color: #188ad1; }
        .toast.lotacao { border-left-color: #9b2c2c; }
        .toast.servico { border-left-color: #0f6b4c; }
        .toast strong { display: block; }
        .toast span { color: var(--suave); font-size: .86rem; }
        .pos-sino { position: relative; }
        .conteudo tr[hidden] { display: none; }
        .acao { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .acao button, .conteudo button, .conteudo input[type="submit"] { background: var(--cor-tela, var(--azul)); color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; font: inherit; cursor: pointer; }
        .conteudo td.acoes { display: table-cell; white-space: nowrap; width: 1%; text-align: right; vertical-align: middle; }
        .conteudo td.acoes form, .conteudo .modal .acao { display: inline-flex; flex-flow: row nowrap; gap: 4px; margin: 0; padding: 0; background: none; border: 0; vertical-align: middle; align-items: center; }
        .conteudo td.acoes > .icone, .conteudo td.acoes > a, .conteudo td.acoes > button { vertical-align: middle; }
        .conteudo td.acoes .botao { padding: 4px 8px; font-size: .78rem; }
        .conteudo .acoes .icone, .conteudo button.icone, .conteudo a.icone { width: 32px; height: 32px; padding: 0; display: inline-grid; place-items: center; border-radius: 8px; border: 0; text-decoration: none; cursor: pointer; font-size: 14px; line-height: 1; }
        .conteudo .icone.ver { background: #eef3f7; color: #47728f; }
        .conteudo .icone.editar { background: #fff6e8; color: #b86e00; }
        .conteudo .icone.aceitar { background: #e7f6ef; color: #0f6b4c; }
        .conteudo .icone.imprimir { background: #e8f3fb; color: #188ad1; }
        .conteudo .icone.encaminhar { background: #eef3f7; color: #47728f; }
        .conteudo .icone.cancelar { background: #fdeceb; color: #9b2c2c; }
        .conteudo .icone.pausar { background: #f3eef8; color: #5c4d7a; }
        .conteudo .icone.marcado { box-shadow: inset 0 0 0 2px currentColor; }
        .aluno-linha { display: flex; align-items: center; gap: 12px; }
        .aluno-linha svg { width: 52px; height: 52px; flex: none; background: #fff; }
        .aluno-linha small { display: block; color: var(--suave); font-weight: 500; }
        .diario tr.ausente td:first-child { box-shadow: inset 3px 0 #9b2c2c; }
        .conteudo input:not([type="checkbox"]):not([type="radio"]):not([type="search"]), .conteudo select, .conteudo textarea { padding: 8px 10px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; color: var(--tinta); background: #fff; }
        .vazio { color: var(--suave); padding: 16px; }
        .conteudo .ficha { display: grid; grid-template-columns: 150px 1fr; gap: 8px 12px; margin: 0; }
        .conteudo .ficha dt { color: var(--suave); font-weight: 700; }
        .conteudo .ficha dd { margin: 0; }
        .conteudo > form:not(.acao) { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 16px 18px; margin: 12px 0 20px; }
        .cartao-rota { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 16px 18px; margin: 12px 0 20px; }
        .cartao-rota h2 { margin: 0 0 8px; }
        .cartao-rota form { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin: 12px 0; }
        .cartao-rota label { display: grid; gap: 4px; font-weight: 600; }
        .numeros { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 0 0 16px; }
        .numeros.gestao { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .numeros article { background: #fff; border: 1px solid var(--linha); border-top: 3px solid var(--azul); border-radius: 4px; padding: 12px 14px; }
        .numeros article.rede { border-top-color: #47728f; }
        .numeros article.ano { border-top-color: #188ad1; }
        .numeros article.viagem { border-top-color: #0f6b4c; }
        .numeros article.frota { border-top-color: #b86e00; }
        .numeros article.conta { border-top-color: #5c4d7a; }
        .numeros strong { display: block; font-size: 1.35rem; color: var(--azul-escuro); }
        .numeros span { color: var(--suave); font-size: .86rem; }
        .painel-grupo { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 14px 16px; margin: 0 0 16px; }
        .painel-grupo > h2 { margin: 0 0 4px; display: flex; align-items: center; gap: 10px; font-size: 1.05rem; }
        .painel-grupo > h2 .marca-grupo { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none; }
        .painel-grupo > .lead { margin: 0 0 14px; }
        .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .kpi { display: flex; gap: 12px; align-items: center; min-width: 0; border: 1px solid var(--linha); border-radius: 4px; padding: 10px 12px; background: #f7f9fa; color: inherit; text-decoration: none; }
        a.kpi:hover { border-color: currentColor; }
        .kpi .marca-kpi { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; flex: none; font-size: 16px; }
        .kpi strong { display: block; font-size: 1.25rem; color: var(--azul-escuro); line-height: 1.15; }
        .kpi em { display: block; font-style: normal; color: var(--suave); font-size: .8rem; }
        .kpi.azul, .marca-grupo.azul { color: #47728f; }
        .kpi.azul .marca-kpi, .marca-grupo.azul { background: #e7f1f6; }
        .kpi.destaque, .marca-grupo.destaque { color: #188ad1; }
        .kpi.destaque .marca-kpi, .marca-grupo.destaque { background: #e8f3fb; }
        .kpi.verde, .marca-grupo.verde { color: #0f6b4c; }
        .kpi.verde .marca-kpi, .marca-grupo.verde { background: #e7f6ef; }
        .kpi.ambar, .marca-grupo.ambar { color: #b86e00; }
        .kpi.ambar .marca-kpi, .marca-grupo.ambar { background: #fff6e8; }
        .kpi.roxo, .marca-grupo.roxo { color: #5c4d7a; }
        .kpi.roxo .marca-kpi, .marca-grupo.roxo { background: #f3eef8; }
        .kpi.alerta, .marca-grupo.alerta { color: #9b2c2c; }
        .kpi.alerta .marca-kpi, .marca-grupo.alerta { background: #fdeceb; }
        .opcoes-relatorio { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin: 0 0 16px; }
        .opcao-relatorio { display: grid; grid-template-columns: 36px 1fr; column-gap: 10px; row-gap: 2px; padding: 12px; background: #fff; border: 1px solid var(--linha); border-top: 3px solid var(--cor, var(--azul)); border-radius: 4px; color: inherit; }
        .opcao-relatorio .marca-opcao { grid-row: 1 / span 2; width: 36px; height: 36px; display: grid; place-items: center; border-radius: 4px; background: color-mix(in srgb, var(--cor) 14%, #fff); color: var(--cor); }
        .opcao-relatorio strong { font-size: .92rem; }
        .opcao-relatorio p { margin: 0; color: var(--suave); font-size: .78rem; }
        .opcao-relatorio .acao { grid-column: 1 / -1; display: flex; gap: 8px; margin-top: 8px; }
        .opcao-relatorio .acao a { font-size: .78rem; }
        .grade-relatorio { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
        .grade-relatorio .painel-grupo { margin: 0; }
        .selo { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 8px; font-size: .78rem; font-weight: 700; background: #eef3f7; color: #47728f; }
        .selo.ok { background: #e7f6ef; color: #0f6b4c; }
        .selo.alerta { background: #fdeceb; color: #9b2c2c; }
        .selo.prazo { background: #fff6e8; color: #b86e00; }
        .conteudo tr.atencao td:first-child { box-shadow: inset 3px 0 0 #9b2c2c; }
        .conteudo tr.atencao .selos-atencao { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .paginas { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: flex-end; margin: 10px 0 16px; color: var(--suave); }
        .paginas a, .conteudo .paginas button, .paginas .atual, .paginas .mudo { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; min-height: 32px; padding: 4px 8px; border: 1px solid var(--linha); border-radius: 4px; background: #fff; color: var(--azul); font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .paginas .atual, .conteudo .paginas button[aria-current="page"] { background: var(--azul); color: #fff; border-color: var(--azul); }
        .paginas .mudo, .conteudo .paginas button:disabled { color: #9aa7b0; background: #f4f7f8; cursor: default; }
        .paginas .contagem { border: 0; background: transparent; min-width: 0; font-weight: 500; color: var(--suave); }
        .conteudo th .ordenador { display: inline-flex; align-items: center; gap: 6px; background: transparent; color: inherit; border: 0; padding: 0; font: inherit; font-weight: 700; cursor: pointer; }
        .conteudo th .ordenador::after { content: "↕"; opacity: .4; font-size: .8em; }
        .conteudo th[aria-sort="ascending"] .ordenador::after { content: "↑"; opacity: 1; }
        .conteudo th[aria-sort="descending"] .ordenador::after { content: "↓"; opacity: 1; }
        @media (max-width: 1100px) {
            .numeros.gestao { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .kpis, .grade-relatorio { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .numeros.gestao, .kpis, .grade-relatorio { grid-template-columns: 1fr; }
        }
        .conteudo ul { background: #fff; border: 1px solid var(--linha); border-radius: 12px; margin: 8px 0 16px; padding: 12px 12px 12px 32px; }
        .pagina { margin: 0 0 14px; }
        .mesa { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
        .diretorio { display: grid; gap: 12px; }
        .bloco, .fila { background: #fff; border: 1px solid var(--linha); border-radius: 4px; padding: 12px 14px; margin: 0 0 12px; }
        .bloco h2, .fila h2 { margin: 0; font-size: .95rem; }
        .bloco h2 small { display: block; font-weight: 500; color: var(--suave); font-size: .78rem; }
        .conteudo .atalhos { list-style: none; margin: 10px 0 0; padding: 0; background: transparent; border: 0; border-radius: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px; }
        .atalhos a { display: flex; align-items: center; gap: 8px; min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 4px; color: var(--tinta); text-decoration: none; background: #f7f9fa; font-weight: 600; }
        .atalhos a i { width: 16px; text-align: center; color: var(--destaque); }
        .atalhos a:hover { border-color: var(--destaque); background: #fff; }
        .fila header { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin: 0 0 10px; }
        .fila header a { font-weight: 700; text-decoration: none; }
        .indicadores { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 10px; }
        .indicadores span { display: inline-flex; align-items: center; gap: 6px; background: #f7f9fa; border: 1px solid var(--linha); border-radius: 4px; padding: 4px 8px; font-weight: 600; font-size: .8rem; }
        .indicadores i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .indicadores .pedido i { background: #188ad1; }
        .indicadores .prazo i { background: #b86e00; }
        .indicadores .lotacao i { background: #9b2c2c; }
        .indicadores .servico i { background: #0f6b4c; }
        .conteudo .fila ul { list-style: none; margin: 0; padding: 0; background: transparent; border: 0; border-radius: 0; display: grid; gap: 6px; }
        .fila li { flex-wrap: wrap; }
        .fila li form { margin: 0; }
        .fila li { display: flex; align-items: flex-start; gap: 8px; padding: 8px 10px; border: 1px solid var(--linha); border-left-width: 3px; border-radius: 4px; background: #f7f9fa; }
        .fila li.pedido { border-left-color: #188ad1; }
        .fila li.prazo { border-left-color: #b86e00; }
        .fila li.lotacao { border-left-color: #9b2c2c; }
        .fila li.servico { border-left-color: #0f6b4c; }
        .fila li i { margin-top: 2px; }
        .fila li.pedido i { color: #188ad1; }
        .fila li.prazo i { color: #b86e00; }
        .fila li.lotacao i { color: #9b2c2c; }
        .fila li.servico i { color: #0f6b4c; }
        .fila li span { flex: 1; }
        .fila .vazio { margin: 0; }
        .grafico { display: grid; gap: 6px; margin: 8px 0 12px; }
        .grafico-linha { display: grid; grid-template-columns: 110px 1fr; gap: 8px; align-items: center; font-size: .8rem; color: var(--suave); }
        .trilho { display: grid; gap: 3px; background: #f4f7f8; border-radius: 6px; padding: 4px; }
        .trilho i { display: block; height: 8px; border-radius: 4px; min-width: 0; }
        .trilho .planejado, .legenda-grafico .planejado { background: #188ad1; }
        .trilho .realizado, .legenda-grafico .realizado { background: #0f6b4c; }
        .trilho .fonte-pnate { background: #5c4d7a; }
        .trilho .fonte-proprio { background: #188ad1; }
        .trilho .fonte-outros { background: #b86e00; }
        .legenda-grafico { display: flex; gap: 14px; align-items: center; font-size: .8rem; color: var(--suave); }
        .legenda-grafico i { width: 12px; height: 8px; border-radius: 4px; display: inline-block; }
        dialog.modal { border: 0; border-radius: 8px; padding: 0; width: min(820px, calc(100% - 32px)); max-height: calc(100vh - 48px); overflow: hidden; box-shadow: 0 18px 50px rgba(20, 32, 42, .28); }
        dialog.modal[open] { display: flex; flex-direction: column; }
        dialog.modal::backdrop { background: rgba(20, 32, 42, .5); }
        .modal-cabeca { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex: none; padding: 14px 18px; border-bottom: 1px solid var(--linha); background: #fff; }
        .modal-cabeca h2 { margin: 0; font-size: 1.02rem; font-weight: 700; letter-spacing: 0; display: flex; align-items: center; gap: 8px; }
        .modal-cabeca h2 i { color: var(--azul); }
        .modal-cabeca button { width: 32px; height: 32px; display: grid; place-items: center; background: #fff; color: var(--suave); border: 1px solid var(--linha); border-radius: 6px; font-size: 1.15rem; line-height: 1; cursor: pointer; padding: 0; }
        .modal-cabeca button:hover { background: var(--azul-claro); color: var(--azul-escuro); }
        .modal form, .modal-corpo { padding: 16px 18px 18px; display: grid; gap: 12px; align-content: start; overflow: auto; flex: 1 1 auto; min-height: 0; background: #f4f7f9; }
        .modal form:has(.modal-miolo) { display: flex; flex-direction: column; gap: 0; padding: 0; overflow: hidden; }
        .modal-miolo { padding: 16px 18px 18px; display: grid; gap: 12px; align-content: start; overflow: auto; flex: 1 1 auto; min-height: 0; }
        .modal-rodape { display: flex; justify-content: flex-end; gap: 8px; flex: none; margin: 0; padding: 12px 18px; background: #fff; border-top: 1px solid var(--linha); }
        .modal-rodape button, .modal-rodape .botao { min-width: 112px; justify-content: center; border-radius: 6px; }
        .conteudo .modal-rodape .botao.claro { background: #fff; color: var(--azul-escuro); border: 1px solid var(--linha); }
        .ficha { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; margin: 0; background: #fff; border: 1px solid var(--linha); border-radius: 8px; padding: 12px 14px; }
        .ficha > div { display: grid; gap: 2px; min-width: 0; }
        .ficha .largo { grid-column: 1 / -1; }
        .ficha dt { margin: 0; font-size: .72rem; color: var(--suave); font-weight: 700; letter-spacing: .02em; }
        .ficha dd { margin: 0; font-weight: 600; }
        dialog.modal > .ficha { margin: 16px 18px 18px; overflow: auto; flex: 1 1 auto; min-height: 0; }
        .modal label { display: grid; gap: 6px; font-size: .78rem; font-weight: 700; color: var(--suave); min-width: 0; }
        .modal label.opcao { display: flex; flex-direction: row; align-items: center; gap: 8px; font-size: .86rem; font-weight: 600; color: var(--tinta); background: #fff; border: 1px solid var(--linha); border-radius: 6px; padding: 8px 10px; }
        .modal label.opcao input { width: 16px; height: 16px; margin: 0; flex: none; }
        .modal .opcoes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; }
        .modal .linha { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: end; }
        .modal .linha > label, .modal form > label { min-width: 0; }
        .modal select, .modal input:not([type="checkbox"]), .modal textarea { width: 100%; min-height: 38px; border-radius: 6px; background: #fff; font-weight: 500; color: var(--tinta); }
        .modal textarea { min-height: 88px; resize: vertical; }
        .modal select:focus, .modal input:focus, .modal textarea:focus { outline: 2px solid rgba(24, 138, 209, .28); border-color: #188ad1; }
        footer.rodape-fixo { background: #fff; border-top: 1px solid var(--linha); }
        .rodape { padding: 10px 18px; color: var(--suave); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: .85rem; }
        @media (max-width: 1100px) {
            .mesa, .numeros { grid-template-columns: 1fr; }
        }
        @media (max-width: 860px) {
            .hamburguer { display: inline-flex; }
            .lateral { position: fixed; z-index: 40; transform: translateX(-105%); transition: transform .15s ease; }
            .lateral.aberta { transform: none; box-shadow: 8px 0 24px rgba(21, 34, 43, .28); }
            .modal .linha, .modal .opcoes, .ficha { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $grupos = \iEducar\Packages\BuritiTransporteEscolar\Support\MenuFluxo::grupos($menuTransporte);
        $paginaAtual = 'Transporte escolar';
        $moduloAtual = 'Transporte escolar';
        $iconeAtual = 'fa-th-large';
        $corAtual = '#1f4e66';
        $temasBusca = [
            'Início' => ['cor' => '#1f4e66', 'icone' => 'fa-th-large'],
            'Cadastros' => ['cor' => '#188ad1', 'icone' => 'fa-folder-open'],
            'Atendimento' => ['cor' => '#0f6b4c', 'icone' => 'fa-inbox'],
            'Operação' => ['cor' => '#b86e00', 'icone' => 'fa-road'],
            'Frota' => ['cor' => '#5c4d7a', 'icone' => 'fa-bus'],
            'Contas' => ['cor' => '#9b2c2c', 'icone' => 'fa-balance-scale'],
        ];
        $temas = [];
        $pedido = '/'.request()->path();
        $melhor = 0;
        foreach ($grupos as $grupoMenu) {
            $itens = [];
            $visual = $temasBusca[$grupoMenu['nome']] ?? ['cor' => '#1f4e66', 'icone' => 'fa-folder'];
            foreach ($grupoMenu['itens'] as $itemMenu) {
                $caminho = parse_url($itemMenu['url'], PHP_URL_PATH) ?: '';
                $casa = $caminho === '/transporte-escolar'
                    ? $pedido === '/transporte-escolar'
                    : ($caminho !== '' && ($pedido === $caminho || str_starts_with($pedido, rtrim($caminho, '/').'/')));
                if ($casa && strlen($caminho) >= $melhor) {
                    $melhor = strlen($caminho);
                    $paginaAtual = $itemMenu['rotulo'];
                    $moduloAtual = $grupoMenu['nome'];
                    $iconeAtual = $itemMenu['icone'];
                    $corAtual = $visual['cor'];
                } elseif ($itemMenu['ativo'] && $melhor === 0) {
                    $paginaAtual = $itemMenu['rotulo'];
                    $moduloAtual = $grupoMenu['nome'];
                    $iconeAtual = $itemMenu['icone'];
                    $corAtual = $visual['cor'];
                }
                $itens[] = [
                    'rotulo' => $itemMenu['rotulo'],
                    'url' => $itemMenu['url'],
                    'icone' => $itemMenu['icone'],
                ];
            }
            $temas[] = [
                'nome' => $grupoMenu['nome'],
                'frase' => $grupoMenu['frase'],
                'cor' => $visual['cor'],
                'icone' => $visual['icone'],
                'itens' => $itens,
            ];
        }
    @endphp
    <div class="app">
        <aside class="lateral" id="menu-transporte" aria-label="Módulos do transporte">
            <a class="marca" href="{{ url('/transporte-escolar') }}">
                @if(!empty($logoMunicipio))
                    <img class="brasao" src="{{ $logoMunicipio }}" alt="Logomarca de {{ $municipio }}">
                @else
                    <span class="icone" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="14" height="9" rx="1"/><path d="M17 9h3l1 3v3h-4"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/></svg>
                    </span>
                @endif
                <span>
                    <strong>Transporte</strong>
                    <small>{{ $municipio }}</small>
                </span>
            </a>
            @foreach($grupos as $grupoMenu)
                @php
                    $temaAtivo = false;
                    foreach ($grupoMenu['itens'] as $itemMenu) {
                        if ($itemMenu['ativo']) {
                            $temaAtivo = true;
                        }
                    }
                @endphp
                <nav class="modulo @if($temaAtivo) aberto @endif" data-modulo="{{ $grupoMenu['nome'] }}" data-ativo="{{ $temaAtivo ? '1' : '0' }}" style="--cor: {{ $temasBusca[$grupoMenu['nome']]['cor'] ?? '#1f4e66' }}">
                    <button type="button" class="modulo-nome" aria-expanded="{{ $temaAtivo ? 'true' : 'false' }}">
                        {{ $grupoMenu['nome'] }}
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="modulo-itens">
                        @foreach($grupoMenu['itens'] as $item)
                            <a class="@if($item['ativo']) ativo @endif" href="{{ $item['url'] }}"><i class="fa {{ $item['icone'] }}" aria-hidden="true"></i>{{ $item['rotulo'] }}</a>
                        @endforeach
                    </div>
                </nav>
            @endforeach
        </aside>
        <div class="miolo">
            <header class="topo">
                <button type="button" class="hamburguer" aria-expanded="false" aria-controls="menu-transporte" aria-label="Abrir módulos">
                    <i class="fa fa-bars" aria-hidden="true"></i>
                </button>
                <nav class="migalha" aria-label="Trilha">
                    <a href="{{ url('/transporte-escolar') }}">Transporte</a>
                    <span aria-hidden="true">/</span>
                    <span>{{ $moduloAtual }}</span>
                    <span aria-hidden="true">/</span>
                    <strong>{{ $paginaAtual }}</strong>
                </nav>
                <div class="barra-acoes">
                    <button type="button" class="acao-topo" data-abre-modal="busca-comandos" title="Buscar" aria-label="Buscar"><i class="fa fa-search" aria-hidden="true"></i></button>
                    <a class="acao-topo" href="{{ url('/transporte-escolar') }}" title="Início" aria-label="Início"><i class="fa fa-home" aria-hidden="true"></i></a>
                    <a class="acao-topo" href="{{ route('home') }}" title="Voltar ao i-Educar" aria-label="Voltar ao i-Educar">
                        <svg viewBox="0 0 16 16" width="22" height="22" aria-hidden="true">
                            <path fill="#0072ff" d="M13.12,10,8.06,1.22,2.88,10l.65.39.32-.53a1.93,1.93,0,0,1,1.57-.77A1.78,1.78,0,0,1,7.29,10.7H8.71a1.77,1.77,0,0,1,1.86-1.62,2,2,0,0,1,1.78,1.1h0l.11.2ZM10.57,8.32A2.58,2.58,0,0,0,8,10.22,2.6,2.6,0,0,0,5.42,8.31a2.84,2.84,0,0,0-.72.1L8.05,2.74l3.28,5.68A2.68,2.68,0,0,0,10.57,8.32Z"/>
                            <polygon fill="#8314f7" points="7.04 5.32 8.98 5.32 8.01 3.73 7.04 5.32"/>
                            <rect fill="#8314f7" x="2.87" y="11.57" width="0.77" height="3.21"/>
                            <rect fill="#8314f7" x="12.36" y="11.57" width="0.77" height="3.21"/>
                            <rect fill="#8314f7" x="7.63" y="11.57" width="0.77" height="1.89"/>
                        </svg>
                    </a>
                    @if(!empty($menuTransporte['painel']))
                        <div class="pos-sino">
                            <button type="button" class="acao-topo sino" id="sino" aria-expanded="false" aria-controls="gaveta-avisos" title="Notificações" aria-label="Notificações">
                                <i class="fa fa-bell" aria-hidden="true"></i>
                                <span class="sino-contagem" id="sino-contagem" hidden>0</span>
                            </button>
                            <div class="gaveta" id="gaveta-avisos" hidden>
                                <header>Notificações <a href="{{ url('/transporte-escolar/notificacoes') }}">Ver todas</a></header>
                                <ul id="gaveta-lista"><li><small>Consultando…</small></li></ul>
                            </div>
                        </div>
                    @endif
                </div>
            </header>
            <main style="--cor-tela: {{ $corAtual }}">
                @if(session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
                @if(session('erro')) <p class="flash erro">{{ session('erro') }}</p> @endif
                <header class="cabeca-tela">
                    <div class="identidade-tela">
                        <span class="marca-tela"><i class="fa {{ $iconeAtual }}" aria-hidden="true"></i></span>
                        <div>
                            <h1 id="titulo-tela">{{ $paginaAtual }}</h1>
                            <p class="lead" id="lead-tela" hidden></p>
                        </div>
                    </div>
                    <div class="acoes-tela" id="acoes-tela"></div>
                </header>
                <div class="conteudo">
                    @yield('transporte')
                </div>
            </main>
            <footer class="rodape-fixo">
                <div class="rodape">
                    <span>{{ $municipio }} · Transporte escolar</span>
                    <span>{{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>
    <dialog class="modal" id="busca-comandos">
        <div class="modal-cabeca"><h2><i class="fa fa-search" aria-hidden="true"></i> Buscar menu</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
        <div class="modal-corpo">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input id="busca-comando" type="search" placeholder="Tela ou módulo" autocomplete="off"></label>
            <div class="busca-grade" id="resultado-comando"></div>
        </div>
    </dialog>
    <div class="toasts" id="toasts" aria-live="polite"></div>
    <script>
        (function () {
            var titulo = document.getElementById('titulo-tela');
            var lead = document.getElementById('lead-tela');
            var destino = document.getElementById('acoes-tela');
            var h1 = document.querySelector('.conteudo h1');
            if (h1 && titulo) {
                var texto = h1.cloneNode(true);
                var icone = texto.querySelector('i');
                if (icone) {
                    icone.remove();
                }
                titulo.textContent = texto.textContent.trim();
                h1.remove();
            }
            var resumo = document.querySelector('.conteudo > .lead');
            if (resumo && lead) {
                lead.textContent = resumo.textContent.trim();
                lead.hidden = lead.textContent === '';
                resumo.remove();
            }
            if (!destino) {
                return;
            }
            var vistos = [];
            function guardar(el) {
                if (!el || vistos.indexOf(el) !== -1 || el.closest('dialog') || el.closest('td') || el.closest('.opcao-relatorio') || el.closest('.tabela-wrap')) {
                    return;
                }
                vistos.push(el);
                destino.appendChild(el);
            }
            document.querySelectorAll('.conteudo > form.acao, .conteudo > .acao, .conteudo > p.acao').forEach(guardar);
            var formTopo = document.querySelector('.conteudo form.acao');
            guardar(formTopo);
            var barra = document.querySelector('.conteudo .barra-dados');
            if (barra) {
                barra.querySelectorAll(':scope > a.botao, :scope > button.botao, :scope > form.acao, :scope > .acao').forEach(guardar);
            }
            if (!vistos.length) {
                var acao = document.querySelector('.conteudo .acao');
                guardar(acao);
            }
            if (vistos.length) {
                destino.classList.add('com-acoes');
            }
        })();
        (function () {
            var botao = document.querySelector('.hamburguer');
            var painel = document.getElementById('menu-transporte');
            if (!botao || !painel) {
                return;
            }
            botao.addEventListener('click', function () {
                var aberto = painel.classList.toggle('aberta');
                botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                botao.querySelector('i').className = aberto ? 'fa fa-times' : 'fa fa-bars';
            });
        })();
        (function () {
            var gravado = {};
            try { gravado = JSON.parse(localStorage.getItem('transporte-modulos') || '{}'); } catch (e) { gravado = {}; }
            document.querySelectorAll('.modulo').forEach(function (modulo) {
                var nome = modulo.getAttribute('data-modulo');
                if (modulo.getAttribute('data-ativo') !== '1' && gravado[nome]) {
                    modulo.classList.add('aberto');
                }
                var botao = modulo.querySelector('.modulo-nome');
                botao.setAttribute('aria-expanded', modulo.classList.contains('aberto') ? 'true' : 'false');
                botao.addEventListener('click', function () {
                    var aberto = modulo.classList.toggle('aberto');
                    botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                    gravado[nome] = aberto;
                    localStorage.setItem('transporte-modulos', JSON.stringify(gravado));
                });
            });
            var temas = @json($temas);
            var campo = document.getElementById('busca-comando');
            var lista = document.getElementById('resultado-comando');
            function pintar(termo) {
                var texto = termo.trim().toLowerCase();
                lista.innerHTML = '';
                var algum = false;
                temas.forEach(function (tema) {
                    var itens = tema.itens.filter(function (item) {
                        return texto === '' || (tema.nome + ' ' + tema.frase + ' ' + item.rotulo).toLowerCase().indexOf(texto) !== -1;
                    });
                    if (!itens.length) {
                        return;
                    }
                    algum = true;
                    var card = document.createElement('section');
                    card.className = 'tema-card';
                    card.style.setProperty('--cor', tema.cor);
                    card.innerHTML = '<header><span><strong></strong><small></small></span><i class="fa" aria-hidden="true"></i></header><ul></ul>';
                    card.querySelector('strong').textContent = tema.nome;
                    card.querySelector('small').textContent = tema.frase;
                    card.querySelector('header > i').className = 'fa ' + tema.icone;
                    var grade = card.querySelector('ul');
                    itens.forEach(function (item) {
                        var linha = document.createElement('li');
                        linha.innerHTML = '<a><i class="fa" aria-hidden="true"></i><span></span></a>';
                        linha.querySelector('a').href = item.url;
                        linha.querySelector('i').className = 'fa ' + item.icone;
                        linha.querySelector('span').textContent = item.rotulo;
                        grade.appendChild(linha);
                    });
                    lista.appendChild(card);
                });
                if (!algum) {
                    lista.innerHTML = '<p class="vazio">Nenhuma tela com esse nome.</p>';
                }
            }
            if (campo) {
                campo.addEventListener('input', function () { pintar(campo.value); });
                document.querySelector('[data-abre-modal="busca-comandos"]').addEventListener('click', function () {
                    setTimeout(function () { campo.focus(); pintar(campo.value); }, 0);
                });
            }
        })();
        document.querySelectorAll('[data-filtro-tabela]').forEach(function (campo) {
            campo.addEventListener('input', function () {
                var painel = campo.closest('[data-painel]') || document;
                var termo = campo.value.trim().toLowerCase();
                var tabelas = [];
                painel.querySelectorAll('tbody tr').forEach(function (linha) {
                    if (linha.hasAttribute('data-vazio')) {
                        return;
                    }
                    var texto = (linha.getAttribute('data-busca') || linha.textContent || '').toLowerCase();
                    linha.setAttribute('data-fora', termo !== '' && texto.indexOf(termo) === -1 ? '1' : '0');
                    var tabela = linha.closest('table');
                    if (tabela && tabelas.indexOf(tabela) === -1) {
                        tabelas.push(tabela);
                    }
                });
                tabelas.forEach(function (tabela) {
                    if (tabela.atualizarGrade) {
                        tabela.atualizarGrade();
                    }
                });
            });
        });
        (function () {
            var tamanho = 25;

            function linhas(corpo) {
                return Array.prototype.filter.call(corpo.rows, function (linha) {
                    return !linha.hasAttribute('data-vazio');
                });
            }

            function numero(valor) {
                var limpo = valor.replace(/\s/g, '').replace(/^R\$/, '').replace(/\./g, '').replace(',', '.');
                return /^-?\d+(\.\d+)?$/.test(limpo) ? parseFloat(limpo) : null;
            }

            function data(valor) {
                var encontrado = valor.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
                return encontrado ? Date.UTC(+encontrado[3], +encontrado[2] - 1, +encontrado[1]) : null;
            }

            function comparar(a, b) {
                var na = numero(a);
                var nb = numero(b);
                if (na !== null && nb !== null) {
                    return na - nb;
                }
                var da = data(a);
                var db = data(b);
                if (da !== null && db !== null) {
                    return da - db;
                }
                return a.localeCompare(b, 'pt');
            }

            function desenhar(nav, pagina, paginas, total, ir) {
                nav.innerHTML = '';
                function botao(rotulo, destino, atual, mudo) {
                    var el = document.createElement('button');
                    el.type = 'button';
                    el.textContent = rotulo;
                    if (atual) {
                        el.setAttribute('aria-current', 'page');
                    }
                    if (mudo) {
                        el.disabled = true;
                    } else {
                        el.addEventListener('click', function () { ir(destino); });
                    }
                    nav.appendChild(el);
                }
                botao('Anterior', pagina - 1, false, pagina <= 1);
                var fim = Math.min(paginas, Math.max(pagina + 3, 7));
                var inicio = Math.max(1, fim - 6);
                fim = Math.min(paginas, inicio + 6);
                for (var numeroPagina = inicio; numeroPagina <= fim; numeroPagina++) {
                    botao(String(numeroPagina), numeroPagina, numeroPagina === pagina, false);
                }
                botao('Próxima', pagina + 1, false, pagina >= paginas);
                var contagem = document.createElement('span');
                contagem.className = 'contagem';
                contagem.textContent = total === 0 ? '0 registros' : (pagina + ' de ' + paginas + ' · ' + total + (total === 1 ? ' registro' : ' registros'));
                nav.appendChild(contagem);
            }

            function mostrar(tabela, pagina) {
                if (!tabela._nav) {
                    return;
                }
                var corpo = tabela.tBodies[0];
                var todas = linhas(corpo);
                var visiveis = todas.filter(function (linha) { return linha.getAttribute('data-fora') !== '1'; });
                var paginas = Math.max(1, Math.ceil(visiveis.length / tamanho));
                pagina = Math.min(Math.max(1, pagina), paginas);
                var inicio = (pagina - 1) * tamanho;
                todas.forEach(function (linha) {
                    var indice = visiveis.indexOf(linha);
                    linha.hidden = indice === -1 || indice < inicio || indice >= inicio + tamanho;
                });
                var vazio = corpo.querySelector('[data-vazio]');
                if (vazio) {
                    vazio.hidden = visiveis.length > 0;
                }
                desenhar(tabela._nav, pagina, paginas, visiveis.length, function (destino) { mostrar(tabela, destino); });
            }

            document.querySelectorAll('.tabela-wrap > table').forEach(function (tabela) {
                var corpo = tabela.tBodies[0];
                var cabeca = tabela.tHead && tabela.tHead.rows[0];
                if (!corpo || !cabeca) {
                    return;
                }
                Array.prototype.forEach.call(cabeca.cells, function (celula, indice) {
                    var titulo = celula.textContent.trim();
                    if (titulo === '' || celula.querySelector('button, a, input, select')) {
                        return;
                    }
                    var botao = document.createElement('button');
                    botao.type = 'button';
                    botao.className = 'ordenador';
                    botao.textContent = titulo;
                    celula.textContent = '';
                    celula.appendChild(botao);
                    botao.addEventListener('click', function () {
                        var crescente = celula.getAttribute('aria-sort') !== 'ascending';
                        Array.prototype.forEach.call(cabeca.cells, function (outra) { outra.removeAttribute('aria-sort'); });
                        celula.setAttribute('aria-sort', crescente ? 'ascending' : 'descending');
                        var lista = linhas(corpo);
                        lista.sort(function (a, b) {
                            var va = a.cells[indice] ? a.cells[indice].textContent.trim() : '';
                            var vb = b.cells[indice] ? b.cells[indice].textContent.trim() : '';
                            return comparar(va, vb) * (crescente ? 1 : -1);
                        });
                        lista.forEach(function (linha) { corpo.appendChild(linha); });
                        mostrar(tabela, 1);
                    });
                });
                var servidor = tabela.parentElement.nextElementSibling && tabela.parentElement.nextElementSibling.classList.contains('paginas');
                if (servidor) {
                    tabela.atualizarGrade = function () {
                        linhas(corpo).forEach(function (linha) {
                            linha.hidden = linha.getAttribute('data-fora') === '1';
                        });
                    };
                    return;
                }
                var nav = document.createElement('nav');
                nav.className = 'paginas';
                nav.setAttribute('aria-label', 'Páginas');
                tabela.parentElement.after(nav);
                tabela._nav = nav;
                tabela.atualizarGrade = function () { mostrar(tabela, 1); };
                mostrar(tabela, 1);
            });
        })();
        document.querySelectorAll('[data-abre-modal]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var modal = document.getElementById(botao.getAttribute('data-abre-modal'));
                if (modal && modal.showModal) {
                    modal.showModal();
                }
                var bloco = modal ? modal.querySelector('[data-proximos]') : null;
                if (bloco && !bloco.dataset.consultado) {
                    bloco.dataset.consultado = '1';
                    var destino = bloco.querySelector('[data-lista-pontos]');
                    fetch(bloco.getAttribute('data-proximos'), { headers: { 'Accept': 'application/json' } })
                        .then(function (resposta) { return resposta.json(); })
                        .then(function (dados) {
                            if (!dados.ok) {
                                destino.textContent = dados.aviso || dados.message || 'Não foi possível calcular a distância.';
                                return;
                            }
                            if (!dados.pontos.length) {
                                destino.textContent = 'Nenhum ponto com coordenada cadastrada.';
                                return;
                            }
                            destino.textContent = dados.pontos.map(function (ponto) {
                                var texto = ponto.nome + ': ' + (ponto.metros >= 1000 ? (ponto.metros / 1000).toFixed(1).replace('.', ',') + ' km' : ponto.metros + ' m');
                                return ponto.dentro ? texto + ' (dentro do raio de ' + ponto.raio + ' m)' : texto;
                            }).join(' · ');
                        })
                        .catch(function () {
                            destino.textContent = 'Não foi possível calcular a distância agora.';
                        });
                }
            });
        });
        document.querySelectorAll('dialog.modal form').forEach(function (form) {
            if (form.querySelector('.modal-rodape')) {
                return;
            }
            var botoes = Array.prototype.filter.call(form.children, function (filho) {
                return filho.tagName === 'BUTTON';
            });
            if (!botoes.length) {
                return;
            }
            var gravar = botoes[botoes.length - 1];
            var miolo = document.createElement('div');
            var rodape = document.createElement('div');
            var cancelar = document.createElement('button');
            miolo.className = 'modal-miolo';
            while (form.firstChild) {
                miolo.appendChild(form.firstChild);
            }
            rodape.className = 'modal-rodape';
            cancelar.type = 'button';
            cancelar.className = 'botao claro';
            cancelar.setAttribute('data-fecha-modal', '');
            cancelar.textContent = 'Cancelar';
            rodape.appendChild(cancelar);
            rodape.appendChild(gravar);
            form.appendChild(miolo);
            form.appendChild(rodape);
        });
        document.addEventListener('click', function (evento) {
            var botao = evento.target.closest('[data-fecha-modal]');
            if (!botao) {
                return;
            }
            var modal = botao.closest('dialog');
            if (modal) {
                modal.close();
            }
        });
        var sino = document.getElementById('sino');
        if (sino) {
            var gaveta = document.getElementById('gaveta-avisos');
            var lista = document.getElementById('gaveta-lista');
            var contagem = document.getElementById('sino-contagem');
            var vistas = JSON.parse(sessionStorage.getItem('transporte-avisos') || '[]');
            var primeira = true;

            function toast(item) {
                var aviso = document.createElement('div');
                aviso.className = 'toast ' + item.tom;
                aviso.innerHTML = '<i class="fa ' + item.icone + '" aria-hidden="true"></i><div><strong></strong><span></span></div>';
                aviso.querySelector('strong').textContent = item.titulo;
                aviso.querySelector('span').textContent = item.texto;
                document.getElementById('toasts').appendChild(aviso);
                setTimeout(function () { aviso.remove(); }, 7000);
            }

            function pintar(dados) {
                contagem.hidden = dados.nao_lidas < 1;
                contagem.textContent = dados.nao_lidas > 9 ? '9+' : String(dados.nao_lidas);
                lista.innerHTML = '';
                if (!dados.recentes.length) {
                    lista.innerHTML = '<li><small>Nenhuma notificação em aberto.</small></li>';
                }
                dados.recentes.forEach(function (item) {
                    var linha = document.createElement('li');
                    linha.innerHTML = '<a href="/transporte-escolar/notificacoes/' + item.id + '"><i class="fa ' + item.icone + ' tom-' + item.tom + '" aria-hidden="true"></i><span><strong></strong><small></small></span></a>';
                    linha.querySelector('strong').textContent = item.titulo;
                    linha.querySelector('small').textContent = item.texto;
                    lista.appendChild(linha);
                    if (!primeira && vistas.indexOf(item.id) === -1) {
                        toast(item);
                    }
                    if (vistas.indexOf(item.id) === -1) {
                        vistas.push(item.id);
                    }
                });
                sessionStorage.setItem('transporte-avisos', JSON.stringify(vistas.slice(-40)));
                primeira = false;
            }

            function pulso() {
                fetch('/transporte-escolar/notificacoes/pulso', { headers: { 'Accept': 'application/json' } })
                    .then(function (resposta) { return resposta.ok ? resposta.json() : null; })
                    .then(function (dados) { if (dados) pintar(dados); });
            }

            sino.addEventListener('click', function () {
                var aberto = gaveta.hidden;
                gaveta.hidden = !aberto;
                sino.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            });
            document.addEventListener('click', function (evento) {
                if (!sino.parentElement.contains(evento.target)) {
                    gaveta.hidden = true;
                    sino.setAttribute('aria-expanded', 'false');
                }
            });
            pulso();
            setInterval(pulso, 20000);
        }
    </script>
</body>
</html>
