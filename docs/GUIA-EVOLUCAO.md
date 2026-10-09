# Guia de evolução — Transporte escolar no i-Educar

> Este arquivo está no pacote v2. O começo descreve o pacote da Portábilis, que este repositório não usa. A entrega atual está em `ESTA-VERSAO.md` e `FLUXOS.md`. O v2 grava no schema `transporte_escolar` e não nas tabelas `modules` daquele pacote.

## Decisão

O texto abaixo registra a leitura do pacote da Portábilis. A decisão que este repositório executa é outra: o v2 não depende daquelas tabelas `modules`.

Evoluir o pacote atual. Ele já é o lugar onde o aluno do i-Educar se liga a empresa, rota, ponto e veículo, e o menu já respeita o multi-tenant (`PackageTenant`).

Ter o mesmo alcance funcional do SETE dentro do i-Educar, e importar a base que o município já lançou lá. A Resolução CD/FNDE nº 18/2021, alterada pela nº 5/2024, ainda exige o SETE na operação paga com PNATE e usa esse sistema na redistribuição de recurso. Enquanto o FNDE não abrir API, o i-Educar importa e reexporta. A prestação financeira oficial continua no SiGPC.

Não começar um pacote novo do zero. O que falta (mapa, embarque, custo, manutenção) se encaixa nas tabelas que já existem. Um segundo cadastro de rota só duplicaria o que a secretaria já tem dificuldade de manter.

## O que o pacote faz hoje

É um cadastro da intranet antiga do i-Educar. Não há tela em Laravel, não há API REST e não há mapa. O `TransportServiceProvider` só publica as migrations no console quando o pacote é permitido no tenant, e só resolve as URLs legadas na requisição do mesmo jeito.

Menu **Transporte escolar**:

| Tela | O que grava |
| --- | --- |
| Empresas | Pessoa jurídica e responsável (`modules.empresa_transporte_escolar`) |
| Motoristas | Pessoa, CNH, categoria, datas de habilitação e vencimento, empresa (`modules.motorista`) |
| Veículos | Descrição, placa, RENAVAM, chassi, marca, anos, lotação, malha, tipo, exclusivo escolar, adaptado para deficiência, ativo, empresa, motorista e data de licença (`modules.veiculo`) |
| Pontos | Nome, endereço do cadastro antigo, latitude e longitude em texto (`modules.ponto_transporte_escolar`) |
| Rotas | Escola de destino (pessoa), descrição, ano, urbana ou rural, km pavimentado e não pavimentado, empresa, se é terceirizada (`modules.rota_transporte_escolar`) |
| Itinerário | Ordem dos pontos da rota, veículo opcional, hora e tipo (`modules.itinerario_transporte_escolar`) |
| Usuários de transporte | Pessoa (não só aluno), rota, ponto, destino e turno (`modules.pessoa_transporte`) |
| Cópia de rotas | Copia rotas e itinerários de uma empresa de um ano para outro. Não copia os usuários |
| Vínculo curto do aluno | `modules.transporte_aluno`: aluno, responsável e quem gravou |

Tipos de veículo semeados no pacote misturam a lista do Educacenso (van, micro-ônibus, ônibus, bicicleta, tração animal, trem, outro e faixas de capacidade). No aluno do núcleo, `veiculo_transporte_escolar` é um array desses códigos, e a ficha tem `tipo_transporte`. Esse dado do Censo e o usuário da rota não são a mesma coisa e o pacote não os mantém alinhados.

Três relatórios (motoristas, usuários, rotas) e o relatório detalhado de rotas estão no pacote de relatórios, não neste. Sem o pacote de relatórios, o menu aponta para telas que não existem.

## Como é feito

- Tabelas no schema `modules`, com sequence própria e SQL cru nas migrations.
- Telas `intranet/transporte_*.php` e controllers `TransporteEscolar` / `Api` no estilo Portábilis (formulário legado, busca de pessoa e de empresa).
- O itinerário é uma lista ordenada de pontos, montada em JavaScript (`transporte-itinerario-cad.js`). Não desenha trajeto.
- Latitude e longitude são campos de texto no formulário do ponto. Não há Leaflet, Google nem OpenStreetMap.
- A cópia anual grava com SQL concatenado e casa a rota nova com a antiga pela ordem da lista. Se a ordem mudar, o itinerário vai para a rota errada.
- O processo de permissão da rota no controller (`21238`) não é o mesmo id de menu da rota (`21239`). Vale conferir na base antes de mexer em perfil.

## Lacunas

### Em relação à operação da prefeitura

- Não há mapa, traçado, arquivo GPX nem cálculo de km a partir dos pontos. O km da rota é digitado.
- Não há embarque, desembarque, falta no transporte nem km rodado no dia.
- Não há monitor, turno de ida e volta como viagem, nem tempo de casa até a escola.
- Não há alerta de CNH vencida nem de licença do veículo, embora as datas existam.
- Não há abastecimento, ordem de serviço, pneu, seguro, IPVA nem contrato terceirizado.
- Não há embarcação, passe estudantil nem centro de custo (municipal, estadual, federal).
- A cópia de ano não leva os alunos. A secretaria recadastra usuário todo ano ou esquece.
- Ponto sem geometria obrigatória: dá para operar o módulo inteiro sem coordenada.
- Usuário de transporte pode ser qualquer pessoa, sem exigir matrícula ativa.

### Em relação à lei e ao FNDE

O pacote não produz o que a prestação de contas e o monitoramento pedem:

- Categorias de despesa do art. 13 da Resolução 18/2021.
- Evidência de que o veículo está licenciado e de que o condutor cumpre os arts. 136 a 139 do Código de Trânsito (autorização para transporte escolar, identificação, equipamentos).
- Distância e tempo do percurso, que a própria resolução manda otimizar.
- Acesso do CACS/Fundeb à operação (a resolução manda garantir acesso ao SETE; um complemento local também precisa de perfil só de leitura para o conselho).
- Arquivo ou relatório no formato que a equipe usa para lançar o SiGPC. O sistema de contas continua sendo o do FNDE. O pacote não deve “enviar a prestação” no lugar da prefeitura.

### Em relação ao código

- CRUD legado, difícil de testar e de colocar mapa.
- Relatórios fora do pacote.
- Sem modelo Eloquent e sem teste automatizado neste repositório.
- Coordenada em `varchar`, não em tipo geográfico.

## Legislação que o módulo precisa respeitar

Consulta em 8 de outubro de 2026. Norma muda; antes de implementar prestação de contas, reler o texto no portal do FNDE.

| Norma | Efeito no produto |
| --- | --- |
| Constituição, art. 208, VII, e LDB arts. 4º, 10 e 11 | Transporte é dever da rede para garantir o acesso. O aluno beneficiário nasce da matrícula, não de uma planilha paralela |
| Lei 10.880/2004 | Cria o PNATE. Repasse automático, conta específica |
| Resolução CD/FNDE 18/2021 | Regra principal de execução, frota, despesa e contas |
| Resolução CD/FNDE 5/2024 | Dá nome ao SETE no § 1º do art. 13 e diz que ele subsidia a redistribuição de recurso (art. 11) |
| Art. 5º e art. 7º da Res. 18/2021 | Beneficiário do cálculo federal: aluno da educação básica pública, área rural, que usa transporte, no Censo do ano anterior. O dinheiro vai para a rede onde ele está matriculado |
| Art. 8º | Norma prevê dez parcelas, de fevereiro a novembro, em conta específica. Saldo não entra de outra conta (vedado depositar recurso próprio na conta do PNATE) |
| Art. 9º | Saldo em 31/12 reprograma. O que passar de 30% do repassado no ano pode ser descontado no ano seguinte |
| Art. 13 | Só paga: manutenção de veículo próprio (reforma, seguro, licenciamento, impostos e taxas do ano, pneu, peça, mecânica, combustível e lubrificante); o equivalente em embarcação regularizada na Capitania; serviço terceirizado; passe estudantil quando existe transporte coletivo |
| Art. 13, §§ 4º a 6º, e CTB arts. 136 a 139 | Veículo e condutor no padrão de transporte escolar. Vedado pagar tarifa bancária, multa, pessoal e tributo que não incida sobre o material ou serviço do programa |
| Art. 14 | Uso principal: aluno da educação básica pública da área rural, casa–escola–casa e atividade pedagógica. Aluno urbano pode ir se não prejudicar o rural. Outra pessoa, só monitor ou auxiliar |
| Art. 15 | Monitor, quando houver, é custeado com recurso próprio da prefeitura, não com PNATE |
| Art. 17 | Compra com recurso do PNATE em pregão eletrônico ou dispensa eletrônica, salvo justificativa de inviabilidade |
| Arts. 21 a 22 e 25 a 29 | CACS/Fundeb acompanha e dá parecer no Sigecon. A prestação vai ao SiGPC |
| Art. 23 | Ideges-PNATE mede a gestão. Nota técnica do FNDE detalha o cálculo. Não inventar o índice dentro do i-Educar |
| Lei 14.133/2021 | Licitação dos contratos terceirizados e das compras |
| Lei 13.146/2015 | Veículo mantido com PNATE precisa de condição de atendimento ao aluno com deficiência |
| Normas da autoridade marítima | Só entram se o município tem embarcação escolar |
| Programa Caminho da Escola (Decreto 12.625/2025 altera o Decreto 11.162/2022; Resolução CD/FNDE 14/2025 altera a Resolução 1/2021) | Compra de veículo padronizado. É outro programa. O PNATE, na prática da resolução, deve priorizar a manutenção desses veículos |
| Prazos de contas (portal MEC Normas, consulta em 08/10/2026) | Exercícios 2023 a 2026: prestação do PNATE até 28/02/2027 e parecer do CACS/Fundeb até 30/04/2027. A partir de 2027, 28/02 e 30/04 do ano seguinte |
| SiGPC Ágil (notícia FNDE) | A partir de 15/09/2026 passa a ser o ambiente exclusivo das funções já liberadas, no lugar do BB Gestão Ágil, inclusive para o PNATE. Exportar do i-Educar um dossiê; não integrar “envio oficial” enquanto o FNDE não publicar API |

## O que os outros sistemas fazem

| Sistema | Para quem | Mapa e rota | Operação | Custo e frota | O que aproveitar | O que não copiar |
| --- | --- | --- | --- | --- | --- | --- |
| **SETE** (FNDE e Cecate/UFG, licença MIT, web e desktop) | Prefeitura e estado, obrigatório no PNATE | Ponto no mapa, desenho, importação GPX, sugestão de rota no desktop, malha viária | Cadastro de aluno, escola, rota, frota, motorista, monitor e ponto. Aplicativo móvel espelha o que foi cadastrado; não é rastreador ao vivo | Estimativa de IPVA, seguro, consumo, pneu, salário de motorista e monitor | Georreferência, GPX, km e tempo estimados, indicador, separação ida/volta como rotas | Substituí-lo. A importação do Censo novo (2025) ainda era limitada no material de capacitação do FNDE |
| **Via Escolar** | Rede pública | Trajeto em tempo real, inclusive offline | Embarque por cartão RFID 13,56 MHz com hora e coordenada | Manutenção com agenda e alerta | Registro offline de embarque com posição | Exigir hardware RFID na primeira versão. Muitos municípios rurais não compram leitor |
| **Sister** | Operação com centro de custo | Roteirização e recálculo quando o aluno falta | Aplicativo do motorista offline, QR Code de veículo e de aluno, previsto contra realizado | Custo por rede (municipal, estadual, federal) | QR Code, viagem sem o aluno faltante, comparativo previsto/realizado | Tabela comercial de central de monitoramento. O i-Educar não deve nascer como produto cobrado por aluno |
| **Rota Escolar** | Empresa de van e ônibus | Mapa ao vivo, velocidade, desvio, geocerca | Lista de embarcados e ausentes, aviso aos responsáveis, GPS só com a rota em execução para poupar bateria | Painel da empresa, não prestação FNDE | Aviso à família e geocerca como módulo opcional | Foco em cliente particular e marca da empresa. Não resolve SiGPC nem Censo |

Inovação que cabe na rede municipal, nesta ordem:

1. Mapa livre (OpenStreetMap), sem chave paga, com o ponto clicado no mapa em vez de latitude digitada.
2. Trajeto desenhado ou GPX, e o km calculado ao lado do km digitado.
3. Celular do motorista que funciona sem sinal e sincroniza depois. Zona rural perde rede no meio da rota.
4. QR Code impresso na secretaria antes de cartão RFID.
5. Recálculo simples: aluno faltante sai da lista do dia; o km previsto daquele dia cai. Otimizador pesado fica para depois, e o desktop do SETE já sugere rota.
6. Família vê “embarcou / desembarcou” quando houver internet. Não prometer mapa ao vivo como requisito do PNATE.

## Direção do produto

O i-Educar é a fonte da matrícula, da escola, do turno, da zona rural e do Educacenso. O pacote de transporte é a fonte da operação local (quem vai em qual rota, o que aconteceu no dia, quanto se gastou em cada categoria do art. 13). O SETE é a fonte que o FNDE olha. O SiGPC é a fonte da prestação financeira.

```text
matrícula (i-Educar)
    → usuário da rota (pacote)
        → viagem do dia (pacote, fase 2)
            → despesa por categoria (pacote, fase 3)
                → dossiê para a equipe lançar no SiGPC (fase 4)
                → exportação para o SETE (fase 4)
```

Regra de ouro: a matrícula nasce no i-Educar. Sem matrícula ativa no ano, a conferência ainda pode deixar o aluno na rota, com aviso, mas o vínculo em `aluno_rota` e o Censo só são gravados quando essa matrícula existe. O campo do Censo (`tipo_transporte` e `veiculo_transporte_escolar`) é preenchido a partir da rota, para o Censo não divergir do ônibus.

## To-do

O que está marcado já está no pacote v2. O que está aberto ainda não está no código. A ordem de execução foi: importar o SETE, completar o cadastro e o mapa, preparar o offline, operar o dia da rota, fechar relatórios e só então dinheiro (recurso, salário, manutenção, custo e gasto). Não abrir gasto sem km realizado.

Salário de motorista e de monitor entra no custo por km e por aluno, como o SETE faz. Não entra como despesa PNATE: o art. 13 da Resolução 18/2021 veta pessoal. A fonte desse lançamento é recurso próprio da prefeitura. O módulo não gera folha nem contracheque.

### Onda 1 — Importar e migrar o SETE

- [x] Mapear ficha a ficha do SETE para as tabelas do pacote: escola, aluno, rota, traçado, ponto, veículo, motorista, monitor, fornecedor, custo e norma.
- [x] Importar planilha Excel que o SETE já exporta (alunos, rotas, frota, motoristas, monitores), com linha de erro e sem duplicar na segunda carga.
- [x] Importar GPX de rota e gravar o traçado junto do itinerário de pontos.
- [x] Chave natural estável (CPF, código INEP da escola, placa, nome da rota + ano) para a mesma carga poder rodar de novo.
- [x] Relatório de divergência: aluno no SETE sem matrícula no i-Educar, rota sem escola, veículo sem placa, ponto sem coordenada.
- [x] Reexportar CSV no formato das fichas do SETE para o município atualizar o sistema do FNDE enquanto a API não existir.
- [x] Guardar a origem do registro (`sete` ou `ieducar`) e a data da última importação.

### Onda 2 — Paridade com os recursos do SETE

- [x] Escola no mapa (latitude e longitude no cadastro que o i-Educar já tem).
- [x] Aluno transportado com zona, escola, etapa, turno, turma, rota, ponto e coordenada da residência.
- [x] Motorista com os dados do SETE: identificação, vínculo, turno, CNH e salário mensal.
- [x] Monitor com identificação, turno, rotas e salário mensal em recurso próprio.
- [x] Fornecedor de peça, combustível e oficina, ligado à empresa que já existe.
- [x] Frota com IPVA, seguro, consumo médio, pneus, licenciamento, lotação, adaptado para deficiência e marca de veículo do Caminho da Escola.
- [x] Ponto de parada criado pelo clique no mapa (OpenStreetMap), sem latitude digitada.
- [x] Rota com tipo, período, km, tempo estimado, escolas atendidas, alunos e sentido. Ida e volta podem ser duas rotas, como no SETE.
- [x] Desenho do traçado no mapa, importação GPX, malha da rota e reaproveitamento de um ano para o outro levando também os alunos com matrícula ativa.
- [x] Sugestão de ordem dos pontos a partir do traçado importado do desktop do SETE. Não reescrever o roteirizador enquanto essa sugestão puder ser importada.
- [x] Lista local das normas (CTB, PNATE, Caminho da Escola) só como referência de leitura no módulo, sem copiar o texto integral da lei.
- [x] Alerta de CNH, licença, seguro e vistoria a vencer em 30 dias.
- [x] Usuário da rota exige matrícula ativa no ano. Os campos de transporte do Educacenso saem dessa rota.
- [x] Cópia anual casa o itinerário pelo código da rota de origem, não pela posição da lista.

### Onda 3 — Offline e descarga periódica

- [x] Pacote offline do motorista: suas rotas do dia, alunos, pontos, veículo, QR Code e última versão baixada.
- [x] Fila local de embarque, desembarque, falta no transporte, odômetro, abastecimento e ocorrência. Cada item tem identificador do aparelho e não se perde se a rede cair.
- [x] Descarga periódica: ao abrir com rede, e perto de 05:00 e 12:30 em dia letivo do calendário. Sem calendário no ano, vale dia de semana.
- [x] Conflito: cadastro mestre da secretaria vence no aluno, na rota e no veículo. Evento de viagem que já subiu não é apagado por uma descarga.
- [x] O dia inteiro sem sinal continua operando. A tela mostra a hora da última descarga bem-sucedida.
- [x] Secretaria vê o que ainda não subiu, por motorista e por dia, enquanto a fila estiver no celular.

### Onda 4 — Operação do dia

- [x] Viagem: data, rota, veículo, motorista, monitor, sentido.
- [x] Embarque na lista, com QR Code do texto TE- e o id do aluno. Hora e coordenada quando o aparelho tiver GPS.
- [x] Falta no transporte separada da falta na aula.
- [x] Km realizado pelo odômetro e, quando há GPS, pela trilha. Os dois ficam separados do km planejado.
- [x] Aviso à família (embarcou, desembarcou) por link, sem loja de aplicativos e sem SMS.
- [x] Geocerca do ponto como opção do município. Desligada, o embarque segue normal.

### Onda 5 — Relatórios

- [x] Os três relatórios atuais (motoristas, usuários, rotas) passam a funcionar dentro do pacote de transporte, sem depender do pacote de relatórios.
- [x] Igual ao SETE: menor, média e maior quilometragem e tempo, em planilha. Ordem de serviço e bilhete de abastecimento saem em PDF de duas vias na mesma folha. Os demais relatórios continuam em planilha ou na impressão da página.
- [x] Previsto contra realizado por rota no ano: km, alunos da lista e alunos que embarcaram. O recorte por dia não está na tela.
- [x] Custo por aluno, por km, por rota e por veículo, com salário rateado, combustível, manutenção, seguro, IPVA e contrato.
- [x] Gasto por fonte: PNATE, recurso próprio, estadual e outro.
- [x] Vencimentos (CNH, licença, seguro, documentação) e frota parada.
- [x] Painel só leitura do CACS/Fundeb: rurais transportados, frota, vencimentos, km e gasto por categoria. Não calcular o Ideges.
- [x] Dossiê mensal para quem lança o SiGPC Ágil: gastos físicos e comprovantes. Empenho continua no sistema de contabilidade do município.
- [x] Conferência Censo: ficha com transporte e sem rota, e o inverso.

### Onda 6 — Recursos, salários, manutenção, custos e gastos

- [x] Fonte de recurso em todo lançamento: PNATE, recurso próprio, estadual, convênio ou outro.
- [x] Registro local das parcelas e do saldo informado (não substitui o extrato do banco). Aviso se o saldo lançado em 31/12 passar de 30% do repasse do ano, que é o corte do art. 9º.
- [x] Salário mensal de motorista e de monitor, com encargo percentual opcional e carga horária. Rateio pelo km ou pelos alunos embarcados, em recurso próprio. Sem recibo.
- [x] Plano de manutenção preventiva por km ou por data (óleo, filtro, pneu, freio, revisão).
- [x] Ordem de serviço corretiva: veículo, oficina ou fornecedor, peça, mão de obra, km e nota.
- [x] Abastecimento: litros, valor, km, veículo e fornecedor. O relatório compara km por litro com o consumo médio cadastrado.
- [x] Gastos nas categorias do art. 13: manutenção rodoviária, manutenção de embarcação, terceirizado e passe estudantil. Seguro, IPVA, licenciamento e taxa do ano entram na manutenção.
- [x] Impedir classificar como PNATE: multa, tarifa bancária, salário, encargo e tributo que não incida sobre o material ou serviço do programa.
- [x] Contrato terceirizado medido pelos dias de semana em que a empresa teve viagem. Sem viagem, o mês fica em zero.
- [x] Custo do mês = combustível + manutenção + seguro + IPVA + licenciamento + terceirizado + passe + salário rateado. Mostrar o que é PNATE e o que é recurso próprio lado a lado.
- [x] Embarcação só aparece se o município marcar que tem transporte aquaviário.


### Onda 7 — Medição do custo

- [x] Encargo percentual opcional no salário de motorista e de monitor, somado ao custo em recurso próprio.
- [x] Contrato do mês proporcional aos dias de semana com viagem da empresa.
- [x] Consumo real (km por litro) contra o consumo médio do veículo.
- [x] Previsto e realizado por rota, e frota sem viagem há 30 dias.
- [x] Veículo com licença vencida aparece na tela de gastos antes do lançamento.

### Onda 8 — Família e cerca

- [x] Link de embarque e de desembarque, sem login e sem aplicativo.
- [x] A escola vê os avisos das suas rotas. O institucional vê a rede.
- [x] Cerca do ponto, desligada por padrão. Com a opção ligada, o embarque com GPS fora do raio fica marcado.
- [x] A cerca não impede o embarque e não é exigência do PNATE.

### Rodada 9 — Indicadores

- [x] Gráfico mensal de km planejado da viagem contra o odômetro, em Relatórios e no CACS.
- [x] Gasto do mês por fonte, só para quem já vê custo.
- [x] Cobertura rural, divergência do Censo, litros por 100 km, preventiva em dia, disponibilidade e ocorrências por 100 mil km.
- [x] Cruzamento de escola, rota, turno e zona, e de vaga publicada com alunos na rota.
- [x] Não calcular o Ideges.

### Rodada 10 — Conformidade

- [x] Autorização DETRAN ou CIRETRAN, com validade, alerta e anexo.
- [x] Checklist de faixa, cinto, tacógrafo e identificação, com anexo.
- [x] Curso de transporte escolar no cadastro do motorista.
- [x] Viagem nova não abre com autorização, licenciamento, CNH ou curso vencido.

### Rodada 11 — Estoque da frota

- [x] Pneu, filtro, óleo e pastilha, com mínimo e lote.
- [x] Baixa ligada à ordem e ao veículo.
- [x] Contagem física com a diferença no movimento.

## Fases

As ondas acima são o to-do. A fase diz o que precisa existir antes para a onda seguinte não nascer vazia. Não lançar a onda 6 (dinheiro) antes do km realizado da onda 4, apoiada na descarga da onda 3. A importação do SETE (onda 1) pode começar junto com a onda 2, porque ela alimenta o cadastro.

### Fase 0 — Arrumar o cadastro que já existe

Para a secretaria parar de perder o vínculo na virada do ano.

- Unificar usuário da rota com matrícula ativa: ao gravar o usuário, exigir aluno com matrícula no ano da rota e gravar junto `transporte_aluno` e os campos de Censo.
- Alerta na lista de motoristas e de veículos quando CNH ou data de licença vencer em 30 dias.
- Cópia de ano também copia os usuários cuja matrícula continuar ativa no ano destino.
- Documentar e validar os códigos de uma letra (`tipo_rota` U/R, `tercerizado`, `malha`, `tipo` do itinerário).
- Corrigir a cópia para casar itinerário pelo código da rota de origem, não pela posição da lista.
- Teste mínimo das regras acima. A tela pode continuar legada nesta fase.

Pronto quando: copiar o ano não deixa aluno de fora sem motivo, e um aluno urbano/rural da rota aparece igual na ficha do Censo.

### Fase 1 — Mapa

Para o gestor ver se a rota cabe no veículo e se o km digitado mente.

- Ponto: clique no mapa (OpenStreetMap) grava latitude e longitude numéricas.
- Rota: desenho da linha ou importação GPX, guardada à parte do itinerário de pontos.
- Mostrar lotação do veículo contra a quantidade de usuários da rota, por turno.
- Mostrar km desenhado contra `km_pav` + `km_npav`.
- Tempo estimado simples (distância e velocidade média configurável). A resolução pede o menor tempo razoável; o número precisa aparecer, mesmo sem otimizador.

Pronto quando: uma rota rural abre no mapa com pontos, linha, km e lotação, sem chave de mapa pago.

### Fase 2 — O dia da rota

Para saber quem embarcou, não só quem estava na lista.

- Viagem: data, rota, veículo, motorista, monitor opcional, sentido.
- Embarque por lista no celular, com QR Code do aluno como atalho. Fila offline e envio quando houver rede.
- Cada evento guarda hora e coordenada quando o aparelho tiver GPS.
- Km realizado (odômetro inicial e final, ou trilha). Separar do km planejado.
- Falta no transporte não é falta na aula. São eventos diferentes. A escola continua lançando frequência no diário.

Pronto quando: um dia sem internet no trecho rural sincroniza depois e a secretaria vê embarcados, ausentes e km daquele dia.

### Fase 3 — Dinheiro

Cobre a onda 6. A prefeitura continua lançando a prestação no SiGPC.

Pronto quando: o mês fecha com custo total, a parte PNATE separada do recurso próprio (salário incluso só neste), e um veículo sem licença válida aparece antes do pagamento.

### Fase 4 — Relatórios e conselho

Cobre a onda 5. Inclui a reexportação para o SETE definida na onda 1.

Pronto quando: a lista de alunos rurais com rota bate com a ficha do Censo, o conselho abre o painel sem usuário de secretaria, e uma planilha do SETE entra e sai sem duplicar cadastro.

### Fase 5 — Família

Cobre a onda 8. O aviso é um link. A cerca é opção do município.

Não fazer nesta linha: rastreador obrigatório em todo veículo, reconhecimento facial, pagamento dentro do i-Educar, folha de pagamento, envio automático da prestação ao FNDE sem API oficial.

### Portal da família

A família usa uma página própria, no mesmo espírito da pré-matrícula digital: não entra no menu do i-Educar. O endereço é `/transporte-escolar/familia`. Ali ela pede o transporte. O andamento fica em `/transporte-escolar/familia/protocolo`. A escola e a secretaria usam `/transporte-escolar/solicitacoes`, processo `880161`.

O protocolo leva ano, sequência e um dígito de conferência. A consulta pede também a data de nascimento do aluno. A escola só encaminha o pedido da própria escola. Aceitar ou recusar é da rede. A família cancela antes da decisão. O pedido não cria pessoa nem matrícula. A página avisa que a prefeitura não pede senha nem dados por rede social.

No mesmo processo, o institucional grava o setor de atendimento: endereço, horário, documentos e coordenadas. A família vê esse bloco, com mapa, só depois que o pedido é aceito.

O institucional agrupa processos do mesmo ano, abre cada processo e publica a vaga com idade em 31 de março, série, turma, escola, rota, turno e quantidade de lugares. As tabelas mostram vagas, ocupadas, disponíveis e a espera. Pode editar o processo, pausá-lo, fechá-lo ou retomá-lo. Pausado ou fechado, a família deixa de ver a vaga. Fechar a oferta manda os pedidos aprovados sem conferência para a lista de espera. O desenho está em `docs/FLUXOS.md`.


## O que o código ainda não faz

- Monitor ligado a uma lista de rotas. O cadastro guarda turno e salário.
- Envio de SMS ou notificação push. O aviso de embarque é um link. O andamento do pedido fica na página Acompanhar.
- Aceitar o pedido não inclui o aluno na rota. A inclusão é a conferência, que pode seguir sem matrícula e deixa o aviso até o i-Educar ter matrícula ativa no ano.
- Não há um pedido por aluno. A mesma pessoa pode abrir outro protocolo enquanto houver vaga.
- A lista de espera não tem peso nem prioridade.


## O que permanece de fora

- Envio oficial ao SiGPC e baixa automática do extrato bancário.
- Folha, contracheque e pagamento de salário. O salário existe só para compor custo.
- Multa de trânsito lançada como gasto do PNATE.
- Pregão e empenho (ficam no sistema de compras e na contabilidade; aqui entra o processo como referência).
- Frequência escolar.
- Mapa pago como dependência obrigatória.
- Roteirizador próprio enquanto a sugestão do SETE desktop puder ser importada.

## Critério que este pacote já cumpriu

O v2 nasceu porque o ambiente alvo pode não ter o pacote da Portábilis. As tabelas dele, em `modules`, não são a base daqui. Empresa, motorista, veículo, ponto e rota deste pacote ficam em `transporte_escolar`.

O multi-tenant já permite ligar o pacote só em alguns municípios. Qualquer fase nova deve continuar atrás de `PackageTenant::allows('buriti/i-educar-transporte-escolar-v2')`, inclusive menu, rota HTTP e migration.
