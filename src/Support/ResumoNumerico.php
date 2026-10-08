<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ResumoNumerico
{
    /**
     * @param list<float> $valores
     * @return array{minimo: float, media: float, maximo: float, total: float}
     */
    public static function de(array $valores): array
    {
        if ($valores === []) {
            return ['minimo' => 0.0, 'media' => 0.0, 'maximo' => 0.0, 'total' => 0.0];
        }

        $total = array_sum($valores);

        return [
            'minimo' => round(min($valores), 2),
            'media' => round($total / count($valores), 2),
            'maximo' => round(max($valores), 2),
            'total' => round($total, 2),
        ];
    }
}
