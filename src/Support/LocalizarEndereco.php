<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Consulta só o texto do endereço. Nome, telefone e protocolo não saem do i-Educar.
 */
final class LocalizarEndereco
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function coordenada(string $endereco): ?array
    {
        $endereco = trim($endereco);

        if (mb_strlen($endereco) < 5) {
            return null;
        }

        $chave = 'transporte-endereco-' . sha1(mb_strtolower($endereco));
        $gravado = Cache::get($chave);

        if (is_array($gravado) && isset($gravado['latitude'], $gravado['longitude'])) {
            return ['latitude' => (float) $gravado['latitude'], 'longitude' => (float) $gravado['longitude']];
        }

        try {
            $resposta = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'i-Educar-transporte-escolar (consulta interna de endereco)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $endereco,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'br',
                ]);
            $item = $resposta->json()[0] ?? null;
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($item) || !is_numeric($item['lat'] ?? null) || !is_numeric($item['lon'] ?? null)) {
            return null;
        }

        $ponto = ['latitude' => (float) $item['lat'], 'longitude' => (float) $item['lon']];
        Cache::put($chave, $ponto, now()->addDay());

        return $ponto;
    }
}
