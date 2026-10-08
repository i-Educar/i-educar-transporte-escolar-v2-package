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
        return [
            self::ABERTO => 'Aberto',
            self::PAUSADO => 'Pausado',
            self::BLOQUEADO => 'Bloqueado',
        ][$situacao] ?? 'Aberto';
    }

    /**
     * Pausa interrompe os pedidos e pode ser retomada.
     * Bloqueio fecha o processo até um desbloqueio explícito.
     */
    public static function proxima(string $atual, string $acao): ?string
    {
        $atual = $atual === '' ? self::ABERTO : $atual;

        return match ($acao) {
            'pausar' => $atual === self::ABERTO ? self::PAUSADO : null,
            'retomar' => $atual === self::PAUSADO ? self::ABERTO : null,
            'bloquear' => $atual === self::BLOQUEADO ? null : self::BLOQUEADO,
            'desbloquear' => $atual === self::BLOQUEADO ? self::ABERTO : null,
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
