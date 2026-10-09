<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\ConsumoReal;
use iEducar\Packages\BuritiTransporteEscolar\Support\DiasUteis;
use iEducar\Packages\BuritiTransporteEscolar\Support\IndicadorRede;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use Illuminate\Support\Facades\DB;

/**
 * Junta os números que os gráficos mostram. Não calcula o Ideges.
 */
final class IndicadoresLeitura
{
    /**
     * @param list<int> $rotas
     * @return array<string, mixed>
     */
    public static function montar(int $ano, int $mes, array $rotas, bool $comCusto): array
    {
        $serie = self::serieKm($ano, $rotas);
        $teto = 0.0;

        foreach ($serie as $ponto) {
            $teto = max($teto, $ponto['planejado'], $ponto['realizado']);
        }

        foreach ($serie as $numero => $ponto) {
            $serie[$numero]['faixa_planejado'] = IndicadorRede::faixa($ponto['planejado'], $teto);
            $serie[$numero]['faixa_realizado'] = IndicadorRede::faixa($ponto['realizado'], $teto);
        }

        $ruraisRota = self::ruraisNaRota($ano, $rotas);
        $ruraisMatricula = self::ruraisMatriculados($ano);
        $semRota = self::censoSemRota($ano);
        $semFicha = self::rotaSemCenso($ano, $rotas);
        $baseCenso = max($semRota + $semFicha, 1);
        $preventiva = self::preventiva($rotas);
        $diasViagem = self::diasComViagem($ano, $rotas);
        $diasLetivos = self::diasLetivos($ano);
        $km = 0.0;

        foreach ($serie as $ponto) {
            $km += $ponto['realizado'];
        }

        $ocorrencias = $rotas === [] ? 0 : (int) DB::table('transporte_escolar.ocorrencia as o')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'o.viagem_id')
            ->whereIn('v.rota_id', $rotas)
            ->whereYear('v.data', $ano)
            ->count();
        $embarcados = $rotas === [] ? 0 : (int) DB::table('transporte_escolar.embarque as e')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->whereIn('v.rota_id', $rotas)
            ->where('e.tipo', 'embarque')
            ->whereYear('v.data', $ano)
            ->whereMonth('v.data', $mes)
            ->distinct()
            ->count('e.ref_cod_aluno');
        $custo = $comCusto ? \iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal::fechar($ano, $mes) : null;

        return [
            'serie' => $serie,
            'cobertura' => [
                'parte' => $ruraisRota,
                'todo' => $ruraisMatricula,
                'percentual' => IndicadorRede::percentual($ruraisRota, $ruraisMatricula),
            ],
            'censo' => [
                'sem_rota' => $semRota,
                'sem_ficha' => $semFicha,
                'percentual' => IndicadorRede::percentual($semRota + $semFicha, $baseCenso),
            ],
            'consumo' => self::consumo($ano, $rotas),
            'preventiva' => [
                'parte' => $preventiva['em_dia'],
                'todo' => $preventiva['com_plano'],
                'percentual' => IndicadorRede::percentual($preventiva['em_dia'], $preventiva['com_plano']),
            ],
            'disponibilidade' => [
                'parte' => $diasViagem,
                'todo' => $diasLetivos,
                'percentual' => IndicadorRede::percentual($diasViagem, $diasLetivos),
            ],
            'ocorrencias' => [
                'quantidade' => $ocorrencias,
                'indice' => IndicadorRede::ocorrenciasPorCemMil($ocorrencias, $km),
            ],
            'custo_aluno' => $custo === null ? null : IndicadorRede::custoPorAluno((float) $custo['pnate'], $embarcados),
            'fontes' => $custo === null ? null : [
                'pnate' => (float) $custo['pnate'],
                'proprio' => (float) $custo['proprio'],
                'outros' => (float) $custo['outros'],
            ],
            'cruzamento' => self::cruzamento($ano, $rotas),
            'vagas' => self::vagas($ano, $rotas),
        ];
    }

    /**
     * @param list<int> $rotas
     * @return array<int, array{planejado: float, realizado: float}>
     */
    private static function serieKm(int $ano, array $rotas): array
    {
        $serie = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $serie[$mes] = ['planejado' => 0.0, 'realizado' => 0.0];
        }

        if ($rotas === []) {
            return $serie;
        }

        $kmRota = DB::table('transporte_escolar.rota')->whereIn('id', $rotas)->get(['id', 'km_pav', 'km_npav'])->keyBy('id');
        $viagens = DB::table('transporte_escolar.viagem')->whereIn('rota_id', $rotas)->whereYear('data', $ano)->get(['rota_id', 'data', 'km_inicial', 'km_final']);

        foreach ($viagens as $viagem) {
            $mes = (int) date('n', strtotime((string) $viagem->data));
            $rota = $kmRota[$viagem->rota_id] ?? null;
            $serie[$mes]['planejado'] += $rota === null ? 0 : (float) $rota->km_pav + (float) $rota->km_npav;
            $km = KmRealizado::deOdometro(
                $viagem->km_inicial === null ? null : (float) $viagem->km_inicial,
                $viagem->km_final === null ? null : (float) $viagem->km_final,
            );
            $serie[$mes]['realizado'] += $km ?? 0;
        }

        foreach ($serie as $mes => $ponto) {
            $serie[$mes]['planejado'] = round($ponto['planejado'], 1);
            $serie[$mes]['realizado'] = round($ponto['realizado'], 1);
        }

        return $serie;
    }

    /**
     * @param list<int> $rotas
     */
    private static function ruraisNaRota(int $ano, array $rotas): int
    {
        if ($rotas === []) {
            return 0;
        }

        return (int) DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->whereIn('ar.rota_id', $rotas)
            ->where('ar.ano', $ano)
            ->where('f.zona_localizacao_censo', 2)
            ->distinct()
            ->count('ar.ref_cod_aluno');
    }

    private static function ruraisMatriculados(int $ano): int
    {
        return (int) RedeConsulta::matriculasAtivas($ano)
            ->where('f.zona_localizacao_censo', 2)
            ->distinct()
            ->count('a.cod_aluno');
    }

    private static function censoSemRota(int $ano): int
    {
        return (int) RedeConsulta::matriculasAtivas($ano)
            ->where(function ($q) {
                $q->where('a.utiliza_transporte_rural', true)->orWhereNotNull('a.veiculo_transporte_escolar');
            })
            ->whereNotExists(function ($q) use ($ano) {
                $q->select(DB::raw('1'))->from('transporte_escolar.aluno_rota as ar')->whereColumn('ar.ref_cod_aluno', 'a.cod_aluno')->where('ar.ano', $ano);
            })
            ->distinct()
            ->count('a.cod_aluno');
    }

    /**
     * @param list<int> $rotas
     */
    private static function rotaSemCenso(int $ano, array $rotas): int
    {
        if ($rotas === []) {
            return 0;
        }

        return (int) DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->whereIn('ar.rota_id', $rotas)
            ->where('ar.ano', $ano)
            ->where(function ($q) {
                $q->whereNull('a.utiliza_transporte_rural')->orWhere('a.utiliza_transporte_rural', false);
            })
            ->whereNull('a.veiculo_transporte_escolar')
            ->distinct()
            ->count('ar.ref_cod_aluno');
    }

    /**
     * @param list<int> $rotas
     * @return list<array{placa: string, litros: float, medio: ?float, alerta: bool}>
     */
    private static function consumo(int $ano, array $rotas): array
    {
        if ($rotas === [] || !DB::getSchemaBuilder()->hasTable('transporte_escolar.abastecimento')) {
            return [];
        }

        $veiculos = DB::table('transporte_escolar.viagem')
            ->whereIn('rota_id', $rotas)
            ->whereYear('data', $ano)
            ->whereNotNull('veiculo_id')
            ->distinct()
            ->pluck('veiculo_id');

        if ($veiculos->isEmpty()) {
            return [];
        }

        $linhas = [];

        foreach (DB::table('transporte_escolar.veiculo')->whereIn('id', $veiculos)->orderBy('placa')->get() as $veiculo) {
            $km = 0.0;
            foreach (DB::table('transporte_escolar.viagem')->where('veiculo_id', $veiculo->id)->whereYear('data', $ano)->get() as $viagem) {
                $km += KmRealizado::deOdometro(
                    $viagem->km_inicial === null ? null : (float) $viagem->km_inicial,
                    $viagem->km_final === null ? null : (float) $viagem->km_final,
                ) ?? 0;
            }
            $litros = (float) DB::table('transporte_escolar.abastecimento')->where('veiculo_id', $veiculo->id)->whereYear('data', $ano)->sum('litros');
            $porCem = IndicadorRede::litrosPorCem($litros, $km);
            $real = ConsumoReal::kmPorLitro($km, $litros);
            $medio = $veiculo->consumo_medio_km_l === null ? null : (float) $veiculo->consumo_medio_km_l;
            $linhas[] = [
                'placa' => (string) ($veiculo->placa ?: $veiculo->descricao),
                'litros' => $porCem,
                'medio' => $medio,
                'alerta' => ConsumoReal::piorQueOMedio($real, $medio),
            ];
        }

        return $linhas;
    }

    /**
     * @param list<int> $rotas
     * @return array{em_dia: int, com_plano: int}
     */
    private static function preventiva(array $rotas): array
    {
        if ($rotas === [] || !DB::getSchemaBuilder()->hasTable('transporte_escolar.manutencao_plano')) {
            return ['em_dia' => 0, 'com_plano' => 0];
        }

        $veiculos = DB::table('transporte_escolar.itinerario')->whereIn('rota_id', $rotas)->whereNotNull('veiculo_id')->distinct()->pluck('veiculo_id');
        $emDia = 0;
        $comPlano = 0;
        $hoje = now()->toDateString();

        foreach ($veiculos as $veiculoId) {
            $planos = DB::table('transporte_escolar.manutencao_plano')->where('veiculo_id', $veiculoId)->get();

            if ($planos->isEmpty()) {
                continue;
            }

            $comPlano++;
            $kmAtual = (float) DB::table('transporte_escolar.viagem')->where('veiculo_id', $veiculoId)->max('km_final');
            $ok = true;

            foreach ($planos as $plano) {
                $estado = IndicadorRede::planoEmDia(
                    $plano->ultimo_km === null ? null : (float) $plano->ultimo_km,
                    $plano->a_cada_km === null ? null : (int) $plano->a_cada_km,
                    $plano->ultima_data === null ? null : (string) $plano->ultima_data,
                    $plano->a_cada_dias === null ? null : (int) $plano->a_cada_dias,
                    $kmAtual,
                    $hoje,
                );

                if ($estado === false) {
                    $ok = false;
                }
            }

            if ($ok) {
                $emDia++;
            }
        }

        return ['em_dia' => $emDia, 'com_plano' => $comPlano];
    }

    /**
     * @param list<int> $rotas
     */
    private static function diasComViagem(int $ano, array $rotas): int
    {
        if ($rotas === []) {
            return 0;
        }

        return (int) DB::table('transporte_escolar.viagem')->whereIn('rota_id', $rotas)->whereYear('data', $ano)->distinct()->count('data');
    }

    private static function diasLetivos(int $ano): int
    {
        $tem = DB::table('pmieducar.calendario_ano_letivo')->where('ano', $ano)->where('ativo', 1)->whereNull('data_exclusao')->exists();

        if (!$tem) {
            $total = 0;

            for ($mes = 1; $mes <= 12; $mes++) {
                $total += DiasUteis::contar($ano, $mes);
            }

            return $total;
        }

        return (int) DB::table('pmieducar.calendario_dia as d')
            ->join('pmieducar.calendario_ano_letivo as a', 'a.cod_calendario_ano_letivo', '=', 'd.ref_cod_calendario_ano_letivo')
            ->leftJoin('pmieducar.calendario_dia_motivo as m', 'm.cod_calendario_dia_motivo', '=', 'd.ref_cod_calendario_dia_motivo')
            ->where('a.ano', $ano)
            ->where('d.ativo', 1)
            ->whereNull('d.data_exclusao')
            ->where(function ($q) {
                $q->whereNull('m.tipo')->orWhere('m.tipo', 'e');
            })
            ->selectRaw("count(distinct (d.mes::text || '-' || d.dia::text)) as dias")
            ->value('dias');
    }

    /**
     * @param list<int> $rotas
     * @return list<object>
     */
    private static function cruzamento(int $ano, array $rotas)
    {
        if ($rotas === []) {
            return [];
        }

        return DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->join('transporte_escolar.rota_escola as re', 're.rota_id', '=', 'r.id')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 're.ref_cod_escola')
            ->join('cadastro.pessoa as pe', 'pe.idpes', '=', 'e.ref_idpes')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->whereIn('ar.rota_id', $rotas)
            ->where('ar.ano', $ano)
            ->groupBy('pe.nome', 'r.descricao', 'ar.turno', 'f.zona_localizacao_censo')
            ->orderBy('pe.nome')
            ->orderBy('r.descricao')
            ->get([
                'pe.nome as escola',
                'r.descricao as rota',
                'ar.turno',
                'f.zona_localizacao_censo as zona',
                DB::raw('count(distinct ar.ref_cod_aluno) as alunos'),
            ])
            ->all();
    }

    /**
     * @param list<int> $rotas
     * @return list<object>
     */
    private static function vagas(int $ano, array $rotas)
    {
        if ($rotas === [] || !DB::getSchemaBuilder()->hasTable('transporte_escolar.processo_vaga_oferta')) {
            return [];
        }

        return DB::table('transporte_escolar.processo_vaga_oferta as o')
            ->join('transporte_escolar.processo_vaga as p', 'p.id', '=', 'o.processo_id')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'o.rota_id')
            ->where('p.ano', $ano)
            ->whereIn('o.rota_id', $rotas)
            ->orderBy('r.descricao')
            ->get([
                'r.descricao as rota',
                'o.turno',
                'o.vagas',
                DB::raw('(select count(distinct ref_cod_aluno) from transporte_escolar.aluno_rota ar where ar.rota_id = o.rota_id and ar.ano = p.ano) as alunos'),
            ])
            ->all();
    }
}
