<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class Gpx
{
    /**
     * @return list<array{latitude: float, longitude: float}>
     */
    public static function coordenadas(string $xml): array
    {
        if (!preg_match_all('/<trkpt\b[^>]*\blat="([^"]+)"[^>]*\blon="([^"]+)"/i', $xml, $direto, PREG_SET_ORDER)) {
            preg_match_all('/<trkpt\b[^>]*\blon="([^"]+)"[^>]*\blat="([^"]+)"/i', $xml, $invertido, PREG_SET_ORDER);
            $pares = [];

            foreach ($invertido as $item) {
                $pares[] = ['latitude' => (float) $item[2], 'longitude' => (float) $item[1]];
            }

            return $pares;
        }

        $pares = [];

        foreach ($direto as $item) {
            $pares[] = ['latitude' => (float) $item[1], 'longitude' => (float) $item[2]];
        }

        return $pares;
    }
}
