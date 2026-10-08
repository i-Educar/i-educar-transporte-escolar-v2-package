<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class OrdemPontos
{
    /**
     * Ordena os pontos pela posição em que cada um aparece na trilha importada.
     *
     * @param list<array{latitude: float, longitude: float}> $trilha
     * @param list<array{id: int, latitude: float, longitude: float}> $pontos
     * @return list<int>
     */
    public static function sugerir(array $trilha, array $pontos): array
    {
        if ($trilha === [] || $pontos === []) {
            return array_map(static fn (array $ponto): int => (int) $ponto['id'], $pontos);
        }

        $ranqueados = [];

        foreach ($pontos as $ponto) {
            $melhor = 0;
            $menor = INF;

            foreach ($trilha as $indice => $vertice) {
                $distancia = KmCalculado::distancia(
                    (float) $ponto['latitude'],
                    (float) $ponto['longitude'],
                    (float) $vertice['latitude'],
                    (float) $vertice['longitude'],
                );

                if ($distancia < $menor) {
                    $menor = $distancia;
                    $melhor = $indice;
                }
            }

            $ranqueados[] = ['id' => (int) $ponto['id'], 'indice' => $melhor];
        }

        usort($ranqueados, static function (array $a, array $b): int {
            return $a['indice'] <=> $b['indice'] ?: $a['id'] <=> $b['id'];
        });

        return array_map(static fn (array $item): int => $item['id'], $ranqueados);
    }
}
