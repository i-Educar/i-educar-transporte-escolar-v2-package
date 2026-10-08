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
| Turma, série, etapa do Censo | `pmieducar.matricula_turma`, `turma`, `serie` | A vaga pode restringir série e turma. A busca do aluno continua mostrando a matrícula |
| Escola, latitude e longitude, zona da escola | `pmieducar.escola` | Atualiza latitude e longitude já existentes. O tipo da coluna continua o do núcleo (`varchar`) |
| INEP da escola e do aluno | `modules.educacenso_cod_escola`, `modules.educacenso_cod_aluno` | Usados na importação se as tabelas existirem. Elas são do núcleo, não do pacote Portábilis de transporte |
| Escolas do usuário | `pmieducar.escola_usuario` | Limite do perfil escolar |
| Nível do tipo de usuário | `pmieducar.tipo_usuario.nivel` | 1 e 2 rede, 4 escola, 8 sem acesso |

## O que passa a existir só aqui

Porque o 2.12 não tem rota operacional, ponto, CNH, salário da operação, lotação nem traçado:

`transporte_escolar.empresa`, `motorista`, `monitor`, `fornecedor`, `veiculo`, `ponto`, `rota`, `rota_escola`, `itinerario`, `tracado`, `aluno_rota`, `importacao_linha`.

A migration `2026_10_08_130000_operacao_custo_relatorio` acrescenta `configuracao`, `dispositivo`, `viagem`, `fila_evento`, `embarque`, `abastecimento`, `manutencao_plano`, `ordem_servico`, `gasto`, `contrato`, `parcela_recurso` e `ocorrencia`.

A migration `2026_10_08_140000_medicao_aviso_geocerca` acrescenta `encargo_percent` em motorista e monitor, `raio_metros` no ponto, `fora_da_cerca` no embarque e a tabela `aviso_familia`.

A migration `2026_10_08_150000_lacunas_operacao` acrescenta a coordenada da casa em `aluno_rota`, o km da trilha em `viagem`, a fila pendente por aparelho e dia, e o comprovante do gasto.

A migration `2026_10_08_160000_solicitacao_familia` cria `transporte_escolar.solicitacao`. O pedido guarda o texto informado no portal. Não cria linha em `cadastro.pessoa` nem em `pmieducar.aluno`.

A migration `2026_10_08_170000_processo_vaga` cria `processo_vaga` e `processo_vaga_oferta`, e liga o pedido a esse processo, à rota e à oferta. O processo guarda o ano letivo e o período. A oferta liga uma rota e uma escola, com turno e número de vagas.

A migration `2026_10_08_180000_documento_controle` acrescenta protocolo e situação em ordem de serviço e abastecimento. A migration `2026_10_08_190000_processo_grupo_regras` cria o agrupamento e as regras de idade, série e turma da vaga. A migration `2026_10_08_200000_processo_situacao` grava se o processo e o agrupamento estão abertos, pausados ou bloqueados.

Nenhuma dessas tabelas é lida em `modules.rota_transporte_escolar` nem nas outras tabelas do pacote da Portábilis. Ambiente sem aquele pacote sobe do mesmo jeito. Os fluxos que usam essas tabelas estão em `docs/FLUXOS.md`.
