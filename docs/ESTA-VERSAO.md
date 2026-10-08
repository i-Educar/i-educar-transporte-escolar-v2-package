# O que esta versão entrega

Versão 1.0. Fases 0 a 5 e ondas 1 a 8. SMS, envio ao SiGPC e folha ficam de fora.

| Entrega | Onde está |
| --- | --- |
| Viagem, embarque, falta no transporte e km do odômetro | `/transporte-escolar/viagens` |
| Fila no navegador e descarga em dia de semana | `/transporte-escolar/offline` |
| Custo do mês, encargo no salário e contrato medido pelas viagens | `/transporte-escolar/gastos` |
| Consumo real, previsto por rota e frota parada | `/transporte-escolar/relatorios` |
| Link de embarque e desembarque, sem aplicativo | `/transporte-escolar/avisos` |
| QR Code, km da trilha e coordenada da casa | Viagem e aluno na rota |
| Fila ainda no celular, por motorista e por dia | `/transporte-escolar/offline` |
| Dia letivo do calendário na janela da descarga | Mesma tela. Sem calendário no ano, vale dia de semana |
| Comprovante no dossiê e rateio do salário por aluno | Gastos e dossiê |
| Cerca do ponto, desligada até o município marcar | Tela de gastos |

A cerca não barra o embarque. O link da família não envia SMS. A janela periódica usa o dia letivo quando o calendário do ano existe. A descarga não reescreve aluno, rota nem veículo. O evento repetido é ignorado. A falta no transporte não entra no diário. O dossiê não envia o SiGPC.
