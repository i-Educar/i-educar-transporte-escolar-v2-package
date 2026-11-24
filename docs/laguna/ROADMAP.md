# ROADMAP — Transporte Escolar v2 — aderência Laguna

> Pacote: `buriti/i-educar-transporte-escolar-v2` · TR **5.4** · Prioridade **P1** · Nota **A/B**  
> Alinhado a `tmp/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`

## Visão

Mapear item a item 5.4.x e fechar passagens/carteira/empenhos/passageiros externos se houver gap real.

## Fases

### F0 · Matriz

Checklist binário 5.4.x.

### F1 · Gaps críticos

Itens que impedem 85%.

### F2 · Empenhos/passagens

Se confirmado gap.

### F3 · PoC

Demo transporte.

## Cobertura 100% dos requisitos (TR)

Fonte: [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) — **40 itens**.

Cada lote deve sair da PoC como **atende** ou entrar no plano de 30 dias.

### Lote R1 — itens 5.4.1 … 5.4.16 (15)

- `5.4.1`, `5.4.2`, `5.4.3`, `5.4.4`, `5.4.5`, `5.4.6`, `5.4.7`, `5.4.8`, `5.4.9`, `5.4.10`, `5.4.12`, `5.4.13`, `5.4.14`, `5.4.15`, `5.4.16`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R2 — itens 5.4.17 … 5.4.31 (15)

- `5.4.17`, `5.4.18`, `5.4.19`, `5.4.20`, `5.4.21`, `5.4.22`, `5.4.23`, `5.4.24`, `5.4.25`, `5.4.26`, `5.4.27`, `5.4.28`, `5.4.29`, `5.4.30`, `5.4.31`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R3 — itens 5.4.31.1 … 5.4.31.10 (10)

- `5.4.31.1`, `5.4.31.2`, `5.4.31.3`, `5.4.31.4`, `5.4.31.5`, `5.4.31.6`, `5.4.31.7`, `5.4.31.8`, `5.4.31.9`, `5.4.31.10`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Gate 100%

- [ ] Todos os lotes R* com status atende ou gap≤30dias
- [ ] Soma atende ≥ 85% na demonstração

## Conformidade Laravel / pacote i-Educar 2.12

Pacote **já existente** e declarado compatível com 2.12. Esta pasta Laguna não redefine o scaffold — apenas o checklist TR. Detalhes de instalação: README do pacote. Padrão geral: `packages/buriti/PADRAO-PACOTE-2.12.md`.

## Dependências

- Cadastro mestre de aluno/escola/turma no i-Educar (SGE).
- Matriz item a item OCR do Anexo I antes de cravar %.
- Alinhamento com apps (5.13/5.14) e exports (4.x) quando o módulo expõe dados ao cidadão/SME.

## Riscos

- Valor global do edital incompatível com greenfield total — reuso e MVP demonstrável são obrigatórios.
- PDF escaneado: numerações OCR podem exigir reconciliação manual.
- PoC só conta o que estiver operacional no dia.

## Definição de pronto da fase PoC

Checklist do `TODO.md` marcado para a fatia MVP + roteiro de demonstração ensaiado.
