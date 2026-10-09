<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

final class TurnoMatricula
{
    /** @var array<int, string> */
    private const MATRICULA = [
        1 => 'manha',
        2 => 'tarde',
        3 => 'noite',
        4 => 'integral',
    ];

    public static function diverge(?string $transporte, ?int $turmaTurnoId): bool
    {
        $matricula = self::MATRICULA[$turmaTurnoId] ?? null;
        $transporte = trim((string) $transporte);

        if ($matricula === null || !isset(Codigos::PERIODO[$transporte])) {
            return false;
        }

        if ($transporte === 'integral' || $matricula === 'integral') {
            return false;
        }

        return $transporte !== $matricula;
    }

    /**
     * @param list<int> $rotas
     * @return array<int, list<array{nome: string, transporte: string, matricula: string}>>
     */
    public static function porRota(array $rotas): array
    {
        if ($rotas === []) {
            return [];
        }

        $linhas = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->join('pmieducar.matricula as m', function ($join) {
                $join->on('m.ref_cod_aluno', '=', 'a.cod_aluno')
                    ->on('m.ano', '=', 'r.ano')
                    ->where('m.ativo', 1);
            })
            ->join('pmieducar.matricula_turma as mt', function ($join) {
                $join->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')->where('mt.ativo', 1);
            })
            ->join('pmieducar.turma as t', 't.cod_turma', '=', 'mt.ref_cod_turma')
            ->whereIn('ar.rota_id', $rotas)
            ->whereNotNull('t.turma_turno_id')
            ->get(['ar.rota_id', 'ar.ref_cod_aluno', 'ar.turno', 'r.periodo', 'p.nome', 't.nm_turma', 't.turma_turno_id']);

        $saida = [];

        foreach ($linhas as $linha) {
            $transporte = isset(Codigos::PERIODO[$linha->turno]) ? (string) $linha->turno : (string) $linha->periodo;

            if (!self::diverge($transporte, (int) $linha->turma_turno_id)) {
                continue;
            }

            $chave = (int) $linha->rota_id;
            $aluno = (int) $linha->ref_cod_aluno;
            $rotulo = (Codigos::PERIODO[self::MATRICULA[(int) $linha->turma_turno_id]] ?? 'Turno da turma') . ' · ' . $linha->nm_turma;
            $saida[$chave][$aluno] ??= [
                'nome' => (string) $linha->nome,
                'transporte' => Codigos::PERIODO[$transporte] ?? $transporte,
                'matricula' => $rotulo,
            ];
        }

        return array_map('array_values', $saida);
    }
}
