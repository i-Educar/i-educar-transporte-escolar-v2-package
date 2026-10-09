<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ResponsaveisAluno
{
    /**
     * @param  list<int>  $alunos
     * @return array<int, array{ieducar: list<array{papel: string, nome: string, telefone: string}>, transporte: array{nome: string, telefone: string}}>
     */
    public static function mapa(array $alunos): array
    {
        $alunos = array_values(array_unique(array_filter(array_map('intval', $alunos))));
        $mapa = [];

        foreach ($alunos as $aluno) {
            $mapa[$aluno] = ['ieducar' => [], 'transporte' => ['nome' => '', 'telefone' => '']];
        }

        if ($alunos === []) {
            return $mapa;
        }

        $fone = static fn (string $coluna): string => "(select trim(to_char(ddd, 'FM999')) || ' ' || fone::text from cadastro.fone_pessoa where idpes = {$coluna} order by tipo desc limit 1)";

        foreach (DB::table('pmieducar.aluno as a')
            ->join('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.pessoa as mae', 'mae.idpes', '=', 'f.idpes_mae')
            ->leftJoin('cadastro.pessoa as pai', 'pai.idpes', '=', 'f.idpes_pai')
            ->leftJoin('cadastro.pessoa as resp', 'resp.idpes', '=', 'f.idpes_responsavel')
            ->whereIn('a.cod_aluno', $alunos)
            ->select([
                'a.cod_aluno',
                'f.idpes_mae',
                'f.idpes_pai',
                'f.idpes_responsavel',
                'f.nome_mae',
                'f.nome_pai',
                'f.nome_responsavel',
                'mae.nome as nome_mae_pessoa',
                'pai.nome as nome_pai_pessoa',
                'resp.nome as nome_resp_pessoa',
            ])
            ->selectRaw($fone('f.idpes_mae') . ' as fone_mae')
            ->selectRaw($fone('f.idpes_pai') . ' as fone_pai')
            ->selectRaw($fone('f.idpes_responsavel') . ' as fone_resp')
            ->get() as $linha) {
            $mapa[(int) $linha->cod_aluno]['ieducar'] = self::ieducar($linha);
        }

        if (Schema::hasTable('transporte_escolar.responsavel_transporte')) {
            foreach (DB::table('transporte_escolar.responsavel_transporte')->whereIn('ref_cod_aluno', $alunos)->get(['ref_cod_aluno', 'nome', 'telefone']) as $linha) {
                $mapa[(int) $linha->ref_cod_aluno]['transporte'] = [
                    'nome' => (string) $linha->nome,
                    'telefone' => (string) ($linha->telefone ?: ''),
                ];
            }
        }

        return $mapa;
    }

    public static function gravar(int $aluno, string $nome, string $telefone): void
    {
        $nome = trim($nome);
        $telefone = trim($telefone);

        if (!Schema::hasTable('transporte_escolar.responsavel_transporte')) {
            if ($nome !== '' || $telefone !== '') {
                throw new RuntimeException('Atualize o banco do transporte para gravar o responsável.');
            }

            return;
        }

        $tabela = DB::table('transporte_escolar.responsavel_transporte')->where('ref_cod_aluno', $aluno);

        if ($nome === '' && $telefone === '') {
            $tabela->delete();

            return;
        }

        if ($nome === '') {
            throw new RuntimeException('Informe o nome do responsável do transporte.');
        }

        if ($tabela->exists()) {
            $tabela->update(['nome' => $nome, 'telefone' => $telefone !== '' ? $telefone : null, 'updated_at' => now()]);

            return;
        }

        DB::table('transporte_escolar.responsavel_transporte')->insert([
            'ref_cod_aluno' => $aluno,
            'nome' => $nome,
            'telefone' => $telefone !== '' ? $telefone : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return list<array{papel: string, nome: string, telefone: string}>
     */
    private static function ieducar(object $linha): array
    {
        $itens = [];
        $vistos = [];
        $adicionar = function (string $papel, mixed $nome, mixed $telefone, mixed $idpes) use (&$itens, &$vistos): void {
            $nome = trim((string) $nome);

            if ($nome === '') {
                return;
            }

            $chave = $idpes ? 'id:' . (int) $idpes : 'nome:' . mb_strtolower($nome);

            if (isset($vistos[$chave])) {
                return;
            }

            $vistos[$chave] = true;
            $itens[] = [
                'papel' => $papel,
                'nome' => $nome,
                'telefone' => trim((string) $telefone),
            ];
        };

        $adicionar('Mãe', $linha->nome_mae_pessoa ?: $linha->nome_mae, $linha->fone_mae, $linha->idpes_mae);
        $adicionar('Pai', $linha->nome_pai_pessoa ?: $linha->nome_pai, $linha->fone_pai, $linha->idpes_pai);
        $adicionar('Responsável', $linha->nome_resp_pessoa ?: $linha->nome_responsavel, $linha->fone_resp, $linha->idpes_responsavel);

        return $itens;
    }
}
