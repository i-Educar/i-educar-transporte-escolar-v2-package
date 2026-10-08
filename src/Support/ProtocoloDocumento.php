<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ProtocoloDocumento
{
    public static function montar(string $prefixo, int $ano, int $sequencia): string
    {
        $corpo = sprintf('%s-%d-%06d', strtoupper($prefixo), $ano, $sequencia);

        return $corpo . '-' . self::digito($corpo);
    }

    public static function conferir(string $protocolo): bool
    {
        $protocolo = strtoupper(trim($protocolo));

        if (!preg_match('/^((?:OS|AB)-\d{4}-\d{6})-(\d)$/', $protocolo, $partes)) {
            return false;
        }

        return (string) self::digito($partes[1]) === $partes[2];
    }

    private static function digito(string $corpo): int
    {
        $soma = 0;

        foreach (str_split($corpo) as $indice => $caractere) {
            $soma += ($indice + 1) * ord($caractere);
        }

        return $soma % 10;
    }
}
