# Transporte escolar v2

Gestão completa do transporte escolar municipal — rotas, frota, pontos, alunos, motoristas/monitores e carteirinhas — independente do pacote Portabilis.

Composer: `buriti/i-educar-transporte-escolar-v2`

## Para que serve

Dá à SME o controle operacional do transporte: desenho de rotas e itinerários, frota, vínculo de alunos, carteirinhas e notificações, com mapa e permissões por processo.

## Desenho de funções

| Função | Entrega |
| --- | --- |
| Rotas e itinerários | Pontos, ordem, escolas, GPX/percurso |
| Pontos e coordenadas | Paradas e georreferenciamento |
| Frota | Veículos e vínculos operacionais |
| Alunos no transporte | Busca e associação a rotas |
| Motoristas e monitores | Cadastro operacional e carteirinhas |
| Carteirinhas | Emissão para motoristas, monitores e alunos |
| Notificações | Avisos e pulso operacional |
| Painel | `/transporte-escolar` |

## Quem usa

- SME transporte
- Escolas
- Motoristas/monitores
- Famílias (status via app)

## Integrações

- Núcleo (aluno, escola)
- Apps API
- Exportação unificada

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** |
| **i-Diário** | Portabilis i-Diário (API / SSO do núcleo) |
| Pacote | `1.8.1` |

Declarado em `composer.json` → `extra.ieducar` / `extra.idiario`.

## Documentação

| Documento | Uso |
| --- | --- |
| [`EPIC.md`](./EPIC.md) | Escopo de produto, aceite e critérios |
| [`ROADMAP.md`](./ROADMAP.md) | Fases e entregas |
| [`TODO.md`](./TODO.md) | Backlog operacional |

---

Licença: **GPL-2.0-or-later** · Organização [i-Educar](https://github.com/i-Educar)
