<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal;
use iEducar\Packages\BuritiTransporteEscolar\Services\IndicadoresLeitura;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CacsController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = $this->mes($request);

        return view('transporte-escolar::cacs', $this->painel($ano, $mes));
    }

    public function csv(Request $request): Response
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = $this->mes($request);
        $planilha = (string) $request->input('planilha', 'resumo');
        $dados = $this->painel($ano, $mes);
        $saida = fopen('php://temp', 'w+');
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $this->escrever($saida, $planilha, $dados, $mes);
        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cacs-' . $planilha . '-' . $ano . '.csv"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function painel(int $ano, int $mes): array
    {
        $rotas = DB::table('transporte_escolar.rota as r')
            ->leftJoin('transporte_escolar.aluno_rota as ar', function ($join) use ($ano) {
                $join->on('ar.rota_id', '=', 'r.id')->where('ar.ano', $ano);
            })
            ->where('r.ano', $ano)
            ->groupBy('r.id', 'r.descricao', 'r.tipo_rota', 'r.km_pav', 'r.km_npav')
            ->orderBy('r.descricao')
            ->get([
                'r.id',
                'r.descricao',
                'r.tipo_rota',
                'r.km_pav',
                'r.km_npav',
                DB::raw('count(distinct ar.ref_cod_aluno) as alunos'),
            ]);
        $ids = $rotas->pluck('id')->all();
        $realizado = $this->kmPorRota($ids, $ano);
        $linhas = $rotas->map(function ($rota) use ($realizado) {
            $rota->planejado = (float) $rota->km_pav + (float) $rota->km_npav;
            $rota->realizado = $realizado[$rota->id] ?? 0.0;
            $rota->rural = $rota->tipo_rota === 'R';

            return $rota;
        });
        $rurais = $linhas->where('rural', true);
        $hoje = now()->toDateString();
        $frota = DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(['placa', 'descricao', 'licenciamento_vencimento']);
        $motoristas = DB::table('transporte_escolar.motorista as m')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->orderBy('p.nome')
            ->get(['p.nome', 'm.cnh', 'm.vencimento_cnh']);
        $custo = CustoMensal::fechar($ano, $mes);

        return [
            'ano' => $ano,
            'mes' => $mes,
            'rotas' => $linhas,
            'rurais' => (int) $rurais->sum('alunos'),
            'alunos' => (int) $linhas->sum('alunos'),
            'kmRural' => round((float) $rurais->sum('realizado'), 1),
            'kmTotal' => round((float) $linhas->sum('realizado'), 1),
            'frota' => $frota,
            'vencidos' => $frota->filter(fn ($veiculo) => $veiculo->licenciamento_vencimento !== null && $veiculo->licenciamento_vencimento < $hoje)->count(),
            'motoristas' => $motoristas,
            'cnh' => $motoristas->filter(fn ($motorista) => $motorista->vencimento_cnh !== null && $motorista->vencimento_cnh < $hoje)->count(),
            'custo' => $custo,
            'categorias' => $this->categorias($custo['linhas']),
            'alertaSaldo' => CustoMensal::alertaSaldo($ano),
            'fontes' => ['pnate' => 'PNATE', 'proprio' => 'Recurso próprio', 'outros' => 'Outras fontes'],
            'indicadores' => IndicadoresLeitura::montar($ano, $mes, $ids, true),
        ];
    }

    /**
     * @param list<int> $ids
     * @return array<int, float>
     */
    private function kmPorRota(array $ids, int $ano): array
    {
        $totais = [];

        if ($ids === []) {
            return $totais;
        }

        foreach (DB::table('transporte_escolar.viagem')->whereIn('rota_id', $ids)->whereYear('data', $ano)->get() as $viagem) {
            $km = KmRealizado::deOdometro(
                $viagem->km_inicial === null ? null : (float) $viagem->km_inicial,
                $viagem->km_final === null ? null : (float) $viagem->km_final,
            );
            $totais[$viagem->rota_id] = ($totais[$viagem->rota_id] ?? 0) + ($km ?? 0);
        }

        return $totais;
    }

    /**
     * @param list<array{rotulo: string, fonte: string, valor: float}> $linhas
     * @return list<array{rotulo: string, fonte: string, valor: float}>
     */
    private function categorias(array $linhas): array
    {
        $grupos = [];

        foreach ($linhas as $linha) {
            $chave = $linha['rotulo'] . '|' . $linha['fonte'];

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['rotulo' => $linha['rotulo'], 'fonte' => $linha['fonte'], 'valor' => 0.0];
            }

            $grupos[$chave]['valor'] += $linha['valor'];
        }

        return array_values($grupos);
    }

    /**
     * @param resource $saida
     * @param array<string, mixed> $dados
     */
    private function escrever($saida, string $planilha, array $dados, int $mes): void
    {
        if ($planilha === 'rotas') {
            fputcsv($saida, ['rota', 'tipo', 'alunos', 'km_planejado', 'km_realizado'], ';');

            foreach ($dados['rotas'] as $rota) {
                fputcsv($saida, [$rota->descricao, $rota->rural ? 'Rural' : 'Urbana', $rota->alunos, $rota->planejado, $rota->realizado], ';');
            }

            return;
        }

        if ($planilha === 'frota') {
            fputcsv($saida, ['placa', 'descricao', 'licenciamento'], ';');

            foreach ($dados['frota'] as $veiculo) {
                fputcsv($saida, [$veiculo->placa, $veiculo->descricao, $veiculo->licenciamento_vencimento], ';');
            }

            return;
        }

        if ($planilha === 'cnh') {
            fputcsv($saida, ['nome', 'cnh', 'vencimento'], ';');

            foreach ($dados['motoristas'] as $motorista) {
                fputcsv($saida, [$motorista->nome, $motorista->cnh, $motorista->vencimento_cnh], ';');
            }

            return;
        }

        if ($planilha === 'custo') {
            fputcsv($saida, ['mes', 'categoria', 'fonte', 'valor'], ';');

            foreach ($dados['categorias'] as $linha) {
                fputcsv($saida, [$mes, $linha['rotulo'], $linha['fonte'], number_format($linha['valor'], 2, '.', '')], ';');
            }

            return;
        }

        fputcsv($saida, ['indicador', 'valor'], ';');
        fputcsv($saida, ['alunos em rota rural', $dados['rurais']], ';');
        fputcsv($saida, ['alunos na lista', $dados['alunos']], ';');
        fputcsv($saida, ['veiculos ativos', $dados['frota']->count()], ';');
        fputcsv($saida, ['licencas vencidas', $dados['vencidos']], ';');
        fputcsv($saida, ['cnh vencidas', $dados['cnh']], ';');
        fputcsv($saida, ['km realizado rural', $dados['kmRural']], ';');
        fputcsv($saida, ['km realizado', $dados['kmTotal']], ';');
        fputcsv($saida, ['pnate do mes', number_format($dados['custo']['pnate'], 2, '.', '')], ';');
        fputcsv($saida, ['recurso proprio do mes', number_format($dados['custo']['proprio'], 2, '.', '')], ';');
    }

    private function mes(Request $request): int
    {
        $mes = (int) $request->input('mes', date('n'));

        return $mes < 1 || $mes > 12 ? (int) date('n') : $mes;
    }
}
