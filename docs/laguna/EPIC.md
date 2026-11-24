# EPIC — Transporte Escolar v2 — aderência Laguna

| Campo | Valor |
| --- | --- |
| Pacote | `buriti/i-educar-transporte-escolar-v2` |
| TR Laguna | **5.4** |
| Prioridade | **P1** |
| Nota atual (executivo) | **A/B** |
| Fonte | `tmp/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md` |
| Regra de ouro | Pregão Eletrônico 77/2026 · Laguna/SC · PoC ≥ 85% por módulo · gaps ≤ 30 dias |
| Base atual | buriti/i-educar-transporte-escolar-v2 1.8.0-beta |

## Objetivo

Mapear item a item 5.4.x e fechar passagens/carteira/empenhos/passageiros externos se houver gap real.

## Padrão técnico (i-Educar 2.12)

| Item | Valor |
| --- | --- |
| Alvo | i-Educar **2.12** · PHP **≥ 8.3** · Illuminate **^11|^12|^13** (Laravel 13 no app) |
| Composer | `buriti/i-educar-transporte-escolar-v2` |
| Namespace | `iEducar\Packages\BuritiTransporteEscolar\` → `src/` |
| Compat | `extra.ieducar.compatible: ["2.12"]` · GPL-2.0-or-later · `type: library` |
| Schema / dados | transporte_escolar (já existente) |
| Rotas | prefixo `transporte-escolar`; SME com middleware `ieducar.*` + `auth`; API apps via Sanctum quando exposto |
| Tenancy | `PackageTenant::allows('buriti/i-educar-transporte-escolar-v2')` antes de migrations/menu |
| Plug-and-play | path em `packages/buriti/…`; manter em `ignore` até instalável |
| Referência | `packages/buriti/PADRAO-PACOTE-2.12.md` |

**Nota de arquitetura:** Pacote já alinhado ao 2.12; docs Laguna = gap analysis 5.4.x, não rescrever arquitetura.

## Cobertura 100% do TR

Este EPIC cobre **todos** os requisitos do módulo(s) 5.4 listados em [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (40 itens OCR).

- PoC: ≥ **85%** demonstráveis e operacionais.
- Até **15%** restante: produção em ≤ **30 dias**, sem ônus.
- Nenhum item do `REQUISITOS-TR.md` pode ficar fora do `ROADMAP.md` / `TODO.md` (direto ou por grupo rastreável).

## Épicos filhos

1. Matriz 5.4.x × telas atuais (rotas, mapa, gastos, frota, portal, viagens).
2. Passagens/carteirinha vs bilhete atual.
3. Empenhos por verba no formato Laguna.
4. Passageiros externos e relatórios faltantes.

## Critérios de aceite (PoC)

- [ ] Checklist [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) com **100%** dos itens classificados (atende / gap / N/A)
- [ ] Itens **atende** demonstráveis na PoC (≥ 85% do módulo 5.4)
- [ ] Itens **gap** ≤ 15% com plano ≤ 30 dias em produção, sem ônus
- [ ] Nenhum requisito do TR omitido da matriz


## Fora de escopo deste EPIC

- Orçamento comercial da proposta Laguna.
- Substituição do core i-Educar fora do domínio do módulo.
