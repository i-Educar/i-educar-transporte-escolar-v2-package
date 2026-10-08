<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\CopiaAnoPlanejador;
use Illuminate\Support\Facades\DB;

final class CopiaAno
{
    /**
     * @return array{copiadas: int, ignoradas: list<string>, alunos: int, alunos_recusados: list<int>}
     */
    public static function executar(int $anoOrigem, int $anoDestino): array
    {
        $rotas = DB::table('transporte_escolar.rota')->where('ano', $anoOrigem)->orderBy('id')->get();
        $ids = $rotas->pluck('id')->all();
        $itinerarios = $ids === []
            ? collect()
            : DB::table('transporte_escolar.itinerario')->whereIn('rota_id', $ids)->orderBy('seq')->get();
        $alunos = $ids === []
            ? collect()
            : DB::table('transporte_escolar.aluno_rota')->whereIn('rota_id', $ids)->get();

        $ativas = [];

        foreach ($alunos as $aluno) {
            $rota = $rotas->firstWhere('id', $aluno->rota_id);
            $ativas[(int) $aluno->ref_cod_aluno . ':' . (int) $aluno->rota_id] = $rota !== null
                && RedeConsulta::matriculasAtivas($anoDestino)->where('a.cod_aluno', $aluno->ref_cod_aluno)->exists();
        }

        $destino = DB::table('transporte_escolar.rota')->where('ano', $anoDestino)->pluck('descricao')->all();

        $plano = CopiaAnoPlanejador::planejar(
            $rotas->map(static fn ($rota): array => ['id' => (int) $rota->id, 'descricao' => (string) $rota->descricao])->all(),
            $itinerarios->map(static fn ($item): array => [
                'rota_id' => (int) $item->rota_id,
                'seq' => (int) $item->seq,
                'ponto_id' => (int) $item->ponto_id,
                'veiculo_id' => $item->veiculo_id === null ? null : (int) $item->veiculo_id,
                'hora' => $item->hora,
                'tipo' => (string) $item->tipo,
            ])->all(),
            $alunos->map(static fn ($aluno): array => [
                'aluno_id' => (int) $aluno->ref_cod_aluno,
                'rota_id' => (int) $aluno->rota_id,
                'ponto_id' => $aluno->ponto_id === null ? null : (int) $aluno->ponto_id,
                'turno' => $aluno->turno,
                'matricula_ativa' => $ativas[(int) $aluno->ref_cod_aluno . ':' . (int) $aluno->rota_id] ?? false,
            ])->all(),
            array_map('strval', $destino),
        );

        $copiadas = 0;
        $alunosCopiados = 0;

        DB::transaction(function () use ($plano, $rotas, $anoDestino, &$copiadas, &$alunosCopiados): void {
            foreach ($plano['rotas'] as $item) {
                $origem = $rotas->firstWhere('id', $item['origem_id']);
                $novoId = DB::table('transporte_escolar.rota')->insertGetId([
                    'ano' => $anoDestino,
                    'descricao' => $origem->descricao,
                    'tipo_rota' => $origem->tipo_rota,
                    'periodo' => $origem->periodo,
                    'sentido' => $origem->sentido,
                    'km_pav' => $origem->km_pav,
                    'km_npav' => $origem->km_npav,
                    'km_calculado' => $origem->km_calculado,
                    'tempo_estimado_min' => $origem->tempo_estimado_min,
                    'velocidade_media_kmh' => $origem->velocidade_media_kmh,
                    'terceirizada' => $origem->terceirizada,
                    'empresa_id' => $origem->empresa_id,
                    'origem' => 'ieducar',
                    'origem_chave' => $origem->descricao . '|' . $anoDestino,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $escolas = DB::table('transporte_escolar.rota_escola')->where('rota_id', $origem->id)->get();

                foreach ($escolas as $escola) {
                    DB::table('transporte_escolar.rota_escola')->insert([
                        'rota_id' => $novoId,
                        'ref_cod_escola' => $escola->ref_cod_escola,
                    ]);
                }

                foreach ($item['itinerarios'] as $parada) {
                    DB::table('transporte_escolar.itinerario')->insert([
                        'rota_id' => $novoId,
                        'seq' => $parada['seq'],
                        'ponto_id' => $parada['ponto_id'],
                        'veiculo_id' => $parada['veiculo_id'],
                        'hora' => $parada['hora'],
                        'tipo' => $parada['tipo'],
                    ]);
                }

                foreach ($item['alunos'] as $aluno) {
                    DB::table('transporte_escolar.aluno_rota')->insert([
                        'ref_cod_aluno' => $aluno['aluno_id'],
                        'rota_id' => $novoId,
                        'ponto_id' => $aluno['ponto_id'],
                        'turno' => $aluno['turno'],
                        'ano' => $anoDestino,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $alunosCopiados++;
                }

                $copiadas++;
            }
        });

        return [
            'copiadas' => $copiadas,
            'ignoradas' => $plano['ignoradas'],
            'alunos' => $alunosCopiados,
            'alunos_recusados' => $plano['alunos_recusados'],
        ];
    }
}
