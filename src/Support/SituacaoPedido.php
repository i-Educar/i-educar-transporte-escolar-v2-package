<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A família cancela. A escola encaminha. A rede aceita ou recusa.
 * Pedido já decidido não volta.
 */
final class SituacaoPedido
{
    public const RECEBIDA = 'recebida';

    public const ANALISE = 'em_analise';

    public const DEFERIDA = 'deferida';

    public const INDEFERIDA = 'indeferida';

    public const ESPERA = 'espera';

    public const CANCELADA = 'cancelada';

    public static function mover(string $atual, string $acao, string $ator): ?string
    {
        if ($ator === 'familia' && $acao === 'cancelar' && in_array($atual, [self::RECEBIDA, self::ANALISE, self::ESPERA], true)) {
            return self::CANCELADA;
        }

        if ($acao === 'encaminhar' && $atual === self::RECEBIDA && in_array($ator, ['escola', 'rede'], true)) {
            return self::ANALISE;
        }

        if ($ator === 'rede' && in_array($atual, [self::RECEBIDA, self::ANALISE], true)) {
            if ($acao === 'deferir') {
                return self::DEFERIDA;
            }

            if ($acao === 'esperar') {
                return self::ESPERA;
            }

            if ($acao === 'indeferir') {
                return self::INDEFERIDA;
            }
        }

        if ($ator === 'rede' && $atual === self::ESPERA) {
            if ($acao === 'deferir') {
                return self::DEFERIDA;
            }

            if ($acao === 'indeferir') {
                return self::INDEFERIDA;
            }
        }

        return null;
    }

    public static function frase(string $situacao): string
    {
        return match ($situacao) {
            self::RECEBIDA => 'A secretaria recebeu o pedido e ainda não começou a análise.',
            self::ANALISE => 'A escola ou a secretaria está analisando o pedido.',
            self::DEFERIDA => 'O pedido foi aceito. A família leva os documentos ao setor de transporte.',
            self::INDEFERIDA => 'O pedido não foi aceito.',
            self::ESPERA => 'O pedido foi analisado. Não há vaga nesta rota agora. O aluno está na lista de espera.',
            self::CANCELADA => 'A família cancelou o pedido.',
            default => 'Situação desconhecida.',
        };
    }
}
