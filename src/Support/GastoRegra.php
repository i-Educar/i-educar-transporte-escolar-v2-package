<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class GastoRegra
{
    public const FONTES = [
        'pnate' => 'PNATE',
        'proprio' => 'Recurso próprio',
        'estadual' => 'Estadual',
        'convenio' => 'Convênio',
        'outro' => 'Outro',
    ];

    public const CATEGORIAS = [
        'manutencao_rodoviaria' => 'Manutenção rodoviária',
        'manutencao_aquaviaria' => 'Manutenção de embarcação',
        'terceirizado' => 'Serviço terceirizado',
        'passe' => 'Passe estudantil',
        'seguro' => 'Seguro',
        'ipva' => 'IPVA',
        'licenciamento' => 'Licenciamento',
        'taxa' => 'Taxa do ano',
    ];

    public const BLOQUEADAS_NO_PNATE = [
        'multa' => 'Multa',
        'tarifa_bancaria' => 'Tarifa bancária',
        'salario' => 'Salário',
        'encargo' => 'Encargo',
        'tributo' => 'Tributo que não incide sobre o material ou serviço',
    ];

    public static function motivo(?string $categoria, ?string $fonte, bool $aquaviario): ?string
    {
        if ($categoria === null || (!isset(self::CATEGORIAS[$categoria]) && !isset(self::BLOQUEADAS_NO_PNATE[$categoria]))) {
            return 'Categoria de gasto desconhecida.';
        }

        if ($fonte === null || !isset(self::FONTES[$fonte])) {
            return 'Fonte de recurso desconhecida.';
        }

        if (isset(self::BLOQUEADAS_NO_PNATE[$categoria]) && $fonte === 'pnate') {
            return self::BLOQUEADAS_NO_PNATE[$categoria] . ' não pode ser lançado com recurso do PNATE.';
        }

        if (isset(self::BLOQUEADAS_NO_PNATE[$categoria])) {
            return 'Salário, encargo, multa e tarifa não entram como gasto operacional. O salário só compõe o custo, em recurso próprio.';
        }

        if ($categoria === 'manutencao_aquaviaria' && !$aquaviario) {
            return 'Embarcação só aparece quando o município marca que tem transporte aquaviário.';
        }

        return null;
    }
}
