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
            --azul: #47728f;
            --azul-escuro: #35586e;
            --azul-claro: #e7f1f6;
            --destaque: #188ad1;
            --fundo: #f4f7f9;
            --tinta: #24323c;
            --suave: #5c6b76;
            --linha: #d5e0e8;
            --erro: #9b2c2c;
            --erro-fundo: #fdeceb;
            --ok: #17663f;
            --ok-fundo: #e5f6ec;
            --verde: var(--azul);
            --verde-escuro: var(--azul-escuro);
            --verde-claro: var(--azul-claro);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: var(--fundo); color: var(--tinta); line-height: 1.5; }
        .casca-topo { position: sticky; top: 0; z-index: 30; background: var(--azul); color: #fff; box-shadow: 0 2px 10px rgba(53, 88, 110, .22); }
        .faixa { height: 4px; background: var(--destaque); }
        .barra { display: flex; justify-content: space-between; gap: 16px; align-items: center; max-width: 1680px; margin: 0 auto; padding: 12px 20px; }
        .barra-acoes { display: flex; align-items: center; gap: 10px; }
        .hamburguer { display: inline-flex; align-items: center; gap: 8px; background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.45); border-radius: 999px; padding: 8px 14px; font: inherit; cursor: pointer; }
        .hamburguer:hover, .hamburguer[aria-expanded="true"] { background: rgba(255,255,255,.14); }
        .menu-painel { display: none; position: absolute; left: 0; right: 0; z-index: 40; background: #fff; color: var(--tinta); border-bottom: 1px solid var(--linha); box-shadow: 0 16px 32px rgba(36, 50, 60, .16); max-height: calc(100vh - 92px); overflow: auto; }
        .menu-painel.aberto { display: block; }
        .menu-miolo { max-width: 1680px; margin: 0 auto; padding: 8px 20px 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
        .tema { border: 1px solid var(--linha); border-radius: 12px; background: #fff; overflow: hidden; }
        .tema-botao { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 12px; background: var(--azul-claro); color: var(--azul-escuro); border: 0; padding: 12px 14px; font: inherit; cursor: pointer; text-align: left; }
        .tema-rotulo { display: flex; align-items: center; gap: 10px; }
        .tema-rotulo strong { display: block; }
        .tema-rotulo small { display: block; font-weight: 400; opacity: .85; }
        .passo { width: 28px; height: 28px; border-radius: 50%; display: inline-grid; place-items: center; background: var(--azul); color: #fff; font-weight: 700; flex: 0 0 auto; }
        .tema.aberto .passo { background: #fff; color: var(--azul); }
        .tema.aberto .tema-botao { background: var(--azul); color: #fff; }
        .tema-botao i { transition: transform .15s ease; }
        .tema.aberto .tema-botao i { transform: rotate(180deg); }
        .submenu { display: none; padding: 6px; }
        .tema.aberto .submenu { display: grid; }
        .submenu a { display: flex; align-items: center; gap: 8px; color: var(--tinta); text-decoration: none; border-radius: 8px; padding: 9px 10px; }
        .submenu a:hover { background: var(--azul-claro); }
        .submenu a.ativo { background: var(--azul); color: #fff; font-weight: 700; }
        .marca { display: flex; gap: 12px; align-items: center; color: #fff; text-decoration: none; }
        .marca strong { display: block; font-size: 1.05rem; }
        .marca small { display: block; font-weight: 400; opacity: .85; }
        .icone { width: 42px; height: 42px; background: var(--destaque); color: #fff; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .icone svg { width: 26px; height: 26px; }
        .voltar { display: inline-flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.4); border-radius: 999px; padding: 8px 14px; white-space: nowrap; }
        .voltar:hover { background: rgba(255,255,255,.12); }
        .brasao { width: 48px; height: 48px; object-fit: contain; background: #fff; border-radius: 8px; padding: 3px; }
        main { max-width: 1680px; margin: 0 auto; padding: 24px 20px 88px; }
        .flash { border-radius: 12px; padding: 12px 14px; margin: 0 0 16px; }
        .flash.ok { background: var(--ok-fundo); color: var(--ok); }
        .flash.erro { background: var(--erro-fundo); color: var(--erro); }
        .conteudo h1 { margin: 0 0 6px; font-size: 1.7rem; display: flex; align-items: center; gap: 10px; }
        .conteudo h1 i { color: var(--azul); }
        .conteudo .lead { margin: 0 0 16px; color: var(--suave); }
        .conteudo h2 { margin: 28px 0 10px; font-size: 1.15rem; }
        .conteudo a { color: var(--azul); }
        .barra-dados { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin: 0 0 12px; }
        .barra-dados h2 { margin: 0; font-size: 1.05rem; }
        .filtro { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--linha); border-radius: 10px; padding: 0 12px; min-width: min(100%, 320px); flex: 1 1 240px; }
        .filtro i { color: var(--suave); }
        .filtro input { border: 0; padding: 10px 0; width: 100%; font: inherit; color: var(--tinta); background: transparent; outline: none; }
        .botao { display: inline-flex; align-items: center; gap: 8px; background: var(--azul); color: #fff; border: 0; border-radius: 10px; padding: 10px 14px; font: inherit; text-decoration: none; cursor: pointer; }
        .botao.claro { background: #fff; color: var(--azul); border: 1px solid var(--linha); }
        .conteudo a.botao { color: #fff; }
        .conteudo a.botao.claro { color: var(--azul); }
        .tabela-wrap { overflow-x: auto; background: #fff; border: 1px solid var(--linha); border-radius: 12px; }
        .conteudo table { width: 100%; border-collapse: collapse; background: #fff; margin: 0; }
        .conteudo th, .conteudo td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--linha); vertical-align: middle; }
        .conteudo th { background: var(--azul-claro); font-size: .86rem; color: var(--azul-escuro); white-space: nowrap; }
        .conteudo tr:last-child td { border-bottom: 0; }
        .conteudo tr[hidden] { display: none; }
        .acao { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .acao button, .conteudo button, .conteudo input[type="submit"] { background: var(--azul); color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; font: inherit; cursor: pointer; }
        .conteudo td.acoes { display: table-cell; white-space: nowrap; width: 1%; text-align: right; vertical-align: middle; }
        .conteudo td.acoes form, .conteudo .modal .acao { display: inline-flex; flex-flow: row nowrap; gap: 4px; margin: 0; padding: 0; background: none; border: 0; vertical-align: middle; align-items: center; }
        .conteudo td.acoes > .icone, .conteudo td.acoes > a, .conteudo td.acoes > button { vertical-align: middle; }
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
        .conteudo > form:not(.acao) { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 16px 18px; margin: 12px 0 20px; }
        .cartao-rota { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 16px 18px; margin: 12px 0 20px; }
        .cartao-rota h2 { margin: 0 0 8px; }
        .cartao-rota form { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin: 12px 0; }
        .cartao-rota label { display: grid; gap: 4px; font-weight: 600; }
        .numeros { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 0 0 16px; }
        .numeros.gestao { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .numeros article { background: #fff; border: 1px solid var(--linha); border-top: 4px solid var(--azul); border-radius: 16px; padding: 14px 16px; }
        .numeros article.rede { border-top-color: #47728f; }
        .numeros article.ano { border-top-color: #188ad1; }
        .numeros article.viagem { border-top-color: #0f6b4c; }
        .numeros article.frota { border-top-color: #b86e00; }
        .numeros article.conta { border-top-color: #5c4d7a; }
        .numeros strong { display: block; font-size: 1.35rem; color: var(--azul-escuro); }
        .numeros span { color: var(--suave); font-size: .86rem; }
        .painel-grupo { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 16px 18px; margin: 0 0 16px; }
        .painel-grupo > h2 { margin: 0 0 4px; display: flex; align-items: center; gap: 10px; font-size: 1.05rem; }
        .painel-grupo > h2 .marca-grupo { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none; }
        .painel-grupo > .lead { margin: 0 0 14px; }
        .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .kpi { display: flex; gap: 12px; align-items: center; min-width: 0; border: 1px solid var(--linha); border-radius: 14px; padding: 12px 14px; background: #f8fbfc; color: inherit; text-decoration: none; }
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
        .grade-relatorio { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
        .grade-relatorio .painel-grupo { margin: 0; }
        .selo { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 8px; font-size: .78rem; font-weight: 700; background: #eef3f7; color: #47728f; }
        .selo.ok { background: #e7f6ef; color: #0f6b4c; }
        .selo.alerta { background: #fdeceb; color: #9b2c2c; }
        .selo.prazo { background: #fff6e8; color: #b86e00; }
        .paginas { display: flex; gap: 12px; align-items: center; justify-content: flex-end; margin: 12px 0 0; color: var(--suave); }
        .paginas a { font-weight: 700; text-decoration: none; }
        @media (max-width: 1100px) {
            .numeros.gestao { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .kpis, .grade-relatorio { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .numeros.gestao, .kpis, .grade-relatorio { grid-template-columns: 1fr; }
        }
        .conteudo ul { background: #fff; border: 1px solid var(--linha); border-radius: 12px; margin: 8px 0 16px; padding: 12px 12px 12px 32px; }
        .fluxograma { list-style: none; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 18px; margin: 0 0 16px; padding: 0; align-items: stretch; }
        .etapa { --etapa: var(--azul); position: relative; background: #fff; border: 1px solid var(--linha); border-top: 4px solid var(--etapa); border-radius: 16px; padding: 12px; display: flex; flex-direction: column; min-width: 0; }
        .etapa[data-passo="1"] { --etapa: #47728f; }
        .etapa[data-passo="2"] { --etapa: #188ad1; }
        .etapa[data-passo="3"] { --etapa: #0f6b4c; }
        .etapa[data-passo="4"] { --etapa: #b86e00; }
        .etapa[data-passo="5"] { --etapa: #5c4d7a; }
        .etapa header { display: flex; gap: 8px; align-items: flex-start; }
        .etapa strong { display: block; color: var(--etapa); font-size: .95rem; }
        .etapa small { display: block; color: var(--suave); font-size: .78rem; }
        .etapa .passo { background: var(--etapa); }
        .etapa:not(:last-child)::after { content: ""; position: absolute; right: -18px; top: 28px; border-top: 7px solid transparent; border-bottom: 7px solid transparent; border-left: 10px solid var(--etapa); }
        .icones-fluxo { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .icones-fluxo a { position: relative; width: 46px; height: 46px; border-radius: 14px; background: color-mix(in srgb, var(--etapa) 16%, #fff); color: var(--etapa); display: grid; place-items: center; text-decoration: none; }
        .icones-fluxo a:hover, .icones-fluxo a:focus-visible { background: var(--etapa); color: #fff; outline: none; }
        .icones-fluxo i { font-size: 18px; }
        .icones-fluxo .dica { position: absolute; left: 50%; bottom: calc(100% + 8px); transform: translateX(-50%); background: #24323c; color: #fff; font-size: .75rem; font-weight: 600; line-height: 1.2; padding: 5px 8px; border-radius: 8px; white-space: nowrap; opacity: 0; pointer-events: none; z-index: 6; }
        .icones-fluxo a:hover .dica, .icones-fluxo a:focus-visible .dica { opacity: 1; }
        .indicadores { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; }
        .indicadores span { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--linha); border-radius: 999px; padding: 6px 12px; font-weight: 600; }
        .indicadores i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .indicadores .pedido i { background: #188ad1; }
        .indicadores .prazo i { background: #b86e00; }
        .indicadores .lotacao i { background: #9b2c2c; }
        .indicadores .servico i { background: #0f6b4c; }
        .card-avisos { background: #fff; border: 1px solid var(--linha); border-radius: 16px; padding: 16px 18px; }
        .card-avisos h2 { margin: 0 0 8px; display: flex; align-items: center; gap: 8px; }
        .card-avisos h2 i { color: var(--destaque); }
        .card-avisos ul { list-style: none; margin: 0; padding: 0; background: transparent; border: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .card-avisos li { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid var(--linha); border-left-width: 4px; border-radius: 12px; background: #f8fbfc; }
        .card-avisos li.pedido { border-left-color: #188ad1; background: #eef6fb; }
        .card-avisos li.prazo { border-left-color: #b86e00; background: #fff8ee; }
        .card-avisos li.lotacao { border-left-color: #9b2c2c; background: #fdeceb; }
        .card-avisos li.servico { border-left-color: #0f6b4c; background: #e8f6ee; }
        .card-avisos li i { margin-top: 3px; }
        .card-avisos li.pedido i { color: #188ad1; }
        .card-avisos li.prazo i { color: #b86e00; }
        .card-avisos li.lotacao i { color: #9b2c2c; }
        .card-avisos li.servico i { color: #0f6b4c; }
        .card-avisos li span { flex: 1; }
        .card-avisos .vazio { margin: 0; }
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
        footer.rodape-fixo { position: fixed; left: 0; right: 0; bottom: 0; z-index: 25; background: #fff; border-top: 1px solid var(--linha); }
        .rodape { max-width: 1680px; margin: 0 auto; padding: 12px 20px; color: var(--suave); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: .92rem; }
        @media (max-width: 1100px) {
            .menu-miolo, .card-avisos ul, .numeros { grid-template-columns: 1fr; }
            .fluxograma { grid-template-columns: 1fr; gap: 22px; }
            .etapa:not(:last-child)::after { right: auto; left: 18px; top: auto; bottom: -16px; border-left: 7px solid transparent; border-right: 7px solid transparent; border-top: 10px solid var(--destaque); border-bottom: 0; }
        }
        @media (max-width: 720px) {
            .barra { align-items: flex-start; flex-direction: column; }
            .barra-acoes { width: 100%; justify-content: space-between; }
            .modal .linha, .modal .opcoes, .ficha { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $grupos = \iEducar\Packages\BuritiTransporteEscolar\Support\MenuFluxo::grupos($menuTransporte);
        $paginaAtual = 'Transporte escolar';
        foreach ($grupos as $grupoMenu) {
            foreach ($grupoMenu['itens'] as $itemMenu) {
                if ($itemMenu['ativo']) {
                    $paginaAtual = $itemMenu['rotulo'];
                }
            }
        }
    @endphp
    <header class="casca-topo">
        <div class="faixa"></div>
        <div class="barra">
            <a class="marca" href="{{ url('/transporte-escolar') }}">
                @if(!empty($logoMunicipio))
                    <img class="brasao" src="{{ $logoMunicipio }}" alt="Logomarca de {{ $municipio }}">
                @endif
                <span class="icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="14" height="9" rx="2"/><path d="M17 9h3l1 3v3h-4"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/></svg>
                </span>
                <span>
                    <strong>{{ $municipio }}</strong>
                    <small>Transporte escolar · {{ $visaoRotulo }}</small>
                </span>
            </a>
            <div class="barra-acoes">
                <button type="button" class="hamburguer" aria-expanded="false" aria-controls="menu-transporte">
                    <i class="fa fa-bars" aria-hidden="true"></i> Menu · {{ $paginaAtual }}
                </button>
                <a class="voltar" href="{{ route('home') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Voltar ao i-Educar</a>
            </div>
        </div>
        <nav class="menu-painel" id="menu-transporte" aria-label="Transporte escolar">
            <div class="menu-miolo">
                @foreach($grupos as $grupoMenu)
                    @php
                        $temaAberto = false;
                        foreach ($grupoMenu['itens'] as $itemMenu) {
                            if ($itemMenu['ativo']) {
                                $temaAberto = true;
                            }
                        }
                    @endphp
                    <section class="tema @if($temaAberto) aberto @endif">
                        <button type="button" class="tema-botao" aria-expanded="{{ $temaAberto ? 'true' : 'false' }}">
                            <span class="tema-rotulo">
                                @if($grupoMenu['numero'])
                                    <span class="passo">{{ $grupoMenu['numero'] }}</span>
                                @endif
                                <span>
                                    <strong>{{ $grupoMenu['nome'] }}</strong>
                                    <small>{{ $grupoMenu['frase'] }}</small>
                                </span>
                            </span>
                            <i class="fa fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="submenu">
                            @foreach($grupoMenu['itens'] as $item)
                                <a class="@if($item['ativo']) ativo @endif" href="{{ $item['url'] }}"><i class="fa {{ $item['icone'] }}" aria-hidden="true"></i>{{ $item['rotulo'] }}</a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </nav>
    </header>
    <main>
        @if(session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
        @if(session('erro')) <p class="flash erro">{{ session('erro') }}</p> @endif
        <div class="conteudo">
            @yield('transporte')
        </div>
    </main>
    <footer class="rodape-fixo">
        <div class="rodape">
            <span>© {{ date('Y') }} {{ $municipio }}. Todos os direitos reservados.</span>
            <span>Transporte escolar · i-Educar</span>
        </div>
    </footer>
    <script>
        (function () {
            var botao = document.querySelector('.hamburguer');
            var painel = document.getElementById('menu-transporte');
            if (!botao || !painel) {
                return;
            }
            botao.addEventListener('click', function () {
                var aberto = painel.classList.toggle('aberto');
                botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                botao.querySelector('i').className = aberto ? 'fa fa-times' : 'fa fa-bars';
            });
            painel.querySelectorAll('.tema-botao').forEach(function (tema) {
                tema.addEventListener('click', function () {
                    var secao = tema.closest('.tema');
                    var aberto = secao.classList.toggle('aberto');
                    tema.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                });
            });
        })();
        document.querySelectorAll('[data-filtro-tabela]').forEach(function (campo) {
            campo.addEventListener('input', function () {
                var painel = campo.closest('[data-painel]') || document;
                var termo = campo.value.trim().toLowerCase();
                painel.querySelectorAll('tbody tr').forEach(function (linha) {
                    if (linha.hasAttribute('data-vazio')) {
                        return;
                    }
                    var texto = (linha.getAttribute('data-busca') || linha.textContent || '').toLowerCase();
                    linha.hidden = termo !== '' && texto.indexOf(termo) === -1;
                });
            });
        });
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
                                destino.textContent = dados.aviso || 'Não foi possível calcular a distância.';
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
    </script>
</body>
</html>
