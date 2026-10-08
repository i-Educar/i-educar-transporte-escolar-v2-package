<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Números novos, fora da faixa do pacote Portábilis (69, 21235–21246).
 * O menu de permissão do i-Educar usa estes processos.
 */
final class Processos
{
    public const RAIZ = 880100;

    public const EMPRESAS = 880110;

    public const MOTORISTAS = 880111;

    public const MONITORES = 880112;

    public const VEICULOS = 880113;

    public const FORNECEDORES = 880114;

    public const PONTOS = 880115;

    public const ROTAS = 880116;

    public const ALUNOS = 880120;

    public const MAPA = 880121;

    public const COPIA = 880122;

    public const SETE = 880123;

    public const DIVERGENCIA = 880124;

    public const NORMAS = 880130;

    public const VIAGENS = 880140;

    public const OFFLINE = 880141;

    public const GASTOS = 880150;

    public const RELATORIOS = 880151;

    public const CACS = 880152;

    public const AVISOS = 880160;

    public const SOLICITACOES = 880161;

    public const AUDITORIA = 880162;

    /**
     * @return array<int, string>
     */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Transporte escolar',
            self::EMPRESAS => 'Empresas',
            self::MOTORISTAS => 'Motoristas',
            self::MONITORES => 'Monitores',
            self::VEICULOS => 'Veículos',
            self::FORNECEDORES => 'Fornecedores',
            self::PONTOS => 'Pontos',
            self::ROTAS => 'Rotas',
            self::ALUNOS => 'Alunos na rota',
            self::MAPA => 'Mapa da rota',
            self::COPIA => 'Cópia do ano',
            self::SETE => 'Importar SETE',
            self::DIVERGENCIA => 'Divergências do SETE',
            self::NORMAS => 'Normas',
            self::VIAGENS => 'Viagens do dia',
            self::OFFLINE => 'Uso offline',
            self::GASTOS => 'Gastos e manutenção',
            self::RELATORIOS => 'Relatórios',
            self::CACS => 'Painel do CACS',
            self::AVISOS => 'Aviso à família',
            self::SOLICITACOES => 'Pedidos da família',
            self::AUDITORIA => 'Auditoria',
        ];
    }
}
