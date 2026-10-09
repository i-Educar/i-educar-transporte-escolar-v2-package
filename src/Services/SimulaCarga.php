<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\AvisoTexto;
use iEducar\Packages\BuritiTransporteEscolar\Support\ModeloAviso;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProtocoloSolicitacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class SimulaCarga
{
    public static function mensagemSemAlunos(int $ano): string
    {
        return 'Não há alunos matriculados em ' . $ano . '. Simule os alunos antes, com php artisan ieducar:matriculas.';
    }

    public static function alunosDoAno(int $ano): int
    {
        return (int) DB::table('pmieducar.matricula')
            ->where('ano', $ano)
            ->where('ativo', 1)
            ->distinct()
            ->count('ref_cod_aluno');
    }

    /**
     * @return array{alunos_ano: int, vinculados: int, rotas: int, viagens: int, avisos: int, notificacoes: int, informes: int, pedidos: int, ocorrencias: int, paradas: int}
     */
    public static function executar(int $ano): array
    {
        $empresa = self::empresa();
        $oficina = self::fornecedor('simula-oficina', 'Oficina simulada', self::cnpj(390000000001));
        $posto = self::fornecedor('simula-posto', 'Posto simulado', self::cnpj(390000000002));
        $motoristas = [
            self::motorista('simula-motorista-1', 'Motorista simulado Um', 'M', $empresa, 'MOT-S01'),
            self::motorista('simula-motorista-2', 'Motorista simulado Dois', 'F', $empresa, 'MOT-S02'),
        ];
        $monitores = [
            self::monitor('simula-monitor-1', 'Monitor simulado Um', 'F', 'MON-S01'),
            self::monitor('simula-monitor-2', 'Monitor simulado Dois', 'M', 'MON-S02'),
        ];
        $onibus = self::veiculo('simula-onibus', 'SIM0A01', 'Ônibus simulado', 3, 40, 3.2, $empresa, $motoristas[0]);
        $van = self::veiculo('simula-van', 'SIM0B02', 'Van simulada', 1, 15, 8, $empresa, $motoristas[1]);
        self::configuracao();
        self::manutencao($onibus);
        self::gasto($onibus);
        self::abastecimento($onibus, $posto);
        self::ordem($van, $oficina);

        $escolas = self::escolas($ano);
        $vinculados = 0;
        $viagens = 0;
        $veiculos = [$onibus, $van];
        $indice = 0;

        foreach ($escolas as $escola) {
            $rota = self::rota($ano, $escola, $empresa, $veiculos[$indice % 2], $motoristas[$indice % 2], $monitores[$indice % 2]);
            $vinculados += self::vincular($ano, $escola, $rota);
            $viagens += self::viagens($ano, $rota);
            $indice++;
        }

        self::processo($ano, $escolas);
        $pedidos = self::pedidos($ano, $escolas[0] ?? null);
        $lotacao = self::lotacao($ano, $empresa, $motoristas[0]);
        $longas = self::rotasLongas($ano, $empresa, $motoristas[0], $monitores[0]);
        $chamada = self::chamada($ano);

        return [
            'alunos_ano' => self::alunosDoAno($ano),
            'vinculados' => $vinculados + $lotacao['vinculados'] + $longas['vinculados'],
            'rotas' => count($escolas) + $lotacao['rotas'] + $longas['rotas'],
            'viagens' => $viagens + $longas['viagens'],
            'avisos' => $chamada['avisos'],
            'notificacoes' => $chamada['notificacoes'],
            'informes' => self::informes($empresa),
            'pedidos' => $pedidos,
            'ocorrencias' => self::ocorrencias($ano) + self::servicos($onibus, $oficina, $posto) + self::contrato($ano, $empresa) + self::offline(),
            'paradas' => $longas['paradas'],
        ];
    }

    /**
     * @return list<object>
     */
    private static function escolas(int $ano): array
    {
        return DB::table('pmieducar.matricula as m')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 'm.ref_ref_cod_escola')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
            ->where('m.ano', $ano)
            ->where('m.ativo', 1)
            ->groupBy('e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude')
            ->orderBy('e.cod_escola')
            ->limit(3)
            ->get(['e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude'])
            ->all();
    }

    private static function empresa(): int
    {
        $id = DB::table('transporte_escolar.empresa')->where('origem_chave', 'simula-empresa')->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $pessoa = self::juridica('Cooperativa simulada de transporte', self::cnpj(390000000003));

        return (int) DB::table('transporte_escolar.empresa')->insertGetId([
            'ref_idpes' => $pessoa,
            'observacao' => 'Carga de simulação do transporte',
            'origem' => 'simula',
            'origem_chave' => 'simula-empresa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function fornecedor(string $chave, string $nome, string $cnpj): int
    {
        $id = DB::table('transporte_escolar.fornecedor')->where('origem_chave', $chave)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('transporte_escolar.fornecedor')->insertGetId([
            'ref_idpes' => self::juridica($nome, $cnpj),
            'observacao' => 'Carga de simulação',
            'origem' => 'simula',
            'origem_chave' => $chave,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function motorista(string $chave, string $nome, string $sexo, int $empresa, string $matricula): int
    {
        $id = DB::table('transporte_escolar.motorista')->where('origem_chave', $chave)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $pessoa = self::fisica($nome, $sexo, '1985-04-12');

        return (int) DB::table('transporte_escolar.motorista')->insertGetId([
            'ref_idpes' => $pessoa,
            'empresa_id' => $empresa,
            'cnh' => substr(self::cpf(392000000 + $pessoa), 0, 11),
            'tipo_cnh' => 'D',
            'dt_habilitacao' => '2012-05-01',
            'vencimento_cnh' => ((int) date('Y') + 1) . '-08-20',
            'vinculo' => 'contratado',
            'turno' => 'manha',
            'salario_mensal' => 3900,
            'carga_horaria' => 40,
            'encargo_percent' => 35,
            'matricula_funcional' => $matricula,
            'origem' => 'simula',
            'origem_chave' => $chave,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function monitor(string $chave, string $nome, string $sexo, string $matricula): int
    {
        $id = DB::table('transporte_escolar.monitor')->where('origem_chave', $chave)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('transporte_escolar.monitor')->insertGetId([
            'ref_idpes' => self::fisica($nome, $sexo, '1992-09-03'),
            'turno' => 'manha',
            'salario_mensal' => 2200,
            'carga_horaria' => 40,
            'encargo_percent' => 35,
            'matricula_funcional' => $matricula,
            'origem' => 'simula',
            'origem_chave' => $chave,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function veiculo(string $chave, string $placa, string $descricao, int $tipo, int $passageiros, float $consumo, int $empresa, int $motorista): int
    {
        $id = DB::table('transporte_escolar.veiculo')->where('origem_chave', $chave)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('transporte_escolar.veiculo')->insertGetId([
            'descricao' => $descricao,
            'placa' => $placa,
            'renavam' => '390' . str_pad((string) abs(crc32($chave)), 8, '0', STR_PAD_LEFT),
            'marca' => 'Simulado',
            'ano_fabricacao' => (int) date('Y') - 6,
            'ano_modelo' => (int) date('Y') - 5,
            'passageiros' => $passageiros,
            'malha' => 'P',
            'tipo_censo' => $tipo,
            'exclusivo_escolar' => true,
            'ativo' => true,
            'consumo_medio_km_l' => $consumo,
            'qtd_pneus' => $passageiros > 20 ? 6 : 4,
            'licenciamento_vencimento' => ((int) date('Y') + 1) . '-04-30',
            'empresa_id' => $empresa,
            'motorista_id' => $motorista,
            'origem' => 'simula',
            'origem_chave' => $chave,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{id: int, veiculo: int, motorista: int, monitor: int, hora: string}
     */
    private static function rota(int $ano, object $escola, int $empresa, int $veiculo, int $motorista, int $monitor, ?string $chave = null): array
    {
        $chave ??= 'simula-rota-' . $escola->cod_escola;
        $id = DB::table('transporte_escolar.rota')->where('origem_chave', $chave)->where('ano', $ano)->value('id');
        $base = self::coordenada($escola);
        $hora = '06:40:00';

        if ($id === null) {
            $id = (int) DB::table('transporte_escolar.rota')->insertGetId([
                'ano' => $ano,
                'descricao' => Str::limit('Linha simulada ' . $escola->cod_escola . ' ' . $escola->nome, 80, ''),
                'tipo_rota' => 'U',
                'periodo' => 'manha',
                'sentido' => 'C',
                'km_pav' => 8,
                'km_npav' => 0,
                'velocidade_media_kmh' => 40,
                'terceirizada' => false,
                'empresa_id' => $empresa,
                'origem' => 'simula',
                'origem_chave' => $chave,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('transporte_escolar.rota_escola')->insertOrIgnore([
                'rota_id' => $id,
                'ref_cod_escola' => $escola->cod_escola,
            ]);
            $escolaPonto = self::ponto('simula-ponto-' . $escola->cod_escola . '-escola', Str::limit($escola->nome, 70, ''), $base[0], $base[1]);
            $parada = self::ponto('simula-ponto-' . $escola->cod_escola . '-parada', 'Parada simulada', $base[0] + 0.008, $base[1] + 0.006);
            $onibus = DB::table('transporte_escolar.veiculo')->where('id', $veiculo)->value('id');
            DB::table('transporte_escolar.itinerario')->insert([
                ['rota_id' => $id, 'seq' => 1, 'ponto_id' => $parada, 'veiculo_id' => $onibus, 'hora' => '06:40:00', 'tipo' => 'I'],
                ['rota_id' => $id, 'seq' => 2, 'ponto_id' => $escolaPonto, 'veiculo_id' => $onibus, 'hora' => '07:10:00', 'tipo' => 'I'],
                ['rota_id' => $id, 'seq' => 3, 'ponto_id' => $escolaPonto, 'veiculo_id' => $onibus, 'hora' => '11:40:00', 'tipo' => 'V'],
                ['rota_id' => $id, 'seq' => 4, 'ponto_id' => $parada, 'veiculo_id' => $onibus, 'hora' => '12:10:00', 'tipo' => 'V'],
            ]);
        }

        return [
            'id' => (int) $id,
            'veiculo' => $veiculo,
            'motorista' => $motorista,
            'monitor' => $monitor,
            'hora' => $hora,
            'escola' => (int) $escola->cod_escola,
        ];
    }

    private static function ponto(string $chave, string $descricao, float $lat, float $lng): int
    {
        $id = DB::table('transporte_escolar.ponto')->where('origem_chave', $chave)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('transporte_escolar.ponto')->insertGetId([
            'descricao' => $descricao,
            'latitude' => round($lat, 7),
            'longitude' => round($lng, 7),
            'raio_metros' => 150,
            'origem' => 'simula',
            'origem_chave' => $chave,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array{id: int, escola: int} $rota
     */
    private static function vincular(int $ano, object $escola, array $rota): int
    {
        if (DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota['id'])->exists()) {
            return 0;
        }

        $alunos = DB::table('pmieducar.matricula as m')
            ->where('m.ano', $ano)
            ->where('m.ativo', 1)
            ->where('m.ref_ref_cod_escola', $escola->cod_escola)
            ->whereNotExists(function ($consulta) use ($ano) {
                $consulta->select(DB::raw('1'))
                    ->from('transporte_escolar.aluno_rota as ar')
                    ->whereColumn('ar.ref_cod_aluno', 'm.ref_cod_aluno')
                    ->where('ar.ano', $ano);
            })
            ->distinct()
            ->orderBy('m.ref_cod_aluno')
            ->limit(12)
            ->pluck('m.ref_cod_aluno');
        $parada = DB::table('transporte_escolar.ponto')->where('origem_chave', 'simula-ponto-' . $escola->cod_escola . '-parada')->value('id');
        $base = self::coordenada($escola);
        $gravados = 0;

        foreach ($alunos as $indice => $aluno) {
            DB::table('transporte_escolar.aluno_rota')->insert([
                'ref_cod_aluno' => $aluno,
                'rota_id' => $rota['id'],
                'ponto_id' => $parada,
                'turno' => 'manha',
                'ano' => $ano,
                'residencia_latitude' => round($base[0] + 0.004 + ($indice * 0.0004), 7),
                'residencia_longitude' => round($base[1] + 0.003 + ($indice * 0.0003), 7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('pmieducar.aluno')->where('cod_aluno', $aluno)->where(function ($consulta) {
                $consulta->whereNull('tipo_transporte')->orWhere('tipo_transporte', 0);
            })->update(['tipo_transporte' => 2]);
            $gravados++;
        }

        return $gravados;
    }

    /**
     * @param array{id: int, veiculo: int, motorista: int, monitor: int, hora: string} $rota
     */
    private static function viagens(int $ano, array $rota): int
    {
        if (DB::table('transporte_escolar.viagem')->where('rota_id', $rota['id'])->whereYear('data', $ano)->exists()) {
            return 0;
        }

        $mes = $ano === (int) date('Y') ? (int) date('n') : 3;
        $dia = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fim = $dia->copy()->endOfMonth();
        $hoje = Carbon::today();
        $criadas = 0;
        $km = 1000.0;
        $primeira = null;

        while ($dia->lte($fim)) {
            if ($dia->isWeekday()) {
                foreach (['I' => '06:40:00', 'V' => '11:40:00'] as $sentido => $hora) {
                    $passada = $dia->lte($hoje);
                    $id = (int) DB::table('transporte_escolar.viagem')->insertGetId([
                        'data' => $dia->toDateString(),
                        'hora' => $hora,
                        'rota_id' => $rota['id'],
                        'veiculo_id' => $rota['veiculo'],
                        'motorista_id' => $rota['motorista'],
                        'monitor_id' => $rota['monitor'],
                        'sentido' => $sentido,
                        'km_inicial' => $passada ? $km : null,
                        'km_final' => $passada ? $km + 8 : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($passada) {
                        $km += 8;
                    }
                    if ($primeira === null && $sentido === 'I' && $passada) {
                        $primeira = $id;
                    }
                    $criadas++;
                }
            }
            $dia->addDay();
        }

        if ($primeira !== null) {
            self::embarques($primeira, $rota['id']);
        }

        return $criadas;
    }

    private static function embarques(int $viagem, int $rota): void
    {
        $alunos = DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota)->orderBy('id')->limit(8)->pluck('ref_cod_aluno');
        $quando = DB::table('transporte_escolar.viagem')->where('id', $viagem)->value('data');

        foreach ($alunos as $indice => $aluno) {
            if ($indice >= 6) {
                continue;
            }
            DB::table('transporte_escolar.embarque')->insert([
                'viagem_id' => $viagem,
                'ref_cod_aluno' => $aluno,
                'tipo' => 'embarque',
                'ocorrido_em' => $quando . ' 06:45:00',
                'fora_da_cerca' => false,
                'fila_uuid' => 'simula-emb-' . $viagem . '-' . $aluno,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param list<object> $escolas
     */
    private static function processo(int $ano, array $escolas): void
    {
        $nome = 'Simulação de transporte ' . $ano;
        $grupo = DB::table('transporte_escolar.processo_grupo')->where('nome', $nome)->where('ano', $ano)->value('id');

        if ($grupo === null) {
            $grupo = DB::table('transporte_escolar.processo_grupo')->insertGetId([
                'nome' => $nome,
                'ano' => $ano,
                'inicio' => $ano . '-02-01',
                'fim' => $ano . '-12-15',
                'ativo' => true,
                'situacao' => 'aberto',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $processo = DB::table('transporte_escolar.processo_vaga')->where('nome', $nome)->where('ano', $ano)->value('id');

        if ($processo === null) {
            $processo = DB::table('transporte_escolar.processo_vaga')->insertGetId([
                'nome' => $nome,
                'ano' => $ano,
                'inicio' => $ano . '-02-01',
                'fim' => $ano . '-12-15',
                'ativo' => true,
                'situacao' => 'aberto',
                'grupo_id' => $grupo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($escolas as $escola) {
            $rota = DB::table('transporte_escolar.rota')->where('origem_chave', 'simula-rota-' . $escola->cod_escola)->where('ano', $ano)->value('id');

            if ($rota === null) {
                continue;
            }

            $existe = DB::table('transporte_escolar.processo_vaga_oferta')
                ->where('processo_id', $processo)
                ->where('rota_id', $rota)
                ->where('ref_cod_escola', $escola->cod_escola)
                ->where('turno', 'manha')
                ->exists();

            if (!$existe) {
                DB::table('transporte_escolar.processo_vaga_oferta')->insert([
                    'processo_id' => $processo,
                    'rota_id' => $rota,
                    'ref_cod_escola' => $escola->cod_escola,
                    'turno' => 'manha',
                    'vagas' => 20,
                    'situacao' => 'aberto',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private static function pedidos(int $ano, ?object $escola): int
    {
        if ($escola === null) {
            return 0;
        }

        $catalogo = [
            ['Ana Simulada', 'recebida', 'Simulação de transporte, 10', null],
            ['Bruno Simulado', 'em_analise', 'Simulação de transporte, 11', null],
            ['Carla Simulada', 'deferida', 'Simulação de transporte, 20', 'Pedido simulado aceito para a linha da manhã.'],
            ['Diego Simulado', 'indeferida', 'Simulação de transporte, 21', 'Endereço simulado fora da área da linha.'],
            ['Elisa Simulada', 'cancelada', 'Simulação de transporte, 22', 'Família simulada cancelou o pedido.'],
            ['Fabio Simulado', 'recebida', 'Simulação de transporte, 23', null],
        ];
        $criados = 0;
        $agora = now();

        foreach ($catalogo as $indice => [$nome, $situacao, $endereco, $motivo]) {
            if (DB::table('transporte_escolar.solicitacao')->where('ano', $ano)->where('endereco', $endereco)->exists()) {
                continue;
            }

            $numero = (int) DB::table('transporte_escolar.solicitacao')->where('ano', $ano)->max('sequencia') + 1;
            $linha = [
                'ano' => $ano,
                'sequencia' => $numero,
                'protocolo' => ProtocoloSolicitacao::montar($ano, $numero),
                'nome_aluno' => $nome,
                'nascimento' => '2016-04-0' . (($indice % 9) + 1),
                'nome_responsavel' => 'Responsável simulado',
                'telefone' => '5499999001' . $indice,
                'endereco' => $endereco,
                'turno' => 'manha',
                'escola_informada' => $escola->nome,
                'ref_cod_escola' => $escola->cod_escola,
                'situacao' => $situacao,
                'motivo_situacao' => $motivo,
                'pedido_em' => $agora,
                'atualizado_em' => $agora,
            ];

            if (Schema::hasColumn('transporte_escolar.solicitacao', 'acompanhamento')) {
                $linha['acompanhamento'] = 'simula' . str_pad((string) ($indice + 1), 4, '0', STR_PAD_LEFT);
                $linha['contato_escola'] = $situacao === 'deferida' ? 'Escola simulada, secretaria, ramal 201' : null;
                $linha['contato_setor'] = $situacao === 'deferida' ? 'Setor de transporte, 8h às 14h' : null;
            }

            DB::table('transporte_escolar.solicitacao')->insert($linha);
            $criados++;
        }

        return $criados;
    }

    /**
     * @return array{vinculados: int, rotas: int}
     */
    private static function lotacao(int $ano, int $empresa, int $motorista): array
    {
        $escola = self::escolas($ano)[0] ?? null;

        if ($escola === null) {
            return ['vinculados' => 0, 'rotas' => 0];
        }

        $veiculo = self::veiculo('simula-kombi', 'SIM0C03', 'Kombi simulada', 1, 4, 9, $empresa, $motorista);
        $chave = 'simula-rota-lotacao-' . $escola->cod_escola;
        $nova = DB::table('transporte_escolar.rota')->where('origem_chave', $chave)->where('ano', $ano)->doesntExist();
        $monitor = (int) DB::table('transporte_escolar.monitor')->where('origem_chave', 'simula-monitor-1')->value('id');
        $rota = self::rota($ano, (object) [
            'cod_escola' => $escola->cod_escola,
            'nome' => 'Lotação ' . $escola->nome,
            'latitude' => $escola->latitude,
            'longitude' => $escola->longitude,
        ], $empresa, $veiculo, $motorista, $monitor, $chave);

        if ($rota['id'] < 1) {
            return ['vinculados' => 0, 'rotas' => 0];
        }

        DB::table('transporte_escolar.rota')->where('id', $rota['id'])->where('origem_chave', $chave)->update([
            'descricao' => Str::limit('Linha simulada lotada ' . $escola->cod_escola, 80, ''),
        ]);

        $antes = (int) DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota['id'])->count();
        $escolaLotada = clone $escola;
        $vinculados = self::vincular($ano, $escolaLotada, $rota);
        $ocupacao = $antes + $vinculados;

        if ($ocupacao > 4) {
            DB::table('transporte_escolar.veiculo')->where('id', $veiculo)->where('origem_chave', 'simula-kombi')->update(['passageiros' => 4]);
        } elseif ($ocupacao > 1) {
            DB::table('transporte_escolar.veiculo')->where('id', $veiculo)->where('origem_chave', 'simula-kombi')->update(['passageiros' => $ocupacao - 1]);
        }

        return ['vinculados' => $vinculados, 'rotas' => $nova ? 1 : 0];
    }

    /**
     * @return array{vinculados: int, rotas: int, viagens: int, paradas: int}
     */
    private static function rotasLongas(int $ano, int $empresa, int $motorista, int $monitor): array
    {
        $resumo = ['vinculados' => 0, 'rotas' => 0, 'viagens' => 0, 'paradas' => 0];
        $veiculo = self::veiculo('simula-onibus-longo', 'SIM0D04', 'Ônibus simulado de linha longa', 3, 46, 2.8, $empresa, $motorista);
        $escolas = self::escolas($ano);

        if ($escolas === []) {
            return $resumo;
        }

        foreach ([
            [$escolas[0], 'manha', 10, 40],
            [$escolas[1] ?? $escolas[0], 'tarde', 8, 28],
        ] as [$escola, $periodo, $quantidade, $limite]) {
            $parte = self::rotaLonga($ano, $escola, $empresa, $veiculo, $motorista, $monitor, $periodo, $quantidade, $limite);
            $resumo['vinculados'] += $parte['vinculados'];
            $resumo['rotas'] += $parte['rotas'];
            $resumo['viagens'] += $parte['viagens'];
            $resumo['paradas'] += $parte['paradas'];
        }

        return $resumo;
    }

    /**
     * @return array{vinculados: int, rotas: int, viagens: int, paradas: int}
     */
    private static function rotaLonga(int $ano, object $escola, int $empresa, int $veiculo, int $motorista, int $monitor, string $periodo, int $quantidade, int $limite): array
    {
        $chave = 'simula-rota-longa-' . $periodo . '-' . $escola->cod_escola;
        $id = DB::table('transporte_escolar.rota')->where('origem_chave', $chave)->where('ano', $ano)->value('id');
        $nova = $id === null;
        $base = self::coordenada($escola);
        $paradas = [];

        for ($ordem = 1; $ordem <= $quantidade; $ordem++) {
            $paradas[] = [
                'id' => self::ponto(
                    'simula-ponto-longo-' . $periodo . '-' . $escola->cod_escola . '-' . $ordem,
                    'Parada longa ' . $ordem,
                    $base[0] + ($ordem * 0.015),
                    $base[1] + ($ordem * 0.008)
                ),
                'lat' => $base[0] + ($ordem * 0.015),
                'lng' => $base[1] + ($ordem * 0.008),
            ];
        }

        $escolaPonto = self::ponto(
            'simula-ponto-' . $escola->cod_escola . '-escola',
            Str::limit((string) $escola->nome, 70, ''),
            $base[0],
            $base[1]
        );

        if ($id === null) {
            $id = (int) DB::table('transporte_escolar.rota')->insertGetId([
                'ano' => $ano,
                'descricao' => Str::limit('Linha longa simulada ' . $escola->cod_escola . ' ' . $periodo, 80, ''),
                'tipo_rota' => 'U',
                'periodo' => $periodo,
                'sentido' => 'C',
                'km_pav' => $quantidade * 3,
                'km_npav' => 6,
                'velocidade_media_kmh' => 40,
                'terceirizada' => false,
                'empresa_id' => $empresa,
                'origem' => 'simula',
                'origem_chave' => $chave,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('transporte_escolar.rota_escola')->insertOrIgnore([
                'rota_id' => $id,
                'ref_cod_escola' => $escola->cod_escola,
            ]);
            $linhas = [];
            $seq = 1;
            $hora = $periodo === 'tarde' ? 12 * 60 + 10 : 5 * 60 + 10;

            foreach ($paradas as $parada) {
                $linhas[] = [
                    'rota_id' => $id,
                    'seq' => $seq,
                    'ponto_id' => $parada['id'],
                    'veiculo_id' => $veiculo,
                    'hora' => sprintf('%02d:%02d:00', intdiv($hora, 60), $hora % 60),
                    'tipo' => 'I',
                ];
                $seq++;
                $hora += 7;
            }

            $linhas[] = [
                'rota_id' => $id,
                'seq' => $seq,
                'ponto_id' => $escolaPonto,
                'veiculo_id' => $veiculo,
                'hora' => sprintf('%02d:%02d:00', intdiv($hora, 60), $hora % 60),
                'tipo' => 'I',
            ];
            $seq++;
            $hora = $periodo === 'tarde' ? 17 * 60 : 12 * 60;
            $linhas[] = [
                'rota_id' => $id,
                'seq' => $seq,
                'ponto_id' => $escolaPonto,
                'veiculo_id' => $veiculo,
                'hora' => sprintf('%02d:%02d:00', intdiv($hora, 60), $hora % 60),
                'tipo' => 'V',
            ];
            $seq++;
            $hora += 7;

            foreach (array_reverse($paradas) as $parada) {
                $linhas[] = [
                    'rota_id' => $id,
                    'seq' => $seq,
                    'ponto_id' => $parada['id'],
                    'veiculo_id' => $veiculo,
                    'hora' => sprintf('%02d:%02d:00', intdiv($hora, 60), $hora % 60),
                    'tipo' => 'V',
                ];
                $seq++;
                $hora += 7;
            }

            DB::table('transporte_escolar.itinerario')->insert($linhas);
            self::ofertaLonga($ano, (int) $id, (int) $escola->cod_escola, $periodo);
        }

        $rota = [
            'id' => (int) $id,
            'veiculo' => $veiculo,
            'motorista' => $motorista,
            'monitor' => $monitor,
            'hora' => $periodo === 'tarde' ? '12:10:00' : '05:10:00',
            'escola' => (int) $escola->cod_escola,
        ];

        return [
            'vinculados' => self::vincularPontos($ano, $escola, $rota, $paradas, $limite, $periodo),
            'rotas' => $nova ? 1 : 0,
            'viagens' => self::viagens($ano, $rota),
            'paradas' => $nova ? $quantidade : 0,
        ];
    }

    /**
     * @param list<array{id: int, lat: float, lng: float}> $paradas
     * @param array{id: int, escola: int} $rota
     */
    private static function vincularPontos(int $ano, object $escola, array $rota, array $paradas, int $limite, string $periodo): int
    {
        if ($paradas === [] || DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota['id'])->exists()) {
            return 0;
        }

        $alunos = DB::table('pmieducar.matricula as m')
            ->where('m.ano', $ano)
            ->where('m.ativo', 1)
            ->where('m.ref_ref_cod_escola', $escola->cod_escola)
            ->whereNotExists(function ($consulta) use ($ano) {
                $consulta->select(DB::raw('1'))
                    ->from('transporte_escolar.aluno_rota as ar')
                    ->whereColumn('ar.ref_cod_aluno', 'm.ref_cod_aluno')
                    ->where('ar.ano', $ano);
            })
            ->distinct()
            ->orderBy('m.ref_cod_aluno')
            ->limit($limite)
            ->pluck('m.ref_cod_aluno');
        $gravados = 0;
        $total = count($paradas);

        foreach ($alunos as $indice => $aluno) {
            $parada = $paradas[$indice % $total];
            DB::table('transporte_escolar.aluno_rota')->insert([
                'ref_cod_aluno' => $aluno,
                'rota_id' => $rota['id'],
                'ponto_id' => $parada['id'],
                'turno' => $periodo,
                'ano' => $ano,
                'residencia_latitude' => round($parada['lat'] + (($indice % 5) * 0.0003), 7),
                'residencia_longitude' => round($parada['lng'] + (($indice % 4) * 0.0003), 7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('pmieducar.aluno')->where('cod_aluno', $aluno)->where(function ($consulta) {
                $consulta->whereNull('tipo_transporte')->orWhere('tipo_transporte', 0);
            })->update(['tipo_transporte' => 2]);
            $gravados++;
        }

        return $gravados;
    }

    private static function ofertaLonga(int $ano, int $rota, int $escola, string $periodo): void
    {
        $processo = DB::table('transporte_escolar.processo_vaga')->where('nome', 'Simulação de transporte ' . $ano)->where('ano', $ano)->value('id');

        if ($processo === null) {
            return;
        }

        $existe = DB::table('transporte_escolar.processo_vaga_oferta')
            ->where('processo_id', $processo)
            ->where('rota_id', $rota)
            ->where('ref_cod_escola', $escola)
            ->where('turno', $periodo)
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('transporte_escolar.processo_vaga_oferta')->insert([
            'processo_id' => $processo,
            'rota_id' => $rota,
            'ref_cod_escola' => $escola,
            'turno' => $periodo,
            'vagas' => 40,
            'situacao' => 'aberto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{avisos: int, notificacoes: int}
     */
    private static function chamada(int $ano): array
    {
        $avisos = 0;
        $notificacoes = 0;
        $rotas = DB::table('transporte_escolar.rota')->where('ano', $ano)->where('origem_chave', 'like', 'simula-rota-%')->orderBy('id')->limit(2)->pluck('id');

        foreach ($rotas as $rota) {
            $viagens = DB::table('transporte_escolar.viagem')
                ->where('rota_id', $rota)
                ->where('sentido', 'I')
                ->whereNotNull('km_final')
                ->orderBy('data')
                ->limit(3)
                ->get(['id', 'data']);
            $alunos = DB::table('transporte_escolar.aluno_rota')->where('rota_id', $rota)->orderBy('id')->limit(6)->get(['ref_cod_aluno', 'id']);

            foreach ($alunos as $indice => $aluno) {
                if (Schema::hasColumn('transporte_escolar.aluno_rota', 'cartao') && DB::table('transporte_escolar.aluno_rota')->where('id', $aluno->id)->whereNull('cartao')->exists()) {
                    $cartao = 'SIM' . str_pad((string) $aluno->id, 6, '0', STR_PAD_LEFT);
                    if (DB::table('transporte_escolar.aluno_rota')->where('cartao', $cartao)->doesntExist()) {
                        DB::table('transporte_escolar.aluno_rota')->where('id', $aluno->id)->update(['cartao' => $cartao]);
                    }
                }

                foreach ($viagens as $viagem) {
                    self::marcar($viagem->id, (int) $aluno->ref_cod_aluno, 'embarque', $viagem->data . ' 06:45:00', $indice === 0);
                    if ($indice < 4) {
                        self::marcar($viagem->id, (int) $aluno->ref_cod_aluno, 'desembarque', $viagem->data . ' 07:05:00', false);
                    }
                }
            }

            if (Schema::hasColumn('transporte_escolar.viagem', 'km_trilha')) {
                DB::table('transporte_escolar.viagem')
                    ->where('rota_id', $rota)
                    ->whereNotNull('km_final')
                    ->whereNull('km_trilha')
                    ->update(['km_trilha' => 8.2]);
            }
        }

        $embarques = DB::table('transporte_escolar.embarque as e')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('r.ano', $ano)
            ->where('r.origem_chave', 'like', 'simula-rota-%')
            ->where('e.fila_uuid', 'like', 'simula-%')
            ->orderBy('e.id')
            ->limit(12)
            ->get(['e.id', 'e.ref_cod_aluno', 'e.tipo', 'e.fora_da_cerca', 'r.descricao']);

        foreach ($embarques as $indice => $embarque) {
            if (!Schema::hasTable('transporte_escolar.aviso_familia') || DB::table('transporte_escolar.aviso_familia')->where('embarque_id', $embarque->id)->exists()) {
                continue;
            }

            $nome = (string) DB::table('pmieducar.aluno as a')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                ->where('a.cod_aluno', $embarque->ref_cod_aluno)
                ->value('p.nome');
            $modelo = Schema::hasTable('transporte_escolar.notificacao_modelo')
                ? DB::table('transporte_escolar.notificacao_modelo')->where('codigo', $embarque->tipo)->first(['titulo', 'corpo'])
                : null;
            $texto = ModeloAviso::texto(
                (string) ($modelo->corpo ?? ''),
                $nome,
                (string) $embarque->descricao,
                $embarque->tipo === 'desembarque' ? 'desembarcou' : 'embarcou',
                AvisoTexto::frase($nome, (string) $embarque->tipo, (string) $embarque->descricao)
            );

            if ($embarque->fora_da_cerca) {
                $extra = Schema::hasTable('transporte_escolar.notificacao_modelo')
                    ? (string) DB::table('transporte_escolar.notificacao_modelo')->where('codigo', 'fora_cerca')->value('corpo')
                    : '';
                $texto .= ' ' . ($extra !== '' ? $extra : 'O embarque ficou fora da cerca do ponto.');
            }

            $aviso = (int) DB::table('transporte_escolar.aviso_familia')->insertGetId([
                'embarque_id' => $embarque->id,
                'ref_cod_aluno' => $embarque->ref_cod_aluno,
                'token' => 'simula-aviso-' . $embarque->id,
                'texto' => $texto,
                'lido_em' => $indice % 3 === 0 ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $avisos++;
            $notificacoes += self::notificar((int) $embarque->ref_cod_aluno, $aviso, (string) ($modelo->titulo ?? 'Transporte escolar'), $texto, $indice % 2 === 0);
        }

        return ['avisos' => $avisos, 'notificacoes' => $notificacoes];
    }

    private static function marcar(int $viagem, int $aluno, string $tipo, string $quando, bool $fora): void
    {
        $uuid = 'simula-' . $tipo . '-' . $viagem . '-' . $aluno;

        if (DB::table('transporte_escolar.embarque')->where('fila_uuid', $uuid)->exists()
            || DB::table('transporte_escolar.embarque')->where('viagem_id', $viagem)->where('ref_cod_aluno', $aluno)->where('tipo', $tipo)->exists()) {
            return;
        }

        DB::table('transporte_escolar.embarque')->insert([
            'viagem_id' => $viagem,
            'ref_cod_aluno' => $aluno,
            'tipo' => $tipo,
            'ocorrido_em' => $quando,
            'fora_da_cerca' => $fora,
            'fila_uuid' => $uuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function notificar(int $aluno, int $aviso, string $titulo, string $corpo, bool $entregue): int
    {
        if (!Schema::hasTable('transporte_escolar.notificacao_inscricao')) {
            return 0;
        }

        $token = 'simula-push-' . $aluno;
        $inscricao = DB::table('transporte_escolar.notificacao_inscricao')->where('token', $token)->value('id');

        if ($inscricao === null) {
            $inscricao = DB::table('transporte_escolar.notificacao_inscricao')->insertGetId([
                'token' => $token,
                'ref_cod_aluno' => $aluno,
                'endpoint' => 'https://simula.invalid/push/' . $aluno,
                'chave_p256dh' => 'simula',
                'chave_auth' => 'simula',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('transporte_escolar.notificacao_fila')->where('aviso_id', $aviso)->where('inscricao_id', $inscricao)->exists()) {
            return 0;
        }

        DB::table('transporte_escolar.notificacao_fila')->insert([
            'inscricao_id' => $inscricao,
            'aviso_id' => $aviso,
            'titulo' => $titulo,
            'corpo' => $corpo,
            'entregue_em' => $entregue ? now() : null,
            'created_at' => now(),
        ]);

        return 1;
    }

    private static function informes(int $empresa): int
    {
        $gravados = 0;
        $seguro = now()->addDays(10)->toDateString();
        $vistoria = now()->addDays(18)->toDateString();

        $gravados += DB::table('transporte_escolar.veiculo')->where('origem_chave', 'simula-onibus')->whereNull('seguro_vencimento')->update(['seguro_vencimento' => $seguro]);
        $gravados += DB::table('transporte_escolar.veiculo')->where('origem_chave', 'simula-van')->whereNull('vistoria_vencimento')->update(['vistoria_vencimento' => $vistoria]);

        if (DB::table('transporte_escolar.motorista')->where('origem_chave', 'simula-motorista-prazo')->doesntExist()) {
            self::motorista('simula-motorista-prazo', 'Motorista simulado Prazo', 'M', $empresa, 'MOT-S03');
            DB::table('transporte_escolar.motorista')->where('origem_chave', 'simula-motorista-prazo')->update([
                'vencimento_cnh' => now()->addDays(12)->toDateString(),
            ]);
            $gravados++;
        }

        return $gravados;
    }

    private static function ocorrencias(int $ano): int
    {
        $viagem = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('r.ano', $ano)
            ->where('r.origem_chave', 'like', 'simula-rota-%')
            ->where('v.sentido', 'I')
            ->whereNotNull('v.km_final')
            ->orderBy('v.data')
            ->value('v.id');

        if ($viagem === null) {
            return 0;
        }

        $gravadas = 0;

        foreach ([
            'simula-oc-atraso' => 'Simulação: a linha saiu 15 minutos depois do horário.',
            'simula-oc-pneu' => 'Simulação: calibragem de pneu no retorno.',
            'simula-oc-aluno' => 'Simulação: um aluno não estava no ponto e a linha seguiu.',
        ] as $uuid => $texto) {
            if (DB::table('transporte_escolar.ocorrencia')->where('fila_uuid', $uuid)->exists()) {
                continue;
            }

            DB::table('transporte_escolar.ocorrencia')->insert([
                'viagem_id' => $viagem,
                'texto' => $texto,
                'ocorrido_em' => now(),
                'fila_uuid' => $uuid,
            ]);
            $gravadas++;
        }

        return $gravadas;
    }

    private static function servicos(int $onibus, int $oficina, int $posto): int
    {
        $gravados = 0;

        if (Schema::hasColumn('transporte_escolar.ordem_servico', 'situacao')
            && DB::table('transporte_escolar.ordem_servico')->where('descricao', 'Simulação: freio dianteiro')->doesntExist()) {
            DB::table('transporte_escolar.ordem_servico')->insert([
                'veiculo_id' => $onibus,
                'fornecedor_id' => $oficina,
                'preventiva' => false,
                'descricao' => 'Simulação: freio dianteiro',
                'valor' => 480,
                'data' => now()->toDateString(),
                'fonte' => 'proprio',
                'protocolo' => 'OS-SIMULA-000002-0',
                'situacao' => 'solicitada',
                'solicitante' => 'Simulação ordem',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        if (Schema::hasColumn('transporte_escolar.abastecimento', 'situacao')
            && DB::table('transporte_escolar.abastecimento')->where('solicitante', 'Simulação bilhete')->doesntExist()) {
            DB::table('transporte_escolar.abastecimento')->insert([
                'veiculo_id' => $onibus,
                'fornecedor_id' => $posto,
                'data' => now()->toDateString(),
                'litros' => 0,
                'valor' => 0,
                'km' => 1200,
                'fonte' => 'pnate',
                'protocolo' => 'AB-SIMULA-000002-0',
                'situacao' => 'solicitado',
                'litros_autorizados' => 45,
                'posto' => 'Posto simulado',
                'motorista' => 'Motorista simulado',
                'solicitante' => 'Simulação bilhete',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        $gasto = DB::table('transporte_escolar.gasto')->where('descricao', 'Simulação: seguro do ônibus')->value('id');

        if ($gasto !== null && Schema::hasTable('transporte_escolar.comprovante')
            && DB::table('transporte_escolar.comprovante')->where('nome', 'Simulação: apólice')->doesntExist()) {
            DB::table('transporte_escolar.comprovante')->insert([
                'gasto_id' => $gasto,
                'nome' => 'Simulação: apólice',
                'caminho' => 'transporte-escolar/comprovantes/simula-apolice.txt',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        return $gravados;
    }

    private static function contrato(int $ano, int $empresa): int
    {
        $gravados = 0;

        if (DB::table('transporte_escolar.contrato')->where('descricao', 'Contrato simulado de transporte')->doesntExist()) {
            DB::table('transporte_escolar.contrato')->insert([
                'empresa_id' => $empresa,
                'descricao' => 'Contrato simulado de transporte',
                'valor_mensal' => 18000,
                'inicio' => $ano . '-01-01',
                'fim' => $ano . '-12-31',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        $mes = (int) date('n');

        foreach (['pnate' => 25000, 'proprio' => 8000] as $fonte => $valor) {
            if (DB::table('transporte_escolar.parcela_recurso')->where('ano', $ano)->where('mes', $mes)->where('fonte', $fonte)->exists()) {
                continue;
            }

            DB::table('transporte_escolar.parcela_recurso')->insert([
                'ano' => $ano,
                'mes' => $mes,
                'fonte' => $fonte,
                'valor' => $valor,
                'saldo_informado' => $valor,
            ]);
            $gravados++;
        }

        return $gravados;
    }

    private static function offline(): int
    {
        $motorista = DB::table('transporte_escolar.motorista')->where('origem_chave', 'simula-motorista-1')->value('id');
        $dispositivo = DB::table('transporte_escolar.dispositivo')->where('token', 'simula-dispositivo-1')->value('id');

        if ($dispositivo === null) {
            $dispositivo = DB::table('transporte_escolar.dispositivo')->insertGetId([
                'nome' => 'Tablet simulado da linha',
                'token' => 'simula-dispositivo-1',
                'motorista_id' => $motorista,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $gravados = 0;
        $dia = now()->toDateString();

        if (DB::table('transporte_escolar.fila_pendente')->where('dispositivo_id', $dispositivo)->where('dia', $dia)->doesntExist()) {
            DB::table('transporte_escolar.fila_pendente')->insert([
                'dispositivo_id' => $dispositivo,
                'dia' => $dia,
                'quantidade' => 3,
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        if (DB::table('transporte_escolar.fila_evento')->where('uuid', 'simula-evento-embarque')->doesntExist()) {
            DB::table('transporte_escolar.fila_evento')->insert([
                'dispositivo_id' => $dispositivo,
                'uuid' => 'simula-evento-embarque',
                'tipo' => 'embarque',
                'payload' => json_encode(['origem' => 'simula', 'tipo' => 'embarque'], JSON_THROW_ON_ERROR),
                'ocorrido_em' => now(),
                'aplicado_em' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $gravados++;
        }

        return $gravados;
    }

    private static function configuracao(): void
    {
        foreach ([
            'atendimento_setor' => 'Transporte Escolar',
            'atendimento_horario' => 'Dias úteis, das 8h às 14h',
            'rateio' => 'km',
            'geocerca' => '1',
            'raio_metros' => '150',
        ] as $chave => $valor) {
            if (!DB::table('transporte_escolar.configuracao')->where('chave', $chave)->exists()) {
                DB::table('transporte_escolar.configuracao')->insert(['chave' => $chave, 'valor' => $valor]);
            }
        }
    }

    private static function manutencao(int $veiculo): void
    {
        if (DB::table('transporte_escolar.manutencao_plano')->where('veiculo_id', $veiculo)->exists()) {
            return;
        }

        DB::table('transporte_escolar.manutencao_plano')->insert([
            ['veiculo_id' => $veiculo, 'item' => 'oleo', 'a_cada_km' => 10000, 'a_cada_dias' => 180, 'ultima_data' => now()->subMonth()->toDateString()],
            ['veiculo_id' => $veiculo, 'item' => 'pneu', 'a_cada_km' => 40000, 'a_cada_dias' => null, 'ultima_data' => now()->subMonth()->toDateString()],
        ]);
    }

    private static function gasto(int $veiculo): void
    {
        if (DB::table('transporte_escolar.gasto')->where('descricao', 'Simulação: seguro do ônibus')->exists()) {
            return;
        }

        DB::table('transporte_escolar.gasto')->insert([
            'data' => now()->toDateString(),
            'categoria' => 'seguro',
            'fonte' => 'pnate',
            'valor' => 800,
            'descricao' => 'Simulação: seguro do ônibus',
            'veiculo_id' => $veiculo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function abastecimento(int $veiculo, int $posto): void
    {
        if (DB::table('transporte_escolar.abastecimento')->where('solicitante', 'Simulação')->exists()) {
            return;
        }

        DB::table('transporte_escolar.abastecimento')->insert([
            'veiculo_id' => $veiculo,
            'fornecedor_id' => $posto,
            'data' => now()->toDateString(),
            'litros' => 40,
            'valor' => 240,
            'km' => 1100,
            'fonte' => 'pnate',
            'protocolo' => 'AB-SIMULA-000001-0',
            'situacao' => 'abastecido',
            'litros_autorizados' => 50,
            'posto' => 'Posto simulado',
            'motorista' => 'Motorista simulado',
            'solicitante' => 'Simulação',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function ordem(int $veiculo, int $oficina): void
    {
        if (DB::table('transporte_escolar.ordem_servico')->where('solicitante', 'Simulação')->exists()) {
            return;
        }

        DB::table('transporte_escolar.ordem_servico')->insert([
            'veiculo_id' => $veiculo,
            'fornecedor_id' => $oficina,
            'preventiva' => true,
            'descricao' => 'Simulação: revisão',
            'valor' => 320,
            'data' => now()->toDateString(),
            'fonte' => 'proprio',
            'protocolo' => 'OS-SIMULA-000001-0',
            'situacao' => 'concluida',
            'solicitante' => 'Simulação',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function coordenada(object $escola): array
    {
        $lat = is_numeric($escola->latitude) ? (float) $escola->latitude : null;
        $lng = is_numeric($escola->longitude) ? (float) $escola->longitude : null;

        if ($lat !== null && $lng !== null && abs($lat) <= 90 && abs($lng) <= 180 && ($lat !== 0.0 || $lng !== 0.0)) {
            return [$lat, $lng];
        }

        return [-15.793889 + (((int) $escola->cod_escola % 20) * 0.02), -47.882778];
    }

    private static function juridica(string $nome, string $cnpj): int
    {
        $id = (int) DB::selectOne("SELECT nextval('cadastro.seq_pessoa') AS id")->id;
        $agora = now();
        DB::table('cadastro.pessoa')->insert([
            'idpes' => $id,
            'nome' => $nome,
            'tipo' => 'J',
            'situacao' => 'A',
            'origem_gravacao' => 'M',
            'operacao' => 'I',
            'data_cad' => $agora,
        ]);
        DB::table('cadastro.juridica')->insert([
            'idpes' => $id,
            'cnpj' => $cnpj,
            'fantasia' => $nome,
            'origem_gravacao' => 'M',
            'operacao' => 'I',
            'data_cad' => $agora,
        ]);

        return $id;
    }

    private static function fisica(string $nome, string $sexo, string $nascimento): int
    {
        $id = (int) DB::selectOne("SELECT nextval('cadastro.seq_pessoa') AS id")->id;
        $agora = now();
        DB::table('cadastro.pessoa')->insert([
            'idpes' => $id,
            'nome' => $nome,
            'tipo' => 'F',
            'situacao' => 'A',
            'origem_gravacao' => 'M',
            'operacao' => 'I',
            'data_cad' => $agora,
        ]);
        DB::table('cadastro.fisica')->insert([
            'idpes' => $id,
            'data_nasc' => $nascimento,
            'sexo' => $sexo,
            'cpf' => self::cpf(392000000 + $id),
            'nacionalidade' => 1,
            'origem_gravacao' => 'M',
            'operacao' => 'I',
            'data_cad' => $agora,
        ]);

        return $id;
    }

    private static function cpf(int $base): string
    {
        $numeros = str_pad((string) ($base % 1000000000), 9, '0', STR_PAD_LEFT);
        $d1 = self::digito($numeros, 10);
        $d2 = self::digito($numeros . $d1, 11);

        return $numeros . $d1 . $d2;
    }

    private static function cnpj(int $base): string
    {
        $numeros = str_pad((string) $base, 12, '0', STR_PAD_LEFT);
        $d1 = self::digitoPesos($numeros, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $d2 = self::digitoPesos($numeros . $d1, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $numeros . $d1 . $d2;
    }

    private static function digito(string $numeros, int $pesoInicial): int
    {
        $soma = 0;

        foreach (str_split($numeros) as $indice => $digito) {
            $soma += (int) $digito * ($pesoInicial - $indice);
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }

    /**
     * @param list<int> $pesos
     */
    private static function digitoPesos(string $numeros, array $pesos): int
    {
        $soma = 0;

        foreach (str_split($numeros) as $indice => $digito) {
            $soma += (int) $digito * $pesos[$indice];
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
