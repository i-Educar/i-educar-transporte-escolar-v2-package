<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\ConsumoReal;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ResumoNumerico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $dados = $this->dados($ano);
        $custo = Acesso::permite(Processos::GASTOS, 'ver') ? CustoMensal::fechar($ano, (int) date('n')) : null;
        $totalGasto = $custo === null ? 0.0 : $custo['pnate'] + $custo['proprio'] + $custo['outros'];

        return view('transporte-escolar::relatorios.index', $dados + [
            'ano' => $ano,
            'podeCusto' => $custo !== null,
            'custo' => $custo,
            'custoAluno' => $dados['alunos'] > 0 ? round($totalGasto / $dados['alunos'], 2) : 0.0,
            'custoKm' => $dados['realizado']['total'] > 0 ? round($totalGasto / $dados['realizado']['total'], 2) : 0.0,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $ano = (int) $request->input('ano', date('Y'));
        $dados = $this->dados($ano);

        return response()->streamDownload(function () use ($dados): void {
            $saida = fopen('php://output', 'w');
            fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($saida, ['indicador', 'minimo', 'media', 'maximo', 'total'], ';');
            fputcsv($saida, ['km informado', $dados['km']['minimo'], $dados['km']['media'], $dados['km']['maximo'], $dados['km']['total']], ';');
            fputcsv($saida, ['tempo estimado min', $dados['tempo']['minimo'], $dados['tempo']['media'], $dados['tempo']['maximo'], $dados['tempo']['total']], ';');
            fputcsv($saida, ['km realizado', $dados['realizado']['minimo'], $dados['realizado']['media'], $dados['realizado']['maximo'], $dados['realizado']['total']], ';');
            fclose($saida);
        }, 'transporte-resumo.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function dossie(Request $request): View
    {
        abort_unless(Acesso::permite(Processos::GASTOS, 'ver'), 403);
        $ano = (int) $request->input('ano', date('Y'));
        $mes = (int) $request->input('mes', date('n'));

        return view('transporte-escolar::relatorios.dossie', [
            'ano' => $ano,
            'mes' => $mes,
            'custo' => CustoMensal::fechar($ano, $mes),
            'comprovantes' => DB::table('transporte_escolar.comprovante as c')
                ->join('transporte_escolar.gasto as g', 'g.id', '=', 'c.gasto_id')
                ->whereYear('g.data', $ano)
                ->whereMonth('g.data', $mes)
                ->get(['c.id', 'c.nome', 'g.descricao', 'g.valor']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dados(int $ano): array
    {
        $rotas = RedeConsulta::rotas($ano)->get();
        $ids = $rotas->pluck('id')->all();
        $kms = [];
        $tempos = [];

        foreach ($rotas as $rota) {
            $kms[] = (float) $rota->km_pav + (float) $rota->km_npav;
            $tempos[] = (float) ($rota->tempo_estimado_min ?? 0);
        }

        $realizados = [];

        if ($ids !== []) {
            foreach (DB::table('transporte_escolar.viagem')->whereIn('rota_id', $ids)->whereYear('data', $ano)->get() as $viagem) {
                $km = KmRealizado::deOdometro(
                    $viagem->km_inicial === null ? null : (float) $viagem->km_inicial,
                    $viagem->km_final === null ? null : (float) $viagem->km_final,
                );

                if ($km !== null) {
                    $realizados[] = $km;
                }
            }
        }

        $alunosRota = $ids === [] ? 0 : DB::table('transporte_escolar.aluno_rota')->whereIn('rota_id', $ids)->distinct()->count('ref_cod_aluno');
        $embarcados = $ids === [] ? 0 : DB::table('transporte_escolar.embarque as e')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->whereIn('v.rota_id', $ids)
            ->where('e.tipo', 'embarque')
            ->distinct()
            ->count('e.ref_cod_aluno');

        return [
            'rotas' => $rotas,
            'km' => ResumoNumerico::de($kms),
            'tempo' => ResumoNumerico::de($tempos),
            'realizado' => ResumoNumerico::de($realizados),
            'alunos' => $alunosRota,
            'embarcados' => $embarcados,
            'motoristas' => DB::table('transporte_escolar.motorista as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['p.nome', 'm.cnh', 'm.vencimento_cnh']),
            'vencimentos' => DB::table('transporte_escolar.veiculo')
                ->where('ativo', true)
                ->whereNotNull('licenciamento_vencimento')
                ->where('licenciamento_vencimento', '<=', now()->addDays(30)->toDateString())
                ->limit(30)
                ->get(),
            'censoSemRota' => $this->censoSemRota($ano),
            'rotaSemCenso' => $this->rotaSemCenso($ano, $ids),
            'porRota' => $this->porRota($rotas, $ano),
            'parados' => $this->parados(),
            'consumo' => $this->consumo($ano),
        ];
    }

    private function porRota($rotas, int $ano): array
    {
        $linhas = [];

        foreach ($rotas as $rota) {
            $kmReal = 0.0;

            foreach (DB::table('transporte_escolar.viagem')->where('rota_id', $rota->id)->whereYear('data', $ano)->get() as $viagem) {
                $km = KmRealizado::deOdometro(
                    $viagem->km_inicial === null ? null : (float) $viagem->km_inicial,
                    $viagem->km_final === null ? null : (float) $viagem->km_final,
                );
                $kmReal += $km ?? 0;
            }

            $linhas[] = [
                'descricao' => $rota->descricao,
                'planejado' => (float) $rota->km_pav + (float) $rota->km_npav,
                'realizado' => round($kmReal, 1),
                'alunos' => DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota->id)->count(),
                'embarcados' => DB::table('transporte_escolar.embarque as e')
                    ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
                    ->where('v.rota_id', $rota->id)
                    ->where('e.tipo', 'embarque')
                    ->distinct()
                    ->count('e.ref_cod_aluno'),
            ];
        }

        return $linhas;
    }

    private function parados()
    {
        $limite = now()->subDays(30)->toDateString();

        return DB::table('transporte_escolar.veiculo as ve')
            ->where('ve.ativo', true)
            ->whereNotExists(function ($q) use ($limite) {
                $q->select(DB::raw('1'))
                    ->from('transporte_escolar.viagem as v')
                    ->whereColumn('v.veiculo_id', 've.id')
                    ->where('v.data', '>=', $limite);
            })
            ->orderBy('ve.placa')
            ->limit(30)
            ->get(['ve.placa', 've.descricao']);
    }

    private function consumo(int $ano): array
    {
        $linhas = [];
        $veiculos = DB::table('transporte_escolar.veiculo')->where('ativo', true)->get();

        foreach ($veiculos as $veiculo) {
            $abastecimentos = DB::table('transporte_escolar.abastecimento')->where('veiculo_id', $veiculo->id)->whereYear('data', $ano)->get();

            if ($abastecimentos->isEmpty()) {
                continue;
            }

            $litros = (float) $abastecimentos->sum('litros');
            $kms = $abastecimentos->pluck('km')->filter(fn ($km) => $km !== null)->map(fn ($km) => (float) $km);
            $km = $kms->isEmpty() ? 0.0 : ($kms->max() - $kms->min());
            $real = ConsumoReal::kmPorLitro($km, $litros);
            $medio = $veiculo->consumo_medio_km_l === null ? null : (float) $veiculo->consumo_medio_km_l;
            $linhas[] = [
                'placa' => $veiculo->placa ?: $veiculo->descricao,
                'real' => $real,
                'medio' => $medio,
                'alerta' => ConsumoReal::piorQueOMedio($real, $medio),
            ];
        }

        return $linhas;
    }

    private function censoSemRota(int $ano)
    {
        $consulta = DB::table('pmieducar.aluno as a')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->join('pmieducar.matricula as m', 'm.ref_cod_aluno', '=', 'a.cod_aluno')
            ->where('m.ano', $ano)
            ->where('m.ativo', 1)
            ->where('a.tipo_transporte', '>', 0)
            ->whereNotExists(function ($q) use ($ano) {
                $q->select(DB::raw('1'))->from('transporte_escolar.aluno_rota as ar')->whereColumn('ar.ref_cod_aluno', 'a.cod_aluno')->where('ar.ano', $ano);
            });
        $escolas = \iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede::escolas();

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('m.ref_ref_cod_escola', $escolas);
        }

        return $consulta->distinct()->limit(50)->get(['a.cod_aluno', 'p.nome']);
    }

    private function rotaSemCenso(int $ano, array $ids)
    {
        if ($ids === []) {
            return collect();
        }

        return DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->where('ar.ano', $ano)
            ->whereIn('ar.rota_id', $ids)
            ->where('a.tipo_transporte', 0)
            ->limit(50)
            ->get(['a.cod_aluno', 'p.nome']);
    }
}
