<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * O endereço do setor só vira mapa quando latitude e longitude são números válidos.
 * Pedido aceito é o único momento em que a família vê esse bloco.
 */
final class AtendimentoSetor
{
    public static function publicar(string $situacao): bool
    {
        return $situacao === SituacaoPedido::DEFERIDA;
    }

    /**
     * @return array{lat: float, lon: float, bbox: string}|null
     */
    public static function mapa(?string $latitude, ?string $longitude): ?array
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        $margem = 0.008;

        return [
            'lat' => $lat,
            'lon' => $lon,
            'bbox' => implode(',', [$lon - $margem, $lat - $margem, $lon + $margem, $lat + $margem]),
        ];
    }
}
