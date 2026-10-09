<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class Coordenada
{
    /**
     * Latitude e longitude entram juntas, dentro do mapa.
     * As duas vazias significam ponto ainda sem localização.
     */
    public static function motivo(?string $latitude, ?string $longitude): ?string
    {
        $latitude = trim((string) $latitude);
        $longitude = trim((string) $longitude);
        $semLatitude = $latitude === '';
        $semLongitude = $longitude === '';

        if ($semLatitude && $semLongitude) {
            return null;
        }

        if ($semLatitude || $semLongitude) {
            return 'Informe latitude e longitude juntas.';
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return 'A coordenada precisa ser numérica.';
        }

        if ((float) $latitude < -90 || (float) $latitude > 90 || (float) $longitude < -180 || (float) $longitude > 180) {
            return 'A coordenada está fora do mapa.';
        }

        return null;
    }

    public static function noTerritorio(?string $latitude, ?string $longitude): ?string
    {
        $motivo = self::motivo($latitude, $longitude);

        if ($motivo !== null || trim((string) $latitude) === '') {
            return $motivo;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -34 || $lat > 6 || $lng < -75 || $lng > -32) {
            return 'A coordenada precisa estar no território brasileiro.';
        }

        return null;
    }

    public static function dentroDoPais(?string $latitude, ?string $longitude): bool
    {
        if (trim((string) $latitude) === '' || trim((string) $longitude) === '') {
            return false;
        }

        return self::noTerritorio($latitude, $longitude) === null;
    }

    public static function valor(?string $numero): ?string
    {
        $numero = trim((string) $numero);

        return $numero === '' ? null : $numero;
    }
}
