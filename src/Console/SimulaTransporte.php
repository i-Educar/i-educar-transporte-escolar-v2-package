<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Console;

use iEducar\Packages\BuritiTransporteEscolar\Services\SimulaCarga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimulaTransporte extends Command
{
    protected $signature = 'transporte:simula
                            {--ano= : Ano letivo. Sem este valor, usa o ano vigente}
                            {--database= : Conexão do município, no multi-tenant}';

    protected $description = 'Carrega a operação de teste do transporte usando os alunos já matriculados no ano';

    public function handle(): int
    {
        $conexao = (string) $this->option('database');

        if ($conexao !== '') {
            DB::setDefaultConnection($conexao);
        }

        if (!Schema::hasTable('transporte_escolar.rota')) {
            $this->error('As tabelas do transporte ainda não existem nesta conexão. Rode as migrations do pacote antes.');

            return self::FAILURE;
        }

        $ano = (int) ($this->option('ano') ?: date('Y'));

        if (SimulaCarga::alunosDoAno($ano) < 1) {
            $this->error(SimulaCarga::mensagemSemAlunos($ano));

            return self::FAILURE;
        }

        $resumo = DB::transaction(fn () => SimulaCarga::executar($ano));

        $this->info('Simulação do transporte de ' . $ano . ' pronta.');
        $this->line('Alunos do ano: ' . $resumo['alunos_ano']);
        $this->line('Alunos colocados em rota nesta execução: ' . $resumo['vinculados']);
        $this->line('Rotas de simulação: ' . $resumo['rotas']);
        $this->line('Paradas das rotas longas: ' . $resumo['paradas']);
        $this->line('Viagens criadas: ' . $resumo['viagens']);
        $this->line('Avisos à família: ' . $resumo['avisos']);
        $this->line('Notificações na fila: ' . $resumo['notificacoes']);
        $this->line('Informes de prazo: ' . $resumo['informes']);
        $this->line('Pedidos da família: ' . $resumo['pedidos']);
        $this->line('Ocorrências e serviços: ' . $resumo['ocorrencias']);

        return self::SUCCESS;
    }
}
