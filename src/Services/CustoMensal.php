<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\DiasUteis;
use iEducar\Packages\BuritiTransporteEscolar\Support\Encargo;
use iEducar\Packages\BuritiTransporteEscolar\Support\MedicaoContrato;
use iEducar\Packages\BuritiTransporteEscolar\Support\RateioSalario;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento;
use Illuminate\Support\Facades\DB;

final class CustoMensal
{
    /**
     * @return array{pnate: float, proprio: float, outros: float, salario: float, linhas: list<array{rotulo: string, fonte: string, valor: float}>}
     */
    public static function fechar(int $ano, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));
        $linhas = [];

        foreach (DB::table('transporte_escolar.abastecimento')->whereBetween('data', [$inicio, $fim])->get() as $item) {
            if (!SituacaoDocumento::contaNoCusto('bilhete', property_exists($item, 'situacao') ? $item->situacao : null)) {
                continue;
            }

            $linhas[] = ['rotulo' => 'Combustível', 'fonte' => (string) $item->fonte, 'valor' => (float) $item->valor];
        }

        foreach (DB::table('transporte_escolar.ordem_servico')->whereBetween('data', [$inicio, $fim])->get() as $item) {
            if (!SituacaoDocumento::contaNoCusto('ordem', property_exists($item, 'situacao') ? $item->situacao : null)) {
                continue;
            }

            $linhas[] = ['rotulo' => $item->preventiva ? 'Manutenção preventiva' : 'Manutenção corretiva', 'fonte' => (string) $item->fonte, 'valor' => (float) $item->valor];
        }

        foreach (DB::table('transporte_escolar.gasto')->whereBetween('data', [$inicio, $fim])->get() as $item) {
            $linhas[] = ['rotulo' => (string) $item->categoria, 'fonte' => (string) $item->fonte, 'valor' => (float) $item->valor];
        }

        $diasUteis = DiasUteis::contar($ano, $mes);

        foreach (DB::table('transporte_escolar.contrato')->where('inicio', '<=', $fim)->where(function ($q) use ($inicio) {
            $q->whereNull('fim')->orWhere('fim', '>=', $inicio);
        })->get() as $item) {
            $dias = (int) DB::table('transporte_escolar.viagem as v')
                ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
                ->where('r.empresa_id', $item->empresa_id)
                ->whereBetween('v.data', [$inicio, $fim])
                ->selectRaw('count(distinct v.data) as dias')
                ->value('dias');
            $linhas[] = [
                'rotulo' => 'Contrato ' . $item->descricao,
                'fonte' => 'pnate',
                'valor' => MedicaoContrato::valor((float) $item->valor_mensal, $dias, $diasUteis),
            ];
        }

        $kmTotal = self::kmRealizado($inicio, $fim);
        $salario = 0.0;

        foreach (DB::table('transporte_escolar.motorista')->get(['salario_mensal', 'encargo_percent']) as $pessoa) {
            $salario += Encargo::compor((float) $pessoa->salario_mensal, (float) ($pessoa->encargo_percent ?? 0));
        }

        foreach (DB::table('transporte_escolar.monitor')->get(['salario_mensal', 'encargo_percent']) as $pessoa) {
            $salario += Encargo::compor((float) $pessoa->salario_mensal, (float) ($pessoa->encargo_percent ?? 0));
        }

        if ($salario > 0) {
            $porAlunos = DB::table('transporte_escolar.configuracao')->where('chave', 'rateio')->value('valor') === 'alunos';
            $alunos = (int) DB::table('transporte_escolar.embarque')
                ->where('tipo', 'embarque')
                ->whereBetween('ocorrido_em', [$inicio . ' 00:00:00', $fim . ' 23:59:59'])
                ->distinct()
                ->count('ref_cod_aluno');
            $linhas[] = [
                'rotulo' => $porAlunos ? 'Salário rateado pelos alunos embarcados' : 'Salário rateado pelo km realizado',
                'fonte' => 'proprio',
                'valor' => $porAlunos
                    ? RateioSalario::porAlunos($salario, $alunos, $alunos)
                    : RateioSalario::porKm($salario, $kmTotal, $kmTotal),
            ];
        }

        $totais = ['pnate' => 0.0, 'proprio' => 0.0, 'outros' => 0.0, 'salario' => 0.0, 'linhas' => $linhas];

        foreach ($linhas as $linha) {
            if ($linha['rotulo'] === 'Salário rateado pelo km realizado') {
                $totais['salario'] += $linha['valor'];
            }

            if ($linha['fonte'] === 'pnate') {
                $totais['pnate'] += $linha['valor'];
            } elseif ($linha['fonte'] === 'proprio') {
                $totais['proprio'] += $linha['valor'];
            } else {
                $totais['outros'] += $linha['valor'];
            }
        }

        return $totais;
    }

    public static function alertaSaldo(int $ano): ?string
    {
        $repassado = (float) DB::table('transporte_escolar.parcela_recurso')->where('ano', $ano)->where('fonte', 'pnate')->sum('valor');
        $saldo = DB::table('transporte_escolar.parcela_recurso')->where('ano', $ano)->where('mes', 12)->where('fonte', 'pnate')->value('saldo_informado');

        if ($repassado <= 0 || $saldo === null) {
            return null;
        }

        if ((float) $saldo > ($repassado * 0.3)) {
            return 'O saldo informado em dezembro passa de 30% do repasse do ano. O art. 9º da Resolução 18/2021 permite desconto no ano seguinte.';
        }

        return null;
    }

    private static function kmRealizado(string $inicio, string $fim): float
    {
        $total = 0.0;

        foreach (DB::table('transporte_escolar.viagem')->whereBetween('data', [$inicio, $fim])->get() as $viagem) {
            if ($viagem->km_inicial === null || $viagem->km_final === null) {
                continue;
            }

            $trecho = (float) $viagem->km_final - (float) $viagem->km_inicial;

            if ($trecho > 0) {
                $total += $trecho;
            }
        }

        return $total;
    }
}
