<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Atualiza os campos que o i-Educar 2.12 já tem no aluno.
 * Não cria coluna nova em pmieducar.aluno.
 */
final class SincroniaCenso
{
    public const NAO_UTILIZA = 0;

    public const ESTADUAL = 1;

    public const MUNICIPAL = 2;

    /**
     * @param list<int> $veiculosAtuais
     * @return array{tipo_transporte: int, utiliza_transporte_rural: bool, veiculo_transporte_escolar: list<int>, rota_transporte: ?string}
     */
    public static function aoVincular(int $tipoAtual, bool $rotaRural, ?int $tipoVeiculo, array $veiculosAtuais, string $nomeRota): array
    {
        $tipo = $tipoAtual === self::ESTADUAL ? self::ESTADUAL : self::MUNICIPAL;
        $veiculos = array_values(array_unique(array_map('intval', $veiculosAtuais)));

        if ($tipoVeiculo !== null && isset(Codigos::VEICULO_CENSO[$tipoVeiculo]) && !in_array($tipoVeiculo, $veiculos, true)) {
            $veiculos[] = $tipoVeiculo;
        }

        return [
            'tipo_transporte' => $tipo,
            'utiliza_transporte_rural' => $rotaRural,
            'veiculo_transporte_escolar' => $veiculos,
            'rota_transporte' => $nomeRota,
        ];
    }

    /**
     * @return array{tipo_transporte: int, utiliza_transporte_rural: bool, rota_transporte: ?string}
     */
    public static function aoDesvincularUltima(): array
    {
        return [
            'tipo_transporte' => self::NAO_UTILIZA,
            'utiliza_transporte_rural' => false,
            'rota_transporte' => null,
        ];
    }
}
