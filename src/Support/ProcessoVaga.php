<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Um processo de vaga é o período em que a família pode pedir transporte,
 * no ano letivo, para escolas e rotas que a rede publicou.
 */
final class ProcessoVaga
{
    public const ABERTO = 'aberto';

    public const PAUSADO = 'pausado';

    public const BLOQUEADO = 'bloqueado';

    public static function rotulo(string $situacao): string
    {
        if ($situacao === self::PAUSADO || $situacao === self::BLOQUEADO) {
            return 'Pausado';
        }

        return 'Aberto';
    }

    /**
     * Pausar e bloquear têm o mesmo efeito: a família deixa de ver a vaga.
     * O botão único pausa o processo aberto e retoma o que está pausado.
     */
    public static function proxima(string $atual, string $acao): ?string
    {
        if ($atual === '' ) {
            $atual = self::ABERTO;
        }

        if ($atual === self::BLOQUEADO) {
            $atual = self::PAUSADO;
        }

        return match ($acao) {
            'pausar' => $atual === self::ABERTO ? self::PAUSADO : null,
            'retomar' => $atual === self::PAUSADO ? self::ABERTO : null,
            default => null,
        };
    }

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

    public static function recebePedido(?string $situacaoProcesso, ?string $situacaoGrupo = null, ?string $situacaoOferta = null): bool
    {
        foreach ([$situacaoProcesso, $situacaoGrupo, $situacaoOferta] as $situacao) {
            if ($situacao !== null && $situacao !== '' && $situacao !== self::ABERTO) {
                return false;
            }
        }

        return true;
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
            SituacaoPedido::CONFERIDA,
        ], true);
    }
}
