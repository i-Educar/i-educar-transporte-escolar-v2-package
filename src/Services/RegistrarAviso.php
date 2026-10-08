<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\AvisoTexto;
use iEducar\Packages\BuritiTransporteEscolar\Support\Geocerca;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegistrarAviso
{
    public static function aposEvento(int $viagemId, int $alunoId, string $tipo, ?float $latitude, ?float $longitude): void
    {
        if (!in_array($tipo, ['embarque', 'desembarque'], true)) {
            return;
        }

        $embarque = DB::table('transporte_escolar.embarque')
            ->where('viagem_id', $viagemId)
            ->where('ref_cod_aluno', $alunoId)
            ->where('tipo', $tipo)
            ->first();

        if ($embarque === null) {
            return;
        }

        $fora = self::foraDaCerca($viagemId, $alunoId, $latitude, $longitude);
        DB::table('transporte_escolar.embarque')->where('id', $embarque->id)->update([
            'fora_da_cerca' => $fora,
            'updated_at' => now(),
        ]);

        if (DB::table('transporte_escolar.aviso_familia')->where('embarque_id', $embarque->id)->exists()) {
            return;
        }

        $nome = (string) DB::table('pmieducar.aluno as a')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->where('a.cod_aluno', $alunoId)
            ->value('p.nome');
        $rota = (string) DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('v.id', $viagemId)
            ->value('r.descricao');

        DB::table('transporte_escolar.aviso_familia')->insert([
            'embarque_id' => $embarque->id,
            'ref_cod_aluno' => $alunoId,
            'token' => Str::random(40),
            'texto' => AvisoTexto::frase($nome, $tipo, $rota) . ($fora ? ' O embarque ficou fora da cerca do ponto.' : ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function foraDaCerca(int $viagemId, int $alunoId, ?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        if (DB::table('transporte_escolar.configuracao')->where('chave', 'geocerca')->value('valor') !== '1') {
            return false;
        }

        $ponto = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.viagem as v', 'v.rota_id', '=', 'ar.rota_id')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'ar.ponto_id')
            ->where('v.id', $viagemId)
            ->where('ar.ref_cod_aluno', $alunoId)
            ->first(['p.latitude', 'p.longitude', 'p.raio_metros']);

        if ($ponto === null || $ponto->latitude === null || $ponto->longitude === null) {
            return false;
        }

        $raio = (int) ($ponto->raio_metros ?: DB::table('transporte_escolar.configuracao')->where('chave', 'raio_metros')->value('valor') ?: 150);

        return !Geocerca::dentro($latitude, $longitude, (float) $ponto->latitude, (float) $ponto->longitude, $raio);
    }
}
