# Fluxos

A família não entra no menu do i-Educar. O institucional abre o processo de vagas. A escola encaminha. A rede aceita ou recusa.

## Processo de vagas

O processo vale para um ano letivo e um período, no mesmo espírito da pré-matrícula. Fora do período, ou sem vaga publicada, a família não envia pedido.

```mermaid
flowchart TD
    A["Institucional abre o processo"] --> B["Informa ano, início e fim"]
    B --> C["Publica escola, rota, turno e vagas"]
    C --> D{"Hoje está no período e o processo está ativo?"}
    D -->|não| E["A página Pedir avisa que não há processo aberto"]
    D -->|sim| F["A família vê ano, escola, rota e vagas livres"]
```

## Pedido da família

O protocolo sai no formato `TE-ano-sequência-dígito`. A consulta pede o protocolo e a data de nascimento. Data errada e protocolo inexistente devolvem a mesma frase.

```mermaid
flowchart TD
    A["Família abre /transporte-escolar/familia"] --> B["Escolhe processo, escola e rota com vaga"]
    B --> C["Informa aluno, nascimento, responsável, telefone e endereço de saída"]
    C --> D{"Ainda há vaga?"}
    D -->|não| E["A rota não recebe o pedido"]
    D -->|sim| F["Protocolo gravado, situação recebida"]
    F --> G["Página Acompanhar"]
    G --> H{"Protocolo e nascimento conferem?"}
    H -->|não| I["Não encontramos o pedido"]
    H -->|sim| J["Mostra a situação"]
```

Pedido recebido, em análise ou aceito ocupa a vaga. Recusado ou cancelado devolve o lugar.

## Análise

A escola só vê o pedido da própria escola e só encaminha. Aceitar ou recusar é da rede. Pedido já decidido não volta.

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
```

## Pedido aceito

A família vê documentos, horário, endereço e mapa só depois do aceite. O mapa depende de latitude e longitude gravadas pelo institucional. A prefeitura não pede senha nem dados por rede social. O andamento fica na página Acompanhar.

```mermaid
flowchart TD
    A["Pedido aceito"] --> B["Levar documentos ao setor de Transporte Escolar"]
    B --> C{"Endereço e coordenadas gravados?"}
    C -->|só endereço| D["Mostra o endereço"]
    C -->|endereço e coordenadas| E["Mostra endereço e mapa"]
    C -->|nada gravado| F["A lista e o endereço ainda não foram publicados"]
```
