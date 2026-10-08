<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class Geocerca
{
    public static function dentro(float $latitude, float $longitude, float $latitudePonto, float $longitudePonto, int $raioMetros): bool
    {
        if ($raioMetros <= 0) {
            return true;
        }

        $metros = KmCalculado::distancia($latitude, $longitude, $latitudePonto, $longitudePonto) * 1000;

        return $metros <= $raioMetros;
    }
}
