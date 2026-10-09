<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Viagem nova só abre com autorização, licenciamento, CNH e curso vigentes.
 * Sem veículo ou sem motorista, o papel correspondente não é exigido.
 */
final class AptidaoViagem
{
    public const ITENS = [
        'faixa' => 'Faixa ESCOLAR',
        'cinto' => 'Cinto de segurança',
        'tacografo' => 'Tacógrafo',
        'identificacao' => 'Identificação do veículo escolar',
    ];

    public static function motivo(?string $autorizacao, ?string $licenciamento, ?string $cnh, ?string $curso, bool $temVeiculo, bool $temMotorista, string $hoje): ?string
    {
        if ($temVeiculo && !self::vigente($autorizacao, $hoje)) {
            return 'O veículo não tem autorização do DETRAN ou da CIRETRAN vigente.';
        }

        if ($temVeiculo && !self::vigente($licenciamento, $hoje)) {
            return 'O licenciamento do veículo não está vigente.';
        }

        if ($temMotorista && !self::vigente($cnh, $hoje)) {
            return 'A CNH do motorista não está vigente.';
        }

        if ($temMotorista && !self::vigente($curso, $hoje)) {
            return 'O curso de transporte escolar do motorista não está vigente.';
        }

        return null;
    }

    public static function vigente(?string $data, string $hoje): bool
    {
        return $data !== null && $data !== '' && $data >= $hoje;
    }
}
