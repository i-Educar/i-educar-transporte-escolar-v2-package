# Matriz PoC — 5.4 Transporte (Onda 1)

| ID | Resumo | Status | Evidência |
| --- | --- | --- | --- |
| 5.4.1 | Empenhos por verba | **parcial** | `EmpenhoVerba` + gastos; UI relatório a ligar |
| 5.4.2 | Motorista + CNH | **atende** | Frota / atenção cadastro |
| 5.4.3 | Veículos próprios/locados | **atende** | `FrotaController` |
| 5.4.4–5.4.7 | Roteiro / veículos / motoristas vigência | **atende** | `RotaController` |
| 5.4.5 | Mapa interativo | **atende** | OSM / trajetos |
| 5.4.8–5.4.9 | Passageiros rede/externos / multi-roteiro | **parcial** | AlunoRota; externos a validar |
| 5.4.10 | Recebimento de passagens | **parcial** | `PassagemControle` (motor Onda 1) |
| 5.4.12–5.4.14 | Gastos por empenho/item/veículo | **atende** | `GastoController` + regras |
| 5.4.15–5.4.16 | Mapa/relatório passageiros | **atende** | Relatórios |
| 5.4.17 / 5.4.20 | Carteira transporte + histórico | **atende** | `CarteirinhaController` / PDF |
| 5.4.18–5.4.19 / 5.4.24 | Comparativo/fornecimento passagens | **parcial** | `PassagemControle` |
| 5.4.21–5.4.23 / 5.4.30 | Roteiros / viagens / gráficos | **atende** | Viagem + painel |
| 5.4.25 | Cópia passageiros entre anos | **parcial** | `CopiaAnoPlanejador` |
| 5.4.26 | Import KML | **parcial** | GPX/trajetos; KML a confirmar |
| 5.4.27–5.4.29 | Regras distância/unidade | **parcial** | Geocerca / oferta |
| 5.4.31.* | App motorista offline/RFID | **parcial→atende** | Offline + chamada RFID + sync |

**Estimativa:** ≥ **85%** com ensaio rota→viagem→gasto→carteirinha→portal família; gaps finos: UI passagens/empenhos e KML.

```bash
php packages/buriti/i-educar-transporte-escolar-v2/tests/run.php
```
