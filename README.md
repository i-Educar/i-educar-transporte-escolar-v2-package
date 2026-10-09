# Transporte Escolar v2

Pacote Buriti para o i-Educar 2.12. Funciona quando o pacote `portabilis/i-educar-transport-package` não está instalado: as tabelas ficam no schema `transporte_escolar`, não em `modules`.

Esta versão é a 1.6.0. Cobre as fases 0 a 5, as ondas 1 a 8, o portal da família, o processo de vagas, o PDF de duas vias, o aviso no navegador, a frequência da viagem e o painel na ordem do trabalho. SMS, envio ao SiGPC e folha de pagamento continuam de fora.

## O que o núcleo já guarda

O pacote não cria de novo nome, CPF, matrícula, turma, série, zona, coordenada da escola nem os campos de transporte do aluno. A lista está em `docs/NUCLEO.md`. Se uma coluna do 2.12 faltar, a migration para e pede para migrar o núcleo antes.

## Menu e perfis

O menu **Transporte escolar** nasce com processos `880100`–`880161`, fora da faixa do pacote da Portábilis. A migration grava `menu_tipo_usuario` conforme o nível do tipo de usuário:

- institucional (níveis 1 e 2) grava a rede, aceita ou recusa o pedido da família e lê o painel do CACS;
- escola (nível 4) consulta a frota e a rota, grava o aluno da própria matrícula e o embarque das suas rotas, encaminha o pedido da própria escola e sobe a fila offline;
- biblioteca (nível 8) não recebe o menu.

O detalhe de cada tela está em `docs/PERMISSOES.md`. O poli-institucional continua vendo todos os menus pelo núcleo, como já acontece no i-Educar. Gastos, SETE, cópia do ano e o painel do CACS não abrem para a escola.

## Instalação

O pacote nasce fora do plug-and-play. Para carregar numa instalação, retire `buriti/i-educar-transporte-escolar-v2` da lista `extra.composer-plug-and-play.ignore` em `packages/composer.json` e rode o plug-and-play.

No multi-tenant, liste a conexão do município em `tenants.packages`. Sem essa chave o pacote vale para todas as conexões. Com a lista, a migration e o menu só entram na conexão citada:

```php
'packages' => [
    'buriti/i-educar-transporte-escolar-v2' => [
        'conexao_do_municipio',
    ],
],
```

```bash
php artisan migrate --database=conexao_do_municipio
php artisan transporte:simula --database=conexao_do_municipio
```

`transporte:simula` monta a operação de teste com os alunos já matriculados no ano vigente: frota, linhas curtas, rotas longas com várias paradas e dezenas de alunos, viagens, avisos, fila de notificação, informes de prazo, pedidos em várias situações, ocorrência, ordem aberta e bilhete pendente. Se o ano não tiver matrícula, o comando para e pede para rodar `php artisan ieducar:matriculas` antes. Uma segunda execução não duplica o que já foi simulado.

Os testes de regra, sem banco, rodam com `php tests/run.php`.

## Documentos

- `docs/ESTA-VERSAO.md` — o que esta entrega faz
- `docs/NUCLEO.md` — tabelas do 2.12 reaproveitadas
- `docs/PERMISSOES.md` — institucional e escolar, por fase e onda
- `docs/GUIA-EVOLUCAO.md` — guia completo, inclusive a fase 5
- `docs/FLUXOS.md` — fluxogramas do processo de vagas, do pedido e da análise

## Portal da família

A família não usa o menu do i-Educar. O endereço `/transporte-escolar/familia` é uma página própria do transporte, no mesmo espírito da pré-matrícula: pedir o transporte e acompanhar o protocolo. A consulta pede o protocolo e a data de nascimento. A escola e a secretaria tratam o pedido em `/transporte-escolar/solicitacoes`. Nesse mesmo lugar, o perfil institucional grava o endereço do setor, os documentos e as coordenadas do mapa. A família vê o mapa quando o pedido é aceito.
