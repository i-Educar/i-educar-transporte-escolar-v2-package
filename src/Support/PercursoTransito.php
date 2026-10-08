<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class PercursoTransito
{
    /**
     * @param array<string, mixed>|null $resposta
     * @return array{pontos: list<array{latitude: float, longitude: float}>, km: float, minutos: int}|null
     */
    public static function dasRuas(?array $resposta): ?array
    {
        $rota = $resposta['routes'][0] ?? null;

        if (!is_array($rota) || !is_array($rota['geometry']['coordinates'] ?? null)) {
            return null;
        }

        $pontos = [];

        foreach ($rota['geometry']['coordinates'] as $par) {
            if (!is_array($par) || count($par) < 2) {
                continue;
            }

            $pontos[] = ['latitude' => (float) $par[1], 'longitude' => (float) $par[0]];
        }

        if (count($pontos) < 2) {
            return null;
        }

        return [
            'pontos' => $pontos,
            'km' => round(((float) ($rota['distance'] ?? 0)) / 1000, 2),
            'minutos' => max(1, (int) round(((float) ($rota['duration'] ?? 0)) / 60)),
        ];
    }

    /**
     * @param list<array{latitude: float, longitude: float}> $paradas
     * @return array{pontos: list<array{latitude: float, longitude: float}>, km: float, minutos: int}
     */
    public static function direto(array $paradas, float $velocidade): array
    {
        $km = KmCalculado::quilometros($paradas);

        return [
            'pontos' => $paradas,
            'km' => $km,
            'minutos' => KmCalculado::minutos($km, $velocidade),
        ];
    }

    /**
     * @param list<array{latitude: float, longitude: float}> $pontos
     */
    public static function gpx(array $pontos): string
    {
        $trechos = '';

        foreach ($pontos as $ponto) {
            $trechos .= '<trkpt lat="' . $ponto['latitude'] . '" lon="' . $ponto['longitude'] . '"></trkpt>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?><gpx><trk><trkseg>' . $trechos . '</trkseg></trk></gpx>';
    }
}
