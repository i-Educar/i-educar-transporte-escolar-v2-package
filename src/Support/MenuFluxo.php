<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A ordem dos temas é a ordem do trabalho.
 * Cada submenu segue a ação seguinte, não o nome da tela.
 */
final class MenuFluxo
{
    /**
     * @param array<string, bool> $pode
     * @return list<array{numero: ?int, nome: string, frase: string, itens: list<array{rotulo: string, icone: string, url: string, ativo: bool}>}>
     */
    public static function grupos(array $pode): array
    {
        $grupos = [
            [
                'numero' => null,
                'nome' => 'Início',
                'frase' => 'Situação do dia',
                'itens' => [
                    self::item($pode, 'painel', 'Início', 'fa-th-large', '/transporte-escolar', request()->is('transporte-escolar')),
                    self::item($pode, 'painel', 'Avisos do dia', 'fa-bell', '/transporte-escolar/notificacoes', request()->is('transporte-escolar/notificacoes*')),
                ],
            ],
            [
                'numero' => null,
                'nome' => 'Cadastros',
                'frase' => 'Quem transporta e por onde',
                'itens' => [
                    self::item($pode, 'frota', 'Empresas', 'fa-building', '/transporte-escolar/empresas', request()->is('transporte-escolar/empresas*')),
                    self::item($pode, 'frota', 'Veículos', 'fa-bus', '/transporte-escolar/veiculos', request()->is('transporte-escolar/veiculos*')),
                    self::item($pode, 'frota', 'Motoristas', 'fa-id-card', '/transporte-escolar/motoristas', request()->is('transporte-escolar/motoristas*')),
                    self::item($pode, 'frota', 'Monitores', 'fa-user', '/transporte-escolar/monitores', request()->is('transporte-escolar/monitores*')),
                    self::item($pode, 'frota', 'Pontos de embarque', 'fa-map-marker', '/transporte-escolar/pontos', request()->is('transporte-escolar/pontos*')),
                    self::item($pode, 'rotas', 'Rotas', 'fa-road', '/transporte-escolar/rotas', request()->is('transporte-escolar/rotas*')),
                    self::item($pode, 'normas', 'Normas do serviço', 'fa-book', '/transporte-escolar/normas', request()->is('transporte-escolar/normas')),
                ],
            ],
            [
                'numero' => null,
                'nome' => 'Atendimento',
                'frase' => 'Ano, vaga e estudante',
                'itens' => [
                    self::item($pode, 'copia', 'Copiar dados do ano', 'fa-copy', '/transporte-escolar/copia-ano', request()->is('transporte-escolar/copia-ano*')),
                    self::item($pode, 'solicitacoes', 'Pedidos da família', 'fa-inbox', '/transporte-escolar/solicitacoes', request()->is('transporte-escolar/solicitacoes*')),
                    self::item($pode, 'alunos', 'Estudantes na rota', 'fa-users', '/transporte-escolar/alunos', request()->is('transporte-escolar/alunos*')),
                ],
            ],
            [
                'numero' => null,
                'nome' => 'Operação',
                'frase' => 'Viagem e comunicação',
                'itens' => [
                    self::item($pode, 'viagens', 'Viagens do dia', 'fa-clock-o', '/transporte-escolar/viagens', request()->is('transporte-escolar/viagens*')),
                    self::item($pode, 'offline', 'Trabalhar sem internet', 'fa-download', '/transporte-escolar/offline', request()->is('transporte-escolar/offline*')),
                    self::item($pode, 'avisos', 'Avisar a família', 'fa-comments', '/transporte-escolar/avisos', request()->is('transporte-escolar/avisos') || request()->is('transporte-escolar/aviso/*')),
                    self::item($pode, 'avisosModelos', 'Modelos de mensagem', 'fa-file-text-o', '/transporte-escolar/avisos/modelos', request()->is('transporte-escolar/avisos/modelos*')),
                ],
            ],
            [
                'numero' => null,
                'nome' => 'Frota',
                'frase' => 'Documentos, peças e custos',
                'itens' => [
                    self::item($pode, 'frota', 'Documentação em dia', 'fa-check-square-o', '/transporte-escolar/conformidade', request()->is('transporte-escolar/conformidade*')),
                    self::item($pode, 'gastos', 'Ordens de serviço', 'fa-wrench', '/transporte-escolar/ordens', request()->is('transporte-escolar/ordens*')),
                    self::item($pode, 'gastos', 'Abastecimento', 'fa-tint', '/transporte-escolar/bilhetes', request()->is('transporte-escolar/bilhetes*')),
                    self::item($pode, 'gastos', 'Peças e estoque', 'fa-cubes', '/transporte-escolar/estoque', request()->is('transporte-escolar/estoque*')),
                    self::item($pode, 'gastos', 'Gastos', 'fa-money', '/transporte-escolar/gastos', request()->is('transporte-escolar/gastos*')),
                ],
            ],
            [
                'numero' => null,
                'nome' => 'Prestação de contas',
                'frase' => 'Indicadores e conselho',
                'itens' => [
                    self::item($pode, 'sete', 'Integração SETE', 'fa-exchange', '/transporte-escolar/sete', request()->is('transporte-escolar/sete*')),
                    self::relatorios($pode),
                    self::item($pode, 'auditoria', 'Histórico de alterações', 'fa-history', '/transporte-escolar/auditoria', request()->is('transporte-escolar/auditoria*')),
                ],
            ],
        ];

        $visiveis = [];

        foreach ($grupos as $grupo) {
            $itens = array_values(array_filter($grupo['itens']));

            if ($itens === []) {
                continue;
            }

            $grupo['itens'] = $itens;
            $visiveis[] = $grupo;
        }

        return $visiveis;
    }

    /**
     * @param array<string, bool> $pode
     * @return ?array{rotulo: string, icone: string, url: string, ativo: bool}
     */
    private static function item(array $pode, string $chave, string $rotulo, string $icone, string $caminho, bool $ativo): ?array
    {
        if (empty($pode[$chave])) {
            return null;
        }

        return [
            'rotulo' => $rotulo,
            'icone' => $icone,
            'url' => url($caminho),
            'ativo' => $ativo,
        ];
    }

    /**
     * @param array<string, bool> $pode
     * @return ?array{rotulo: string, icone: string, url: string, ativo: bool}
     */
    private static function relatorios(array $pode): ?array
    {
        $rede = !empty($pode['relatorios']);
        $conselho = !empty($pode['cacs']);

        if (!$rede && !$conselho) {
            return null;
        }

        $caminho = $rede ? '/transporte-escolar/relatorios' : '/transporte-escolar/cacs';

        return self::item(
            ['relatorios' => true],
            'relatorios',
            'Relatórios',
            'fa-bar-chart',
            $caminho,
            request()->is('transporte-escolar/relatorios*') || request()->is('transporte-escolar/cacs*'),
        );
    }
}
