<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\EmpenhoVerba;
use iEducar\Packages\BuritiTransporteEscolar\Support\PassagemControle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PassagemEmpenhoController
{
    public function index(Request $request): View
    {
        $movimentos = [
            ['tipo' => 'urbano', 'quantidade' => 100, 'sentido' => 'recebido', 'rede' => 'municipal'],
            ['tipo' => 'urbano', 'quantidade' => 40, 'sentido' => 'entregue', 'rede' => 'municipal'],
            ['tipo' => 'intermunicipal', 'quantidade' => 20, 'sentido' => 'recebido'],
            ['tipo' => 'intermunicipal', 'quantidade' => 5, 'sentido' => 'entregue', 'rede' => 'estadual'],
        ];
        $passagens = PassagemControle::consolidar($movimentos);

        $gastos = [];
        if (Schema::hasTable('transporte_escolar.gasto')) {
            $ano = (int) $request->query('ano', date('Y'));
            $mes = (int) $request->query('mes', date('n'));
            $inicio = sprintf('%04d-%02d-01', $ano, $mes);
            $fim = date('Y-m-t', strtotime($inicio));
            $rows = DB::table('transporte_escolar.gasto')
                ->whereBetween('data', [$inicio, $fim])
                ->orderByDesc('data')
                ->limit(100)
                ->get(['fonte', 'categoria', 'valor', 'descricao']);
            foreach ($rows as $r) {
                $gastos[] = [
                    'empenho' => 'EMP-'.substr(md5((string) $r->descricao), 0, 6),
                    'verba' => (string) ($r->fonte ?: 'PNATE'),
                    'fornecedor' => (string) ($r->categoria ?: 'geral'),
                    'valor' => (float) $r->valor,
                ];
            }
        }
        if ($gastos === []) {
            $gastos = [
                ['empenho' => '2026NE001', 'verba' => 'PNATE', 'fornecedor' => 'Posto Alfa', 'valor' => 1500.5],
                ['empenho' => '2026NE002', 'verba' => 'PNATE', 'fornecedor' => 'Oficina Beta', 'valor' => 820],
                ['empenho' => '2026RP010', 'verba' => 'Proprio', 'fornecedor' => 'Posto Alfa', 'valor' => 300],
            ];
        }

        $empenhos = EmpenhoVerba::agrupar($gastos);

        return view('transporte-escolar::gastos.passagem-empenho', [
            'passagens' => $passagens,
            'empenhos' => $empenhos,
            'saldoNegativo' => PassagemControle::saldoNegativo($passagens['saldo_por_tipo']),
            'fonteGastos' => Schema::hasTable('transporte_escolar.gasto') ? 'transporte_escolar.gasto' : 'demo',
        ]);
    }
}
