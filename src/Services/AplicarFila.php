<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\FilaIdempotente;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AplicarFila
{
    /**
     * @param list<array<string, mixed>> $eventos
     * @return array{novos: int, repetidos: int}
     */
    public static function receber(?int $dispositivoId, array $eventos): array
    {
        $uuids = array_values(array_filter(array_map(static fn (array $evento): string => (string) ($evento['uuid'] ?? ''), $eventos)));
        $jaGravados = DB::table('transporte_escolar.fila_evento')->whereIn('uuid', $uuids === [] ? [''] : $uuids)->pluck('uuid')->all();
        $novos = FilaIdempotente::novos($uuids, $jaGravados);
        $repetidos = count($uuids) - count($novos);
        $gravados = 0;

        foreach ($eventos as $evento) {
            $uuid = (string) ($evento['uuid'] ?? '');

            if ($uuid === '' || !in_array($uuid, $novos, true)) {
                continue;
            }

            $tipo = (string) ($evento['tipo'] ?? '');

            if (FilaIdempotente::alteraCadastroMestre($tipo)) {
                throw new RuntimeException('A descarga não altera aluno, rota nem veículo.');
            }

            DB::table('transporte_escolar.fila_evento')->insert([
                'dispositivo_id' => $dispositivoId,
                'uuid' => $uuid,
                'tipo' => $tipo,
                'payload' => json_encode($evento, JSON_UNESCAPED_UNICODE),
                'ocorrido_em' => $evento['ocorrido_em'] ?? now(),
                'latitude' => $evento['latitude'] ?? null,
                'longitude' => $evento['longitude'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            self::aplicar($uuid, $tipo, $evento);
            DB::table('transporte_escolar.fila_evento')->where('uuid', $uuid)->update(['aplicado_em' => now()]);
            $gravados++;
        }

        return ['novos' => $gravados, 'repetidos' => $repetidos];
    }

    /**
     * @param array<string, mixed> $evento
     */
    private static function aplicar(string $uuid, string $tipo, array $evento): void
    {
        if (in_array($tipo, ['embarque', 'desembarque', 'falta'], true) && isset($evento['viagem_id'], $evento['aluno_id'])) {
            DB::table('transporte_escolar.embarque')->updateOrInsert(
                [
                    'viagem_id' => (int) $evento['viagem_id'],
                    'ref_cod_aluno' => (int) $evento['aluno_id'],
                    'tipo' => $tipo,
                ],
                [
                    'ocorrido_em' => $evento['ocorrido_em'] ?? now(),
                    'latitude' => $evento['latitude'] ?? null,
                    'longitude' => $evento['longitude'] ?? null,
                    'fila_uuid' => $uuid,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            RegistrarAviso::aposEvento(
                (int) $evento['viagem_id'],
                (int) $evento['aluno_id'],
                $tipo,
                isset($evento['latitude']) ? (float) $evento['latitude'] : null,
                isset($evento['longitude']) ? (float) $evento['longitude'] : null,
            );
            TrilhaKm::atualizar((int) $evento['viagem_id']);

            return;
        }

        if ($tipo === 'odometro' && isset($evento['viagem_id'])) {
            $viagem = DB::table('transporte_escolar.viagem')->where('id', (int) $evento['viagem_id'])->first();

            if ($viagem === null) {
                return;
            }

            $inicial = array_key_exists('km_inicial', $evento) ? (float) $evento['km_inicial'] : ($viagem->km_inicial === null ? null : (float) $viagem->km_inicial);
            $final = array_key_exists('km_final', $evento) ? (float) $evento['km_final'] : ($viagem->km_final === null ? null : (float) $viagem->km_final);

            if (KmRealizado::deOdometro($inicial, $final) === null && $inicial !== null && $final !== null) {
                return;
            }

            DB::table('transporte_escolar.viagem')->where('id', $viagem->id)->update([
                'km_inicial' => $inicial,
                'km_final' => $final,
                'updated_at' => now(),
            ]);

            return;
        }

        if ($tipo === 'abastecimento' && isset($evento['veiculo_id'], $evento['litros'], $evento['valor'])) {
            DB::table('transporte_escolar.abastecimento')->insert([
                'veiculo_id' => (int) $evento['veiculo_id'],
                'data' => $evento['data'] ?? now()->toDateString(),
                'litros' => $evento['litros'],
                'valor' => $evento['valor'],
                'km' => $evento['km'] ?? null,
                'fonte' => 'pnate',
                'fila_uuid' => $uuid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        if ($tipo === 'ocorrencia') {
            DB::table('transporte_escolar.ocorrencia')->insert([
                'viagem_id' => $evento['viagem_id'] ?? null,
                'texto' => (string) ($evento['texto'] ?? ''),
                'ocorrido_em' => $evento['ocorrido_em'] ?? now(),
                'fila_uuid' => $uuid,
            ]);
        }
    }
}
