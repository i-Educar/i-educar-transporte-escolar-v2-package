<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Console;

use iEducar\Packages\BuritiTransporteEscolar\Support\EmpenhoVerba;
use iEducar\Packages\BuritiTransporteEscolar\Support\PassagemControle;
use Illuminate\Console\Command;

class PassagemEmpenhoDemoCommand extends Command
{
    protected $signature = 'transporte:passagem-empenho-demo {--dir=}';

    protected $description = 'Onda 4 — demo PassagemControle + EmpenhoVerba (evidência PoC)';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/transporte-onda4');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $passagens = PassagemControle::consolidar([
            ['tipo' => 'urbano', 'quantidade' => 100, 'sentido' => 'recebido'],
            ['tipo' => 'urbano', 'quantidade' => 35, 'sentido' => 'entregue', 'rede' => 'municipal'],
            ['tipo' => 'rural', 'quantidade' => 50, 'sentido' => 'recebido'],
            ['tipo' => 'rural', 'quantidade' => 10, 'sentido' => 'entregue', 'rede' => 'municipal'],
        ]);
        $empenhos = EmpenhoVerba::agrupar([
            ['empenho' => '2026NE001', 'verba' => 'PNATE', 'fornecedor' => 'Posto Alfa', 'valor' => 1500.5],
            ['empenho' => '2026NE002', 'verba' => 'PNATE', 'fornecedor' => 'Oficina Beta', 'valor' => 820],
            ['empenho' => '2026RP010', 'verba' => 'Proprio', 'fornecedor' => 'Posto Alfa', 'valor' => 300],
        ]);

        file_put_contents($dir.'/passagens.json', json_encode($passagens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/empenhos.json', json_encode($empenhos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Onda 4 — passagem/empenho demo');
        $this->line('Destino: '.$dir);
        $this->line(sprintf('  ✓ passagens recebido=%d entregue=%d', $passagens['total_recebido'], $passagens['total_entregue']));
        $this->line(sprintf('  ✓ empenhos total=R$ %.2f (%d verbas)', $empenhos['total_geral'], count($empenhos['por_verba'])));
        $this->info('Gate Onda 4.B OK. UI: /transporte-escolar/gastos/passagens-empenhos');

        return self::SUCCESS;
    }
}
