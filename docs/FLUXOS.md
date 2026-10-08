# Fluxos

A família não entra no menu do i-Educar. O institucional prepara a rede, abre o processo e publica a vaga. A escola encaminha o pedido da própria escola. A rede aceita ou recusa. O dia da rota, a frota e a conta ficam nas telas da equipe, na ordem do painel.

## Ordem do trabalho

O painel em `/transporte-escolar` segue esta ordem. Cada etapa tem uma cor. O ícone abre a tela; o nome aparece ao passar o mouse.

```mermaid
flowchart LR
    A["1 Preparar a rede"] --> B["2 Abrir o ano"]
    B --> C["3 Rodar o dia"]
    C --> D["4 Cuidar da frota"]
    D --> E["5 Prestar contas"]
```

| Etapa | Cor | O que a equipe faz |
| --- | --- | --- |
| 1 Preparar a rede | Azul | Empresas, veículos, motoristas, monitores, pontos, rotas e normas |
| 2 Abrir o ano | Azul claro | Cópia do ano, pedidos da família e alunos na rota |
| 3 Rodar o dia | Verde | Viagens, pacote offline e aviso à família |
| 4 Cuidar da frota | Âmbar | Ordens de serviço, bilhetes e gastos |
| 5 Prestar contas | Roxo | SETE, relatórios e CACS |

O card de informes usa a mesma lógica de cor: azul para pedido da família, âmbar para prazo de CNH ou documento, vermelho para lotação acima dos lugares e verde para ordem ou bilhete ainda aberto.

## Processo de vagas

O institucional agrupa os processos que a família vê juntos, abre cada processo no ano letivo e publica a vaga com escola, rota, turno, quantidade, idade em 31 de março, série e turma. Série ou turma em branco significa qualquer uma. O processo pausado sai da página da família. O mesmo botão retoma. Não há exclusão de processo nem de vaga. Se já existe pedido, o ano do processo e a escola, a rota e o turno da vaga permanecem. Se já existe pedido aceito, idade, série e turma também permanecem. As vagas não ficam abaixo dos pedidos que ainda ocupam lugar.

```mermaid
flowchart TD
    A["Agrupar processos do mesmo ano"] --> B["Abrir processo no período"]
    B --> C["Publicar vaga: escola, rota, turno, lugares, idade, série e turma"]
    C --> D{"Situação"}
    D -->|Aberto e no período| E["A família vê a vaga se ainda há lugar e a regra cabe"]
    D -->|Pausado| F["Some da família. O mesmo botão retoma"]
    B --> H["Editar nome, ano, período e agrupamento"]
```

Pedido recebido, em análise ou aceito ocupa a vaga. Recusado ou cancelado devolve o lugar. O grupo também pode ser pausado ou bloqueado: se o processo está num grupo, os dois precisam estar abertos e no período.

## Pedido da família

O endereço é `/transporte-escolar/familia`. O protocolo sai no formato `TE-ano-sequência-dígito`. A consulta pede o protocolo e a data de nascimento. Data errada e protocolo inexistente devolvem a mesma frase. A prefeitura não pede senha nem dados do aluno por rede social.

```mermaid
flowchart TD
    A["Família abre o portal"] --> B["Escolhe o agrupamento, se houver"]
    B --> C["Informa nascimento, série e turma"]
    C --> D{"Há oferta aberta que cabe na regra e ainda tem lugar?"}
    D -->|não| E["A página avisa que não há vaga"]
    D -->|sim| F["Informa aluno, responsável, telefone e endereço de saída"]
    F --> G["Protocolo gravado, situação recebida"]
    G --> H["Acompanhar: protocolo e nascimento"]
```

## Análise do pedido

Em `/transporte-escolar/solicitacoes`, cada linha tem Ver protocolo. A ficha mostra aluno, nascimento, responsável, telefone, endereço, escola, turno, processo, rota, série e turma antes de aceitar ou recusar. A observação da decisão aparece para a família na página Acompanhar.

Depois do endereço, a ficha calcula a distância até os pontos com coordenada. O mapa detalhado abre em outra aba. A consulta ao mapa usa só o texto do endereço. Nome, telefone e nascimento ficam no i-Educar.

```mermaid
flowchart TD
    A["Recebida"] --> B["Escola ou rede encaminha"]
    B --> C["Em análise"]
    A --> D["Família cancela"]
    C --> D
    A --> E["Rede aceita"]
    C --> E
    A --> F["Rede recusa"]
    C --> F
    E --> G["Aceita"]
    F --> H["Recusada"]
    D --> I["Cancelada"]
    G --> J["Família vê o setor e leva os documentos"]
```

Pedido já decidido não volta. Aceitar não cria pessoa, matrícula nem aluno na rota. A secretaria inclui o aluno em Alunos na rota.

## Pedido aceito

A família vê documentos, horário, endereço e mapa só depois do aceite, e só com o que o institucional gravou em Pedidos da família. O mapa exige latitude e longitude válidas.

## Rota

Em `/transporte-escolar/rotas/{id}` há três ações diferentes. O clique no mapa preenche só o formulário que estiver escutando.

```mermaid
flowchart TD
    A["Abrir a rota"] --> B["1 Incluir parada"]
    A --> C["2 Criar ponto e colocar nesta rota"]
    A --> D["3 Ajustar coordenada de um ponto desta rota"]
    B --> E["Usa um ponto que já existe. Não grava coordenada"]
    C --> F["O clique preenche o ponto novo e a parada entra no itinerário"]
    D --> G["O clique só entra depois de escolher Usar o clique neste ajuste"]
    A --> H["Escola e GPX ficam à parte"]
    H --> I["O clique do mapa não altera a escola"]
```

Coordenada fora do território brasileiro não entra no mapa. A escola com latitude ou longitude inválida fica fora do enquadramento até ser corrigida. Retirar uma parada refaz a ordem das demais.

## Cobertura no mapa da rota

O mapa mostra os pontos da rota, o raio de cada um, um raio de 500 m em volta da escola e a concentração das residências. Nome, endereço e data de nascimento não aparecem. A faixa de idade e a mancha de residências só entram com pelo menos três registros. Em cada ponto a equipe vê a quantidade de alunos, de embarques e de desembarques.

## Ordem de serviço e bilhete

O pedido nasce em Ordens ou em Bilhetes, com protocolo. A impressão é sempre duas vias na mesma folha: via 1, arquivo, em cima; via 2, canhoto, embaixo; linha tracejada com tesoura no meio. Cabeçalho e rodapé são curtos e trazem o município, o título, o protocolo e a via.

```mermaid
flowchart TD
    A["Ordem solicitada"] --> B["Autorizada"]
    B --> C["Em execução"]
    C --> D["Concluída"]
    A --> E["Cancelada"]
    B --> E
    C --> E
    F["Bilhete solicitado"] --> G["Autorizado"]
    G --> H["Abastecido"]
    F --> I["Cancelado"]
    G --> I
    D --> J["Entra no custo do mês"]
    H --> J
```

Ordem que não está concluída e bilhete que não está abastecido não entram no custo. O PDF não substitui nota fiscal nem o extrato da conta do PNATE.

## Gastos do mês

A tela `/transporte-escolar/gastos` abre pelo mês. Os totais separam PNATE, recurso próprio e outras fontes. A composição soma o bilhete abastecido, a ordem concluída, o gasto do art. 13, o contrato medido pelas viagens e o salário rateado em recurso próprio. Combustível e serviço não se lançam nesta tela: nascem em Bilhetes e Ordens. Aqui entram o gasto do art. 13, o contrato, o plano preventivo, a parcela informada e as regras da cerca, do transporte aquaviário e do rateio.

## Frequência da viagem

A tela Frequência lista cada viagem do mês e emite o PDF da chamada. Presente só aparece quando houve embarque. Sem marcação, a linha é falta. No mês, o aluno é frequente se embarcou ao menos uma vez na rota; se todas as viagens ficaram sem marcação, a situação é não frequente. O perfil da escola vê apenas as rotas das escolas que ele acessa. O institucional vê a rede.

## Chamada pelo aplicativo

O aplicativo ainda não existe. A API que ele vai usar já recebe a marcação na hora e a fila gravada no aparelho quando a rede volta. O dispositivo se identifica pelo token, no cabeçalho `Authorization: Bearer` ou `X-Transporte-Token`, e precisa estar ligado a um motorista ou a um monitor.

- `GET /transporte-escolar/api/chamada?data=AAAA-MM-DD` devolve as viagens daquele dia, com aluno, cartão, QR e a marca atual.
- `POST /transporte-escolar/api/chamada` recebe `eventos`. Cada evento traz `uuid`, `viagem_id`, `tipo` (`embarque`, `falta` ou `desembarque`), `origem` (`motorista`, `monitor` ou `rfid`) e `aluno_id` ou `cartao`.
- O mesmo `uuid` enviado de novo não grava outra presença. A falta apaga o embarque. O embarque é a presença.

## O que estes fluxos ainda não fazem

- Aceitar o pedido não coloca o aluno na rota.
- Não há limite de um pedido por aluno.
- Não há fila de espera com prioridade. Sem lugar, o pedido não entra.
- Não há envio de SMS nem notificação de celular.
- O módulo não envia a prestação ao SiGPC e não gera folha de pagamento.
