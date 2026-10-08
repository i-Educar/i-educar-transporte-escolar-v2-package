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
                'nome' => 'Situação',
                'frase' => 'Veja o que pede atenção agora.',
                'itens' => [
                    self::item($pode, 'painel', 'Painel', 'fa-dashboard', '/transporte-escolar', request()->is('transporte-escolar')),
                ],
            ],
            [
                'numero' => 1,
                'nome' => 'Preparar a rede',
                'frase' => 'Primeiro quem transporta, o caminho e a regra.',
                'itens' => [
                    self::item($pode, 'frota', 'Empresas', 'fa-building', '/transporte-escolar/empresas', request()->is('transporte-escolar/empresas*')),
                    self::item($pode, 'frota', 'Veículos', 'fa-bus', '/transporte-escolar/veiculos', request()->is('transporte-escolar/veiculos*')),
                    self::item($pode, 'frota', 'Motoristas', 'fa-id-card', '/transporte-escolar/motoristas', request()->is('transporte-escolar/motoristas*')),
                    self::item($pode, 'frota', 'Monitores', 'fa-user', '/transporte-escolar/monitores', request()->is('transporte-escolar/monitores*')),
                    self::item($pode, 'frota', 'Pontos', 'fa-map-marker', '/transporte-escolar/pontos', request()->is('transporte-escolar/pontos*')),
                    self::item($pode, 'rotas', 'Rotas', 'fa-road', '/transporte-escolar/rotas', request()->is('transporte-escolar/rotas*')),
                    self::item($pode, 'normas', 'Normas', 'fa-book', '/transporte-escolar/normas', request()->is('transporte-escolar/normas')),
                ],
            ],
            [
                'numero' => 2,
                'nome' => 'Abrir o ano',
                'frase' => 'Copie o ano, publique a vaga e vincule o aluno.',
                'itens' => [
                    self::item($pode, 'copia', 'Cópia do ano', 'fa-copy', '/transporte-escolar/copia-ano', request()->is('transporte-escolar/copia-ano*')),
                    self::item($pode, 'solicitacoes', 'Pedidos da família', 'fa-inbox', '/transporte-escolar/solicitacoes', request()->is('transporte-escolar/solicitacoes*')),
                    self::item($pode, 'alunos', 'Alunos na rota', 'fa-users', '/transporte-escolar/alunos', request()->is('transporte-escolar/alunos*')),
                ],
            ],
            [
                'numero' => 3,
                'nome' => 'Rodar o dia',
                'frase' => 'Abra a viagem, leve o pacote offline e avise a família.',
                'itens' => [
                    self::item($pode, 'viagens', 'Viagens', 'fa-clock-o', '/transporte-escolar/viagens', request()->is('transporte-escolar/viagens*')),
                    self::item($pode, 'offline', 'Offline', 'fa-download', '/transporte-escolar/offline', request()->is('transporte-escolar/offline*')),
                    self::item($pode, 'avisos', 'Aviso à família', 'fa-comments', '/transporte-escolar/avisos', request()->is('transporte-escolar/avisos*')),
                ],
            ],
            [
                'numero' => 4,
                'nome' => 'Cuidar da frota',
                'frase' => 'Peça o serviço, autorize o abastecimento e lance o custo.',
                'itens' => [
                    self::item($pode, 'gastos', 'Ordens de serviço', 'fa-wrench', '/transporte-escolar/ordens', request()->is('transporte-escolar/ordens*')),
                    self::item($pode, 'gastos', 'Bilhetes', 'fa-tint', '/transporte-escolar/bilhetes', request()->is('transporte-escolar/bilhetes*')),
                    self::item($pode, 'gastos', 'Gastos', 'fa-money', '/transporte-escolar/gastos', request()->is('transporte-escolar/gastos*')),
                ],
            ],
            [
                'numero' => 5,
                'nome' => 'Prestar contas',
                'frase' => 'Confira o SETE, os relatórios e o conselho.',
                'itens' => [
                    self::item($pode, 'sete', 'SETE', 'fa-exchange', '/transporte-escolar/sete', request()->is('transporte-escolar/sete*')),
                    self::item($pode, 'relatorios', 'Relatórios', 'fa-file-text-o', '/transporte-escolar/relatorios', request()->is('transporte-escolar/relatorios*')),
                    self::item($pode, 'cacs', 'CACS', 'fa-balance-scale', '/transporte-escolar/cacs', request()->is('transporte-escolar/cacs')),
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
}
