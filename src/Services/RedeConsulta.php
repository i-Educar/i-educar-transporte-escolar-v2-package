<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Facades\DB;

final class RedeConsulta
{
    public static function rotas(?int $ano = null)
    {
        $consulta = DB::table('transporte_escolar.rota as r')
            ->select('r.*')
            ->orderByDesc('r.ano')
            ->orderBy('r.descricao');

        if ($ano !== null) {
            $consulta->where('r.ano', $ano);
        }

        $escolas = VisaoRede::escolas();

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereExists(function ($q) use ($escolas) {
                $q->select(DB::raw('1'))
                    ->from('transporte_escolar.rota_escola as re')
                    ->whereColumn('re.rota_id', 'r.id')
                    ->whereIn('re.ref_cod_escola', $escolas);
            });
        }

        return $consulta;
    }

    public static function escolas()
    {
        $consulta = DB::table('pmieducar.escola as e')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
            ->where('e.ativo', 1)
            ->orderBy('p.nome')
            ->select('e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude', 'e.zona_localizacao');

        $escolas = VisaoRede::escolas();

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('e.cod_escola', $escolas);
        }

        return $consulta;
    }

    /**
     * Matrícula em curso no ano: ativa, sem saída e sem transferência, abandono, pré-matrícula ou falecimento.
     */
    public static function matriculasAtivas(int $ano)
    {
        $consulta = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'm.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 'm.ref_ref_cod_escola')
            ->join('cadastro.pessoa as pe', 'pe.idpes', '=', 'e.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->leftJoin('pmieducar.serie as s', 's.cod_serie', '=', 'm.ref_ref_cod_serie')
            ->leftJoin('pmieducar.matricula_turma as mt', function ($join) {
                $join->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')->where('mt.ativo', 1);
            })
            ->leftJoin('pmieducar.turma as t', 't.cod_turma', '=', 'mt.ref_cod_turma')
            ->where('m.ano', $ano)
            ->where('m.ativo', 1)
            ->where('a.ativo', 1)
            ->whereNull('m.data_exclusao')
            ->whereNotIn('m.aprovado', [4, 6, 11, 15])
            ->where(function ($q) {
                $q->where('m.saida_escola', false)->orWhereNull('m.saida_escola');
            })
            ->select(
                'a.cod_aluno',
                'p.nome',
                'f.cpf',
                'f.zona_localizacao_censo',
                'm.ref_ref_cod_escola as cod_escola',
                'pe.nome as escola',
                's.nm_serie',
                't.nm_turma',
                'mt.etapa_educacenso',
                'a.tipo_transporte',
                'a.veiculo_transporte_escolar',
                'a.utiliza_transporte_rural',
                'a.rota_transporte',
            )
            ->orderBy('p.nome');

        $escolas = VisaoRede::escolas();

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('m.ref_ref_cod_escola', $escolas);
        }

        return $consulta;
    }
}
