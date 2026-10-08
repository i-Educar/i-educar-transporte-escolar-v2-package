<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class KmCalculado
{
    /**
     * @param list<array{latitude: float, longitude: float}> $pontos
     */
    public static function quilometros(array $pontos): float
    {
        $total = 0.0;

        for ($i = 1, $n = count($pontos); $i < $n; $i++) {
            $total += self::distancia(
                (float) $pontos[$i - 1]['latitude'],
                (float) $pontos[$i - 1]['longitude'],
                (float) $pontos[$i]['latitude'],
                (float) $pontos[$i]['longitude'],
            );
        }

        return round($total, 2);
    }

    public static function minutos(float $km, float $velocidadeKmh): int
    {
        if ($velocidadeKmh <= 0 || $km <= 0) {
            return 0;
        }

        return (int) round(($km / $velocidadeKmh) * 60);
    }

    public static function distancia(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $raio = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $raio * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
