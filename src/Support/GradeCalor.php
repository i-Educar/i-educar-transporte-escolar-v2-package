<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Agrupa residências numa grade larga e omite a célula com poucos alunos.
 * O mapa não recebe nome, endereço nem a casa de uma criança só.
 */
final class GradeCalor
{
    /**
     * @param list<array{latitude: float, longitude: float}> $casas
     * @return list<array{latitude: float, longitude: float, alunos: int}>
     */
    public static function celulas(array $casas, int $minimo = FaixaEtaria::MINIMO): array
    {
        $grupos = [];

        foreach ($casas as $casa) {
            $latitude = round($casa['latitude'], 2);
            $longitude = round($casa['longitude'], 2);
            $chave = $latitude . '|' . $longitude;
            $grupos[$chave]['latitude'] = $latitude;
            $grupos[$chave]['longitude'] = $longitude;
            $grupos[$chave]['alunos'] = ($grupos[$chave]['alunos'] ?? 0) + 1;
        }

        return array_values(array_filter(
            $grupos,
            static fn (array $celula): bool => $celula['alunos'] >= $minimo,
        ));
    }
}
