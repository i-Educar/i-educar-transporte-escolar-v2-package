# O que esta versão entrega

Versão 1.8.0-beta. Fases 0 a 5, ondas 1 a 8, portal da família, processo de vagas, documentos em duas vias, aviso no navegador, frequência da viagem, painel com mapa e caixa de notificações. SMS, envio ao SiGPC e folha ficam de fora. O desenho de cada fluxo está em `docs/FLUXOS.md`. O rótulo beta marca a revisão visual e de fluxos ainda em conferência.

| Entrega | Onde está |
| --- | --- |
| Painel com indicadores em coluna, mapa das rotas e fila de atenção | `/transporte-escolar` |
| Viagem, embarque, falta no transporte e km do odômetro | `/transporte-escolar/viagens` |
| Fila no navegador e descarga em dia de semana | `/transporte-escolar/offline` |
| Custo do mês só com bilhete abastecido, ordem concluída e gasto do art. 13 | `/transporte-escolar/gastos` |
| Consumo real, previsto por rota e frota parada | `/transporte-escolar/relatorios` |
| Link de embarque e desembarque, sem aplicativo | `/transporte-escolar/avisos` |
| QR Code, km da trilha e coordenada da casa | Viagem e aluno na rota |
| Portal da família: pedir transporte e acompanhar o protocolo | `/transporte-escolar/familia` |
| Agrupamento, processo com edição, pausa e bloqueio, vaga por idade e, se a rede precisar, série ou turma do ano nas escolas da rota | `/transporte-escolar/solicitacoes` |
| Ficha do protocolo, distância até o ponto e mapa do endereço | Mesma tela e `/transporte-escolar/solicitacoes/{id}/mapa` |
| Rota: incluir parada, criar ponto no mapa e ajustar coordenada em passos separados | `/transporte-escolar/rotas/{id}` |
| Ordem e bilhete com protocolo, situação e PDF de duas vias na mesma folha | `/transporte-escolar/ordens` e `/transporte-escolar/bilhetes` |
| Aviso de embarque e desembarque no navegador, com modelo editável pela prefeitura | `/transporte-escolar/avisos` e `/transporte-escolar/avisos/modelos` |
| Alunos transportados, com matrícula, responsável e nome completo só no perfil institucional | `/transporte-escolar/alunos` |
| Pedido sem nome completo no PDF, com QR e página pública que pede dados antes do status | `/transporte-escolar/solicitacoes` |
| Percurso pelas ruas, com quilometragem e tempo gravados na rota | `/transporte-escolar/rotas/{id}` |
| Frequência impressa na viagem, com CPF abreviado e status de presença ou ausência | `/transporte-escolar/viagens` |
| Relatórios e leitura do conselho na mesma tela, com gráfico de km, gasto por fonte, cobertura rural, Censo, preventiva, disponibilidade e litros por 100 km | `/transporte-escolar/relatorios` |
| Autorização DETRAN ou CIRETRAN, checklist e curso do motorista. Viagem nova exige papel vigente | `/transporte-escolar/conformidade` |
| Estoque de pneu, filtro, óleo e pastilha, com baixa na ordem e contagem | `/transporte-escolar/estoque` |
| Veículo com os tipos do Censo nos modais rodoviário, aquaviário e ferroviário | `/transporte-escolar/veiculos` |
| Carga de teste com alunos do ano, rotas longas, avisos, notificações e informes | `php artisan transporte:simula` |
| Sino, lista e detalhe das notificações internas. A leitura tira o aviso da contagem; a fila segue até a pendência acabar | `/transporte-escolar/notificacoes` |

A cerca não barra o embarque. O responsável escolhe receber o aviso no navegador; não há SMS. No portal, a família acompanha o pedido na página Acompanhar. A prefeitura não pede senha nem dados por rede social. Quando o pedido é aceito, a página mostra os documentos e o endereço do setor, com mapa se o institucional gravou latitude e longitude. A janela periódica usa o dia letivo quando o calendário do ano existe. A descarga não reescreve aluno, rota nem veículo. O evento repetido é ignorado. A falta no transporte não entra no diário. O dossiê não envia o SiGPC. O pedido da família não cria pessoa nem matrícula. Aceitar o pedido não coloca o aluno na rota.
