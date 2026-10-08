<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * O protocolo é o número que a família guarda.
 * O último dígito confere o restante, para um erro de digitação não abrir outro pedido.
 */
final class ProtocoloSolicitacao
{
    public static function montar(int $ano, int $sequencia): string
    {
        $corpo = sprintf('TE-%d-%06d', $ano, $sequencia);

        return $corpo . '-' . self::digito($corpo);
    }

    public static function conferir(string $protocolo): bool
    {
        $protocolo = strtoupper(trim($protocolo));

        if (!preg_match('/^(TE-\d{4}-\d{6})-(\d)$/', $protocolo, $partes)) {
            return false;
        }

        return (string) self::digito($partes[1]) === $partes[2];
    }

    /**
     * Protocolo inválido e pedido inexistente devolvem respostas diferentes.
     * Data de nascimento errada usa a mesma resposta de pedido inexistente.
     *
     * @return 'invalido'|'nao_encontrado'|'ok'
     */
    public static function respostaConsulta(bool $formatoOk, bool $achou, bool $nascimentoIgual): string
    {
        if (!$formatoOk) {
            return 'invalido';
        }

        if (!$achou || !$nascimentoIgual) {
            return 'nao_encontrado';
        }

        return 'ok';
    }

    public static function mesmaData(?string $gravada, ?string $informada): bool
    {
        $gravada = self::data($gravada);
        $informada = self::data($informada);

        return $gravada !== null && $gravada === $informada;
    }

    private static function data(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        $data = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if ($data === false || $data->format('Y-m-d') !== $valor) {
            return null;
        }

        return $valor;
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
