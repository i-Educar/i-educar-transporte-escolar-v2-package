<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Um processo de vaga é o período em que a família pode pedir transporte,
 * no ano letivo, para escolas e rotas que a rede publicou.
 */
final class ProcessoVaga
{
    public static function noPeriodo(bool $ativo, string $inicio, string $fim, \DateTimeImmutable $hoje): bool
    {
        if (!$ativo) {
            return false;
        }

        $abre = \DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $fim);

        if ($abre === false || $fecha === false) {
            return false;
        }

        $dia = $hoje->setTime(0, 0);

        return $dia >= $abre && $dia <= $fecha;
    }

    public static function cabe(int $vagas, int $ocupadas): bool
    {
        return $vagas > 0 && $ocupadas < $vagas;
    }

    public static function ocupa(string $situacao): bool
    {
        return in_array($situacao, [
            SituacaoPedido::RECEBIDA,
            SituacaoPedido::ANALISE,
            SituacaoPedido::DEFERIDA,
        ], true);
    }
}
