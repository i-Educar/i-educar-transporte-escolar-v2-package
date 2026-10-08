<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportadorSete
{
    public static function csv(string $entidade): StreamedResponse
    {
        $nome = 'transporte-' . $entidade . '.csv';

        return response()->streamDownload(function () use ($entidade): void {
            $saida = fopen('php://output', 'w');
            fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($entidade === 'veiculos') {
                fputcsv($saida, ['placa', 'descricao', 'renavam', 'passageiros', 'consumo', 'pneus', 'caminho da escola'], ';');
                foreach (DB::table('transporte_escolar.veiculo')->orderBy('placa')->get() as $item) {
                    fputcsv($saida, [$item->placa, $item->descricao, $item->renavam, $item->passageiros, $item->consumo_medio_km_l, $item->qtd_pneus, $item->caminho_da_escola ? 'sim' : 'nao'], ';');
                }
            } elseif ($entidade === 'motoristas') {
                fputcsv($saida, ['nome', 'cpf', 'cnh', 'tipo cnh', 'turno', 'salario'], ';');
                foreach (self::pessoas('motorista') as $item) {
                    fputcsv($saida, [$item->nome, $item->cpf, $item->cnh, $item->tipo_cnh, $item->turno, $item->salario_mensal], ';');
                }
            } elseif ($entidade === 'monitores') {
                fputcsv($saida, ['nome', 'cpf', 'turno', 'salario'], ';');
                foreach (self::pessoas('monitor') as $item) {
                    fputcsv($saida, [$item->nome, $item->cpf, $item->turno, $item->salario_mensal], ';');
                }
            } else {
                fputcsv($saida, ['aluno', 'cpf', 'escola', 'rota', 'ano', 'turno', 'tipo rota'], ';');
                $linhas = DB::table('transporte_escolar.aluno_rota as ar')
                    ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
                    ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
                    ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                    ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
                    ->leftJoin('transporte_escolar.rota_escola as re', 're.rota_id', '=', 'r.id')
                    ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 're.ref_cod_escola')
                    ->leftJoin('cadastro.pessoa as pe', 'pe.idpes', '=', 'e.ref_idpes')
                    ->orderBy('r.ano')
                    ->orderBy('p.nome')
                    ->get(['p.nome', 'f.cpf', 'pe.nome as escola', 'r.descricao', 'r.ano', 'ar.turno', 'r.tipo_rota']);

                foreach ($linhas as $item) {
                    fputcsv($saida, [$item->nome, $item->cpf, $item->escola, $item->descricao, $item->ano, $item->turno, $item->tipo_rota], ';');
                }
            }

            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function pessoas(string $tabela)
    {
        return DB::table('transporte_escolar.' . $tabela . ' as t')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 't.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 't.ref_idpes')
            ->orderBy('p.nome')
            ->get();
    }
}
