<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

final class ImportadorSete
{
    /**
     * @return array{ok: int, erros: int}
     */
    public static function arquivo(string $caminho, string $nome, int $ano): array
    {
        $linhas = self::ler($caminho, $nome);

        if ($linhas === []) {
            throw new RuntimeException('A planilha não tem linhas.');
        }

        $cabecalho = array_map([self::class, 'normalizar'], array_shift($linhas));
        $tipo = self::detectar($cabecalho);

        if ($tipo === null) {
            throw new RuntimeException('Não reconheci as colunas. Use a planilha de alunos, rotas, veículos, motoristas ou monitores do SETE.');
        }

        $ok = 0;
        $erros = 0;

        foreach ($linhas as $numero => $linha) {
            if (self::vazia($linha)) {
                continue;
            }

            $dados = [];

            foreach ($cabecalho as $indice => $coluna) {
                $dados[$coluna] = trim((string) ($linha[$indice] ?? ''));
            }

            try {
                self::gravar($tipo, $dados, $ano);
                self::registrar($tipo, $dados, 'ok', null, $numero + 2);
                $ok++;
            } catch (RuntimeException $e) {
                self::registrar($tipo, $dados, 'erro', $e->getMessage(), $numero + 2);
                $erros++;
            }
        }

        return ['ok' => $ok, 'erros' => $erros];
    }

    /**
     * @param array<string, string> $dados
     */
    private static function gravar(string $tipo, array $dados, int $ano): void
    {
        match ($tipo) {
            'rota' => self::rota($dados, $ano),
            'veiculo' => self::veiculo($dados),
            'motorista' => self::pessoaOperacao('motorista', $dados),
            'monitor' => self::pessoaOperacao('monitor', $dados),
            default => self::aluno($dados, $ano),
        };
    }

    /**
     * @param array<string, string> $dados
     */
    private static function aluno(array $dados, int $ano): void
    {
        $pessoa = self::pessoaPorCpf($dados['cpf'] ?? '');
        $alunoId = null;

        if ($pessoa !== null) {
            $alunoId = DB::table('pmieducar.aluno')->where('ref_idpes', $pessoa)->where('ativo', 1)->value('cod_aluno');
        }

        if ($alunoId === null && ($dados['inep'] ?? '') !== '' && DB::getSchemaBuilder()->hasTable('modules.educacenso_cod_aluno')) {
            $alunoId = DB::table('modules.educacenso_cod_aluno')
                ->where('cod_aluno_inep', preg_replace('/\D/', '', $dados['inep']))
                ->value('cod_aluno');
        }

        if ($alunoId === null) {
            throw new RuntimeException('Aluno não encontrado no i-Educar por CPF ou INEP. Cadastre a pessoa no núcleo antes de importar.');
        }

        $rotaNome = $dados['rota'] ?? '';

        if ($rotaNome === '') {
            throw new RuntimeException('Linha sem nome de rota.');
        }

        $rotaId = self::rota($dados, $ano);
        $turno = self::primeiro($dados, ['turno', 'periodo']);
        AtribuicaoAluno::vincular((int) $alunoId, $rotaId, null, $turno !== '' ? $turno : null);
    }

    /**
     * @param array<string, string> $dados
     */
    private static function rota(array $dados, int $ano): int
    {
        $nome = self::primeiro($dados, ['rota', 'nome da rota', 'nome']);

        if ($nome === '') {
            throw new RuntimeException('Rota sem nome.');
        }

        $anoRota = (int) ($dados['ano'] ?? 0);
        $anoRota = $anoRota > 0 ? $anoRota : $ano;
        $chave = $nome . '|' . $anoRota;
        $existente = DB::table('transporte_escolar.rota')->where('origem_chave', $chave)->value('id');

        $tipo = strtoupper(substr(self::primeiro($dados, ['tipo', 'tipo da rota', 'tipo rota']), 0, 1));
        $tipo = isset(Codigos::TIPO_ROTA[$tipo]) ? $tipo : 'R';

        $campos = [
            'ano' => $anoRota,
            'descricao' => $nome,
            'tipo_rota' => $tipo,
            'periodo' => 'manha',
            'sentido' => 'I',
            'km_pav' => self::numero(self::primeiro($dados, ['km pav', 'km pavimentado'])),
            'km_npav' => self::numero(self::primeiro($dados, ['km nao pav', 'km nao pavimentado', 'km'])),
            'terceirizada' => false,
            'origem' => 'sete',
            'origem_chave' => $chave,
            'updated_at' => now(),
        ];

        if ($existente !== null) {
            DB::table('transporte_escolar.rota')->where('id', $existente)->update($campos);
            self::ligarEscola((int) $existente, $dados);

            return (int) $existente;
        }

        $campos['created_at'] = now();
        $campos['velocidade_media_kmh'] = 40;

        $id = (int) DB::table('transporte_escolar.rota')->insertGetId($campos);
        self::ligarEscola($id, $dados);

        return $id;
    }

    /**
     * @param array<string, string> $dados
     */
    private static function ligarEscola(int $rotaId, array $dados): void
    {
        $escolaId = null;
        $inep = preg_replace('/\D/', '', self::primeiro($dados, ['inep escola', 'codigo inep escola', 'escola inep']));

        if ($inep !== '' && DB::getSchemaBuilder()->hasTable('modules.educacenso_cod_escola')) {
            $escolaId = DB::table('modules.educacenso_cod_escola')->where('cod_escola_inep', $inep)->value('cod_escola');
        }

        if ($escolaId === null) {
            $nome = self::primeiro($dados, ['escola', 'nome escola']);

            if ($nome !== '') {
                $escolaId = DB::table('pmieducar.escola as e')
                    ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
                    ->whereRaw('lower(p.nome) = ?', [mb_strtolower($nome)])
                    ->value('e.cod_escola');
            }
        }

        if ($escolaId === null) {
            return;
        }

        DB::table('transporte_escolar.rota_escola')->updateOrInsert(
            ['rota_id' => $rotaId, 'ref_cod_escola' => $escolaId],
            [],
        );
    }

    /**
     * @param array<string, string> $dados
     */
    private static function veiculo(array $dados): void
    {
        $placa = strtoupper(preg_replace('/[^A-Z0-9]/', '', self::primeiro($dados, ['placa'])));

        if ($placa === '') {
            throw new RuntimeException('Veículo sem placa.');
        }

        $campos = [
            'descricao' => self::primeiro($dados, ['descricao', 'modelo', 'nome']) ?: $placa,
            'renavam' => self::primeiro($dados, ['renavam']) ?: null,
            'passageiros' => (int) (self::primeiro($dados, ['passageiros', 'capacidade', 'lotacao']) ?: 0),
            'tipo_censo' => 3,
            'consumo_medio_km_l' => self::numero(self::primeiro($dados, ['consumo', 'consumo medio'])),
            'qtd_pneus' => (int) (self::primeiro($dados, ['pneus']) ?: 0) ?: null,
            'caminho_da_escola' => self::sim(self::primeiro($dados, ['caminho da escola'])),
            'origem' => 'sete',
            'origem_chave' => $placa,
            'updated_at' => now(),
        ];

        $id = DB::table('transporte_escolar.veiculo')->where('placa', $placa)->value('id');

        if ($id === null) {
            $campos['placa'] = $placa;
            $campos['created_at'] = now();
            DB::table('transporte_escolar.veiculo')->insert($campos);

            return;
        }

        DB::table('transporte_escolar.veiculo')->where('id', $id)->update($campos);
    }

    /**
     * @param array<string, string> $dados
     */
    private static function pessoaOperacao(string $tabela, array $dados): void
    {
        $idpes = self::pessoaPorCpf($dados['cpf'] ?? '');

        if ($idpes === null) {
            throw new RuntimeException('CPF não cadastrado em cadastro.fisica. O núcleo precisa ter a pessoa antes.');
        }

        $campos = [
            'vinculo' => 'contratado',
            'turno' => self::primeiro($dados, ['turno']) ?: null,
            'salario_mensal' => self::numero(self::primeiro($dados, ['salario', 'salario mensal'])),
            'origem' => 'sete',
            'origem_chave' => preg_replace('/\D/', '', $dados['cpf'] ?? ''),
            'updated_at' => now(),
        ];

        if ($tabela === 'motorista') {
            $campos['cnh'] = self::primeiro($dados, ['cnh']) ?: null;
            $campos['tipo_cnh'] = self::primeiro($dados, ['categoria', 'tipo cnh']) ?: null;
        }

        $id = DB::table('transporte_escolar.' . $tabela)->where('ref_idpes', $idpes)->value('id');

        if ($id === null) {
            $campos['ref_idpes'] = $idpes;
            $campos['created_at'] = now();
            DB::table('transporte_escolar.' . $tabela)->insert($campos);

            return;
        }

        DB::table('transporte_escolar.' . $tabela)->where('id', $id)->update($campos);
    }

    private static function pessoaPorCpf(string $cpf): ?int
    {
        $digitos = preg_replace('/\D/', '', $cpf);

        if ($digitos === '') {
            return null;
        }

        $id = DB::table('cadastro.fisica')
            ->whereRaw("regexp_replace(cpf::text, '\\D', '', 'g') = ?", [$digitos])
            ->value('idpes');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param list<string> $cabecalho
     */
    private static function detectar(array $cabecalho): ?string
    {
        $tem = static fn (string $coluna): bool => in_array($coluna, $cabecalho, true);

        if ($tem('placa')) {
            return 'veiculo';
        }

        if ($tem('cnh')) {
            return 'motorista';
        }

        if ($tem('monitor') || ($tem('salario') && !$tem('aluno') && !$tem('cpf'))) {
            return 'monitor';
        }

        if ($tem('aluno') || $tem('nome do aluno') || ($tem('cpf') && ($tem('rota') || $tem('escola')))) {
            return 'aluno';
        }

        if ($tem('rota') || $tem('nome da rota')) {
            return 'rota';
        }

        return null;
    }

    /**
     * @return list<list<mixed>>
     */
    private static function ler(string $caminho, string $nome): array
    {
        $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (in_array($extensao, ['xlsx', 'xls'], true)) {
            if (!class_exists(IOFactory::class)) {
                throw new RuntimeException('Planilha Excel precisa do PhpSpreadsheet, que acompanha o i-Educar 2.12.');
            }

            $planilha = IOFactory::load($caminho)->getActiveSheet()->toArray(null, true, true, false);

            return $planilha;
        }

        $arquivo = new \SplFileObject($caminho);
        $arquivo->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $linhas = [];

        foreach ($arquivo as $linha) {
            if (is_array($linha)) {
                $linhas[] = $linha;
            }
        }

        return $linhas;
    }

    /**
     * @param list<mixed> $linha
     */
    private static function vazia(array $linha): bool
    {
        foreach ($linha as $celula) {
            if (trim((string) $celula) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function normalizar(mixed $valor): string
    {
        $texto = mb_strtolower(trim((string) $valor));
        $texto = strtr($texto, ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }

    /**
     * @param array<string, string> $dados
     * @param list<string> $chaves
     */
    private static function primeiro(array $dados, array $chaves): string
    {
        foreach ($chaves as $chave) {
            if (($dados[$chave] ?? '') !== '') {
                return $dados[$chave];
            }
        }

        return '';
    }

    private static function numero(string $valor): ?float
    {
        if ($valor === '') {
            return null;
        }

        $limpo = str_replace(['.', ' '], ['', ''], $valor);
        $limpo = str_replace(',', '.', $limpo);

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    private static function sim(string $valor): bool
    {
        return in_array(self::normalizar($valor), ['sim', 's', '1', 'true'], true);
    }

    /**
     * @param array<string, string> $dados
     */
    private static function registrar(string $tipo, array $dados, string $situacao, ?string $mensagem, int $linha): void
    {
        DB::table('transporte_escolar.importacao_linha')->insert([
            'entidade' => $tipo,
            'chave' => $dados['cpf'] ?? $dados['placa'] ?? $dados['rota'] ?? $dados['nome'] ?? null,
            'situacao' => $situacao,
            'mensagem' => $mensagem,
            'linha' => $linha,
            'payload' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
