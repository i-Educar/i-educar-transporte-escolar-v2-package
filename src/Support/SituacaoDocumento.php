<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A ordem descreve o serviço antes da execução.
 * O bilhete autoriza o abastecimento e o canhoto confirma o que foi posto no tanque.
 */
final class SituacaoDocumento
{
    /** @var array<string, list<string>> */
    public const ORDEM = [
        'solicitada' => ['autorizada', 'cancelada'],
        'autorizada' => ['em_execucao', 'cancelada'],
        'em_execucao' => ['concluida', 'cancelada'],
        'concluida' => [],
        'cancelada' => [],
    ];

    /** @var array<string, list<string>> */
    public const BILHETE = [
        'solicitado' => ['autorizado', 'cancelado'],
        'autorizado' => ['abastecido', 'cancelado'],
        'abastecido' => [],
        'cancelado' => [],
    ];

    /**
     * @param array<string, list<string>> $mapa
     */
    public static function pode(array $mapa, string $de, string $para): bool
    {
        return in_array($para, $mapa[$de] ?? [], true);
    }

    /**
     * @param array<string, list<string>> $mapa
     * @return list<string>
     */
    public static function proximas(array $mapa, string $de): array
    {
        return $mapa[$de] ?? [];
    }

    public static function frase(string $situacao): string
    {
        return match ($situacao) {
            'solicitada', 'solicitado' => 'Solicitado',
            'autorizada', 'autorizado' => 'Autorizado',
            'em_execucao' => 'Em execução',
            'concluida' => 'Concluída',
            'abastecido' => 'Abastecido',
            'cancelada', 'cancelado' => 'Cancelado',
            default => $situacao,
        };
    }

    public static function contaNoCusto(string $tipo, ?string $situacao): bool
    {
        if ($situacao === null || $situacao === '') {
            return true;
        }

        return $tipo === 'ordem' ? $situacao === 'concluida' : $situacao === 'abastecido';
    }
}
