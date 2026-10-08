<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class PontosProximos
{
    /**
     * @param list<array{id: int, nome: string, latitude: float, longitude: float, raio: int}> $pontos
     * @return list<array{id: int, nome: string, metros: int, raio: int, dentro: bool}>
     */
    public static function ordenar(float $latitude, float $longitude, array $pontos, int $limite = 5): array
    {
        $lista = [];

        foreach ($pontos as $ponto) {
            $metros = (int) round(KmCalculado::distancia(
                $latitude,
                $longitude,
                $ponto['latitude'],
                $ponto['longitude'],
            ) * 1000);
            $lista[] = [
                'id' => $ponto['id'],
                'nome' => $ponto['nome'],
                'metros' => $metros,
                'raio' => $ponto['raio'],
                'dentro' => $ponto['raio'] > 0 && $metros <= $ponto['raio'],
            ];
        }

        usort($lista, static fn (array $a, array $b): int => $a['metros'] <=> $b['metros']);

        return array_slice($lista, 0, max(1, $limite));
    }
}
