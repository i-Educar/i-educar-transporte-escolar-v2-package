<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Pontos públicos de Caxias do Sul. Servem de endereço quando a escola
 * ainda não tem coordenada válida no território brasileiro.
 */
final class LocaisSimulados
{
    /** @var list<array{nome: string, lat: float, lng: float}> */
    public const LUGARES = [
        ['nome' => 'Praça Dante Alighieri, Centro, Caxias do Sul', 'lat' => -29.16794, 'lng' => -51.17939],
        ['nome' => 'Rua Sinimbu, Centro, Caxias do Sul', 'lat' => -29.16880, 'lng' => -51.18055],
        ['nome' => 'Rua Pinheiro Machado, Centro, Caxias do Sul', 'lat' => -29.16620, 'lng' => -51.17680],
        ['nome' => 'Bairro São Pelegrino, Caxias do Sul', 'lat' => -29.16390, 'lng' => -51.18640],
        ['nome' => 'Bairro Nossa Senhora de Lourdes, Caxias do Sul', 'lat' => -29.16150, 'lng' => -51.17020],
        ['nome' => 'Bairro Esplanada, Caxias do Sul', 'lat' => -29.17440, 'lng' => -51.16020],
        ['nome' => 'Bairro Pio X, Caxias do Sul', 'lat' => -29.17880, 'lng' => -51.19350],
        ['nome' => 'Bairro Exposição, Caxias do Sul', 'lat' => -29.17180, 'lng' => -51.18660],
        ['nome' => 'Bairro Cruzeiro, Caxias do Sul', 'lat' => -29.18620, 'lng' => -51.16880],
        ['nome' => 'Bairro Santa Catarina, Caxias do Sul', 'lat' => -29.18350, 'lng' => -51.17600],
        ['nome' => 'Bairro Panazzolo, Caxias do Sul', 'lat' => -29.19120, 'lng' => -51.17240],
        ['nome' => 'Bairro Kayser, Caxias do Sul', 'lat' => -29.15280, 'lng' => -51.20150],
    ];

    /**
     * @return array{nome: string, lat: float, lng: float}
     */
    public static function lugar(int $indice): array
    {
        $lugares = self::LUGARES;
        $lugar = $lugares[$indice % count($lugares)];

        return [
            'nome' => $lugar['nome'],
            'lat' => $lugar['lat'],
            'lng' => $lugar['lng'],
        ];
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function proximo(float $latitude, float $longitude, int $indice, int $metros = 0): array
    {
        if (!Coordenada::dentroDoPais((string) $latitude, (string) $longitude)) {
            $lugar = self::lugar($indice);

            return [$lugar['lat'], $lugar['lng']];
        }

        $metros = $metros > 0 ? $metros : 140 + ($indice % 7) * 80;
        $rumo = deg2rad(($indice * 47) % 360);
        $lat = $latitude + ($metros * cos($rumo)) / 111320;
        $lng = $longitude + ($metros * sin($rumo)) / (111320 * max(0.2, cos(deg2rad($latitude))));

        if (!Coordenada::dentroDoPais((string) $lat, (string) $lng)) {
            $lugar = self::lugar($indice);

            return [$lugar['lat'], $lugar['lng']];
        }

        return [round($lat, 7), round($lng, 7)];
    }
}
