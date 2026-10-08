<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Abre uma viagem por dia letivo e por sentido da linha.
 * Sem calendário escolar, o mês usa os dias de semana.
 */
final class LoteViagem
{
    /**
     * @param list<string> $tiposItinerario
     * @return list<string>
     */
    public static function sentidos(string $sentidoRota, array $tiposItinerario): array
    {
        $tipos = array_values(array_unique(array_filter(
            $tiposItinerario,
            static fn ($tipo): bool => in_array($tipo, ['I', 'V'], true),
        )));

        if ($tipos !== []) {
            return $tipos;
        }

        return match ($sentidoRota) {
            'I' => ['I'],
            'V' => ['V'],
            default => ['I', 'V'],
        };
    }

    public static function horario(?string $hora, string $periodo): string
    {
        if ($hora !== null && $hora !== '') {
            return substr($hora, 0, 5);
        }

        return match ($periodo) {
            'tarde' => '13:00',
            'noite' => '18:30',
            default => '07:00',
        };
    }

    /**
     * @param list<array{dia: int, mes: int, tipos: list<?string>}> $diasCalendario
     * @return list<string>
     */
    public static function datas(int $ano, int $mes, bool $temCalendario, array $diasCalendario): array
    {
        if (!$temCalendario) {
            $total = (int) date('t', strtotime(sprintf('%04d-%02d-01', $ano, $mes)));
            $datas = [];

            for ($dia = 1; $dia <= $total; $dia++) {
                $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

                if ((int) date('N', strtotime($data)) < 6) {
                    $datas[] = $data;
                }
            }

            return $datas;
        }

        $datas = [];

        foreach ($diasCalendario as $dia) {
            if (DiaLetivo::eLetivo(true, $dia['tipos']) !== true) {
                continue;
            }

            $datas[] = sprintf('%04d-%02d-%02d', $ano, (int) $dia['mes'], (int) $dia['dia']);
        }

        $datas = array_values(array_unique($datas));
        sort($datas);

        return $datas;
    }
}
