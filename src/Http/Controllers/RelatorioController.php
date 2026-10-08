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
use Symfony\Component\HttpFoundation\Response;

class RelatorioController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = (int) $request->input('mes', date('n'));
        $mes = $mes < 1 || $mes > 12 ? (int) date('n') : $mes;
        $dados = $this->dados($ano);
        $podeCusto = Acesso::permite(Processos::GASTOS, 'ver');
        $custo = $podeCusto ? CustoMensal::fechar($ano, $mes) : null;
        $totalGasto = $custo === null ? 0.0 : $custo['pnate'] + $custo['proprio'] + $custo['outros'];

        return view('transporte-escolar::relatorios.index', $dados + [
            'ano' => $ano,
            'mes' => $mes,
            'podeCusto' => $podeCusto,
            'custo' => $custo,
            'alertaSaldo' => $podeCusto ? CustoMensal::alertaSaldo($ano) : null,
            'custoAluno' => $dados['alunos'] > 0 ? round($totalGasto / $dados['alunos'], 2) : 0.0,
            'custoKm' => $dados['realizado']['total'] > 0 ? round($totalGasto / $dados['realizado']['total'], 2) : 0.0,
            'fontes' => ['pnate' => 'PNATE', 'proprio' => 'Recurso próprio', 'outros' => 'Outras fontes'],
        ]);
    }

    public function csv(Request $request): Response
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = (int) $request->input('mes', date('n'));
        $mes = $mes < 1 || $mes > 12 ? (int) date('n') : $mes;
        $planilha = (string) $request->input('planilha', 'resumo');
        $dados = $this->dados($ano);

        if ($planilha === 'custo') {
            abort_unless(Acesso::permite(Processos::GASTOS, 'ver'), 403);
        }

        $saida = fopen('php://temp', 'w+');
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $this->escreverPlanilha($saida, $planilha, $dados, $ano, $mes);
        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transporte-' . $planilha . '-' . $ano . '.csv"',
        ]);
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
        $viagens = $ids === [] ? 0 : DB::table('transporte_escolar.viagem')->whereIn('rota_id', $ids)->whereYear('data', $ano)->count();
        $frota = $this->frotaVisivel($ids, $ano);

        return [
            'rotas' => $rotas,
            'km' => ResumoNumerico::de($kms),
            'tempo' => ResumoNumerico::de($tempos),
            'realizado' => ResumoNumerico::de($realizados),
            'alunos' => $alunosRota,
            'embarcados' => $embarcados,
            'viagens' => $viagens,
            'motoristas' => $this->motoristas($frota['motoristas']),
            'vencimentos' => $this->vencimentos($frota['veiculos']),
            'censoSemRota' => $this->censoSemRota($ano),
            'rotaSemCenso' => $this->rotaSemCenso($ano, $ids),
            'porRota' => $this->porRota($rotas, $ano),
            'parados' => $this->parados($frota['veiculos']),
            'consumo' => $this->consumo($ano, $frota['veiculos']),
        ];
    }

    /**
     * @param list<int> $rotaIds
     * @return array{veiculos: list<int>|null, motoristas: list<int>|null}
     */
    private function frotaVisivel(array $rotaIds, int $ano): array
    {
        if (\iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede::escolas() === null) {
            return ['veiculos' => null, 'motoristas' => null];
        }

        if ($rotaIds === []) {
            return ['veiculos' => [], 'motoristas' => []];
        }

        $viagens = DB::table('transporte_escolar.viagem')->whereIn('rota_id', $rotaIds)->whereYear('data', $ano);

        return [
            'veiculos' => (clone $viagens)->whereNotNull('veiculo_id')->distinct()->pluck('veiculo_id')->map(fn ($id) => (int) $id)->all(),
            'motoristas' => (clone $viagens)->whereNotNull('motorista_id')->distinct()->pluck('motorista_id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /**
     * @param list<int>|null $ids
     */
    private function motoristas(?array $ids)
    {
        $consulta = DB::table('transporte_escolar.motorista as m')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->orderBy('p.nome');

        if (is_array($ids)) {
            $consulta->whereIn('m.id', $ids === [] ? [0] : $ids);
        }

        return $consulta->get(['p.nome', 'm.cnh', 'm.vencimento_cnh']);
    }

    /**
     * @param list<int>|null $ids
     */
    private function vencimentos(?array $ids)
    {
        $consulta = DB::table('transporte_escolar.veiculo')
            ->where('ativo', true)
            ->whereNotNull('licenciamento_vencimento')
            ->where('licenciamento_vencimento', '<=', now()->addDays(30)->toDateString())
            ->orderBy('licenciamento_vencimento');

        if (is_array($ids)) {
            $consulta->whereIn('id', $ids === [] ? [0] : $ids);
        }

        return $consulta->limit(30)->get();
    }

    /**
     * @param resource $saida
     * @param array<string, mixed> $dados
     */
    private function escreverPlanilha($saida, string $planilha, array $dados, int $ano, int $mes): void
    {
        if ($planilha === 'rotas') {
            fputcsv($saida, ['rota', 'tipo', 'km_planejado', 'km_realizado', 'alunos', 'embarcados'], ';');

            foreach ($dados['porRota'] as $rota) {
                fputcsv($saida, [$rota['descricao'], $rota['tipo'], $rota['planejado'], $rota['realizado'], $rota['alunos'], $rota['embarcados']], ';');
            }

            return;
        }

        if ($planilha === 'motoristas') {
            fputcsv($saida, ['nome', 'cnh', 'vencimento'], ';');

            foreach ($dados['motoristas'] as $motorista) {
                fputcsv($saida, [$motorista->nome, $motorista->cnh, $motorista->vencimento_cnh], ';');
            }

            return;
        }

        if ($planilha === 'censo') {
            fputcsv($saida, ['situacao', 'aluno'], ';');

            foreach ($dados['censoSemRota'] as $aluno) {
                fputcsv($saida, ['ficha com transporte e sem rota', $aluno->nome], ';');
            }

            foreach ($dados['rotaSemCenso'] as $aluno) {
                fputcsv($saida, ['rota sem transporte na ficha', $aluno->nome], ';');
            }

            return;
        }

        if ($planilha === 'consumo') {
            fputcsv($saida, ['veiculo', 'km_por_litro', 'medio_cadastrado', 'alerta'], ';');

            foreach ($dados['consumo'] as $item) {
                fputcsv($saida, [$item['placa'], $item['real'], $item['medio'], $item['alerta'] ? 'sim' : 'nao'], ';');
            }

            return;
        }

        if ($planilha === 'custo') {
            $custo = CustoMensal::fechar($ano, $mes);
            fputcsv($saida, ['mes', 'rotulo', 'fonte', 'valor'], ';');

            foreach ($custo['linhas'] as $linha) {
                fputcsv($saida, [$mes, $linha['rotulo'], $linha['fonte'], number_format($linha['valor'], 2, '.', '')], ';');
            }

            fputcsv($saida, [$mes, 'total PNATE', 'pnate', number_format($custo['pnate'], 2, '.', '')], ';');
            fputcsv($saida, [$mes, 'total recurso proprio', 'proprio', number_format($custo['proprio'], 2, '.', '')], ';');
            fputcsv($saida, [$mes, 'total outras fontes', 'outros', number_format($custo['outros'], 2, '.', '')], ';');

            return;
        }

        fputcsv($saida, ['indicador', 'minimo', 'media', 'maximo', 'total'], ';');
        fputcsv($saida, ['km informado', $dados['km']['minimo'], $dados['km']['media'], $dados['km']['maximo'], $dados['km']['total']], ';');
        fputcsv($saida, ['tempo estimado min', $dados['tempo']['minimo'], $dados['tempo']['media'], $dados['tempo']['maximo'], $dados['tempo']['total']], ';');
        fputcsv($saida, ['km realizado', $dados['realizado']['minimo'], $dados['realizado']['media'], $dados['realizado']['maximo'], $dados['realizado']['total']], ';');
        fputcsv($saida, ['alunos na lista', '', '', '', $dados['alunos']], ';');
        fputcsv($saida, ['alunos com embarque', '', '', '', $dados['embarcados']], ';');
        fputcsv($saida, ['viagens', '', '', '', $dados['viagens']], ';');
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
                'tipo' => \iEducar\Packages\BuritiTransporteEscolar\Support\Codigos::TIPO_ROTA[$rota->tipo_rota] ?? $rota->tipo_rota,
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

    /**
     * @param list<int>|null $ids
     */
    private function parados(?array $ids)
    {
        $limite = now()->subDays(30)->toDateString();
        $consulta = DB::table('transporte_escolar.veiculo as ve')
            ->where('ve.ativo', true)
            ->whereNotExists(function ($q) use ($limite) {
                $q->select(DB::raw('1'))
                    ->from('transporte_escolar.viagem as v')
                    ->whereColumn('v.veiculo_id', 've.id')
                    ->where('v.data', '>=', $limite);
            })
            ->orderBy('ve.placa');

        if (is_array($ids)) {
            $consulta->whereIn('ve.id', $ids === [] ? [0] : $ids);
        }

        return $consulta->limit(30)->get(['ve.placa', 've.descricao']);
    }

    /**
     * @param list<int>|null $ids
     */
    private function consumo(int $ano, ?array $ids): array
    {
        $linhas = [];
        $consulta = DB::table('transporte_escolar.veiculo')->where('ativo', true);

        if (is_array($ids)) {
            $consulta->whereIn('id', $ids === [] ? [0] : $ids);
        }

        $veiculos = $consulta->get();

        foreach ($veiculos as $veiculo) {
            $abastecimentos = DB::table('transporte_escolar.abastecimento')->where('veiculo_id', $veiculo->id)->whereYear('data', $ano)->get()
                ->filter(fn ($item) => \iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento::contaNoCusto('bilhete', $item->situacao ?? null));

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
