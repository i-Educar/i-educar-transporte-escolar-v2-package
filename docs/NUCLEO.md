# Tabelas do i-Educar 2.12 que o pacote não recria

A migration `2026_10_08_120000_create_transporte_escolar_v2_tables` chama `CoreEsquema::exigir()`. Se a coluna não existir, nada do schema `transporte_escolar` é criado.

| Dado | Onde já está | O que o v2 faz |
| --- | --- | --- |
| Nome da pessoa | `cadastro.pessoa.nome` | Só guarda `ref_idpes` |
| CPF, nascimento, sexo, zona do Censo | `cadastro.fisica` | Busca pelo CPF. Não cria pessoa |
| CNPJ e fantasia | `cadastro.juridica` | Empresa e fornecedor apontam para a pessoa jurídica |
| Aluno | `pmieducar.aluno` | Vínculo em `transporte_escolar.aluno_rota` |
| Tipo de transporte, veículo do Censo, rota em texto, transporte rural | `pmieducar.aluno.tipo_transporte`, `veiculo_transporte_escolar`, `rota_transporte`, `utiliza_transporte_rural` | Atualiza esses campos ao vincular ou retirar o aluno |
| Matrícula, ano, escola, situação | `pmieducar.matricula` | Exige matrícula ativa: `ativo = 1`, sem saída, situação fora de transferido (4), abandono (6), pré-matrícula (11) e falecido (15) |
| Turma, série, etapa do Censo | `pmieducar.matricula_turma`, `turma`, `serie` | Só mostra na busca do aluno |
| Escola, latitude e longitude, zona da escola | `pmieducar.escola` | Atualiza latitude e longitude já existentes. O tipo da coluna continua o do núcleo (`varchar`) |
| INEP da escola e do aluno | `modules.educacenso_cod_escola`, `modules.educacenso_cod_aluno` | Usados na importação se as tabelas existirem. Elas são do núcleo, não do pacote Portábilis de transporte |
| Escolas do usuário | `pmieducar.escola_usuario` | Limite do perfil escolar |
| Nível do tipo de usuário | `pmieducar.tipo_usuario.nivel` | 1 e 2 rede, 4 escola, 8 sem acesso |

## O que passa a existir só aqui

Porque o 2.12 não tem rota operacional, ponto, CNH, salário da operação, lotação nem traçado:

`transporte_escolar.empresa`, `motorista`, `monitor`, `fornecedor`, `veiculo`, `ponto`, `rota`, `rota_escola`, `itinerario`, `tracado`, `aluno_rota`, `importacao_linha`.

A migration `2026_10_08_130000_operacao_custo_relatorio` acrescenta `configuracao`, `dispositivo`, `viagem`, `fila_evento`, `embarque`, `abastecimento`, `manutencao_plano`, `ordem_servico`, `gasto`, `contrato`, `parcela_recurso` e `ocorrencia`.

A migration `2026_10_08_140000_medicao_aviso_geocerca` acrescenta `encargo_percent` em motorista e monitor, `raio_metros` no ponto, `fora_da_cerca` no embarque e a tabela `aviso_familia`.

Nenhuma dessas tabelas é lida em `modules.rota_transporte_escolar` nem nas outras tabelas do pacote da Portábilis. Ambiente sem aquele pacote sobe do mesmo jeito.
