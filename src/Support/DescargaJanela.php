<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class DescargaJanela
{
    /**
     * @param list<string> $horarios
     */
    public static function noHorario(\DateTimeInterface $agora, array $horarios = ['05:00', '12:30'], int $toleranciaMinutos = 20, ?bool $diaLetivo = null): bool
    {
        if ($diaLetivo === false) {
            return false;
        }

        if ($diaLetivo === null && (int) $agora->format('N') >= 6) {
            return false;
        }

        $minutos = ((int) $agora->format('H') * 60) + (int) $agora->format('i');

        foreach ($horarios as $horario) {
            [$hora, $minuto] = array_map('intval', explode(':', $horario));
            $alvo = ($hora * 60) + $minuto;

            if (abs($minutos - $alvo) <= $toleranciaMinutos) {
                return true;
            }
        }

        return false;
    }

    public static function aoAbrirComRede(bool $temRede): bool
    {
        return $temRede;
    }
}
