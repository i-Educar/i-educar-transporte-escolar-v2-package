<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\KmCalculado;
use Illuminate\Support\Facades\DB;

final class TrilhaKm
{
    public static function atualizar(int $viagemId): void
    {
        $pontos = [];

        foreach (DB::table('transporte_escolar.embarque')
            ->where('viagem_id', $viagemId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('ocorrido_em')
            ->get(['latitude', 'longitude']) as $ponto) {
            $pontos[] = ['latitude' => (float) $ponto->latitude, 'longitude' => (float) $ponto->longitude];
        }

        DB::table('transporte_escolar.viagem')->where('id', $viagemId)->update([
            'km_trilha' => count($pontos) < 2 ? null : KmCalculado::quilometros($pontos),
            'updated_at' => now(),
        ]);
    }
}
