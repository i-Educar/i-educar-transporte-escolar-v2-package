# Permissões por perfil

O i-Educar separa o tipo de usuário pelo campo `pmieducar.tipo_usuario.nivel`:

| Nível | Perfil | O que vê no transporte |
| --- | --- | --- |
| 1 | Poli-institucional | O núcleo já libera todos os menus. A migration também marca visualizar, cadastrar e excluir |
| 2 | Institucional | A rede inteira. Grava empresa, frota, rota, mapa, cópia de ano e SETE |
| 4 | Escola | Só escolas de `pmieducar.escola_usuario`. Consulta frota, rota, mapa, normas e divergências da própria escola. Grava e exclui apenas aluno da matrícula dessas escolas |
| 8 | Biblioteca | Nenhum processo deste menu |

A tela Tipos de usuário continua podendo apertar ou afrouxar cada processo. A migration só deixa o padrão. Os números abaixo são a coluna `menus.process`.

## Fase 0 — cadastro que já existe e vínculo do aluno

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880120 | Alunos na rota | Rede, grava e exclui | Só a matrícula das suas escolas, grava e exclui |
| 880122 | Cópia do ano | Grava | Sem menu |
| 880111 | Motoristas (alerta de CNH no painel) | Grava | Consulta |
| 880113 | Veículos (alerta de licença no painel) | Grava | Consulta |

O painel (880100) mostra os alertas. A escola vê a lotação só das rotas que passam nas suas escolas.

## Fase 1 — mapa

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880121 | Mapa da rota | Consulta | Consulta |
| 880115 | Gravar ponto e coordenada, importar GPX | Grava | Consulta |
| 880116 | Alterar rota, itinerário e ordem sugerida | Grava | Consulta das rotas das suas escolas |

A coordenada da escola é gravada em `pmieducar.escola`, só por quem tem permissão de gravar rota.

## Onda 1 — SETE

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880123 | Importar e exportar | Grava e exporta | Sem menu |
| 880124 | Divergências | A rede | Linhas cuja planilha cita o nome das suas escolas |

## Onda 2 — paridade de cadastro

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880110 | Empresas | Grava | Consulta |
| 880112 | Monitores e salário | Grava | Consulta |
| 880114 | Fornecedores | Grava | Consulta |
| 880130 | Normas | Consulta | Consulta |

Salário não é folha e não é despesa do PNATE. Os dois perfis que consultam a frota enxergam o valor porque ele compõe o custo da rota da rede. Quem não deve ver salário precisa ter o processo de motorista ou monitor desmarcado em Tipos de usuário.

## Fase 2 — dia da rota

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880140 | Viagens, embarque e falta no transporte | Grava | Grava nas rotas das suas escolas. Não exclui a viagem |

A escola não vê viagem de rota que não passa por ela. O código `TE-` mais o id do aluno é o atalho do QR Code.

## Onda 3 — offline

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880141 | Pacote offline e descarga da fila | Grava e vê aparelhos atrasados | Grava só o pacote das suas rotas. Não vê a lista de aparelhos da rede |

## Fase 3 — dinheiro

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880150 | Gastos, manutenção, contrato e parcela | Grava | Sem menu |

## Fase 4 — relatórios e conselho

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880151 | Relatórios e planilha | Consulta a rede, inclusive o custo | Consulta só as suas rotas, sem o bloco de custo |
| 880152 | Painel do CACS | Só leitura | Sem menu |

O dossiê mensal usa a mesma permissão de gastos, não a de relatório.

## Onda 7 — medição

Não há processo novo. Encargo fica no cadastro de motorista e monitor. Consumo, rota e frota parada ficam em Relatórios. Licença vencida aparece em Gastos, que a escola não abre.

## Onda 8 — família e cerca

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880160 | Lista de avisos e link | Consulta a rede | Consulta as rotas das suas escolas |

A página pública do link não pede login e não entra no menu. Ligar a cerca é gravação de Gastos, só do institucional.

## Portal da família

| Processo | Tela | Institucional | Escola |
| --- | --- | --- | --- |
| 880161 | Pedidos da família | Vê a rede, encaminha, aceita e recusa | Vê e encaminha só o pedido com escola das suas escolas. Não aceita nem recusa |

A página `/transporte-escolar/familia` não pede login e não entra no menu. Quem pede informa o aluno, o endereço de saída, o turno e a escola, e recebe um protocolo `TE-ano-sequência-dígito`. A consulta seguinte exige o protocolo e a data de nascimento. Data errada e protocolo inexistente devolvem a mesma frase, para não revelar se o pedido existe. A família cancela enquanto o pedido está recebido ou em análise. Pedido aceito ou recusado não volta. O pedido não cria pessoa, matrícula nem aluno na rota.

O perfil institucional grava, em Pedidos da família, o nome do setor, o endereço, o horário, a lista de documentos e a latitude e longitude. A família só vê esse bloco quando o pedido está aceito. Com as duas coordenadas, a página mostra o mapa.

O mesmo perfil abre o processo de vagas: nome, ano letivo e período. Em seguida publica vagas por escola e rota desse ano. A família só escolhe uma combinação publicada e com lugar. Pedido recebido, em análise ou aceito ocupa a vaga. Recusado ou cancelado devolve o lugar.
