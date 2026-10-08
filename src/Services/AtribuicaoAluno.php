<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\SincroniaCenso;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AtribuicaoAluno
{
    public static function vincular(int $alunoId, int $rotaId, ?int $pontoId, ?string $turno, ?float $latitude = null, ?float $longitude = null): void
    {
        $rota = DB::table('transporte_escolar.rota')->where('id', $rotaId)->first();

        if ($rota === null) {
            throw new RuntimeException('Rota não encontrada.');
        }

        $matricula = RedeConsulta::matriculasAtivas((int) $rota->ano)
            ->where('a.cod_aluno', $alunoId)
            ->first();

        if ($matricula === null) {
            throw new RuntimeException('O aluno não tem matrícula ativa neste ano na escola que você pode ver.');
        }

        if (!VisaoRede::contemEscola((int) $matricula->cod_escola)) {
            throw new RuntimeException('A matrícula é de outra escola.');
        }

        $escolasDaRota = DB::table('transporte_escolar.rota_escola')
            ->where('rota_id', $rotaId)
            ->pluck('ref_cod_escola')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($escolasDaRota !== [] && !in_array((int) $matricula->cod_escola, $escolasDaRota, true)) {
            throw new RuntimeException('Esta rota não atende a escola da matrícula.');
        }

        $gravar = [
            'ponto_id' => $pontoId,
            'turno' => $turno,
            'ano' => (int) $rota->ano,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if ($latitude !== null && $longitude !== null) {
            $gravar['residencia_latitude'] = $latitude;
            $gravar['residencia_longitude'] = $longitude;
        }

        DB::table('transporte_escolar.aluno_rota')->updateOrInsert(
            ['ref_cod_aluno' => $alunoId, 'rota_id' => $rotaId],
            $gravar,
        );

        self::sincronizar($alunoId, $rota);
    }

    public static function cartao(int $alunoId, int $rotaId, string $cartao): void
    {
        $vinculo = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->where('ar.ref_cod_aluno', $alunoId)
            ->where('ar.rota_id', $rotaId)
            ->first(['ar.id', 'r.ano']);

        if ($vinculo === null) {
            throw new RuntimeException('Este aluno não está nesta rota.');
        }

        $codigo = trim($cartao);

        if ($codigo !== '') {
            $outro = DB::table('transporte_escolar.aluno_rota')
                ->where('cartao', $codigo)
                ->where(function ($consulta) use ($alunoId, $rotaId) {
                    $consulta->where('ref_cod_aluno', '!=', $alunoId)->orWhere('rota_id', '!=', $rotaId);
                })
                ->exists();

            if ($outro) {
                throw new RuntimeException('Este cartão já está com outro aluno.');
            }
        }

        $matricula = RedeConsulta::matriculasAtivas((int) $vinculo->ano)->where('a.cod_aluno', $alunoId)->first();

        if ($matricula !== null && !VisaoRede::contemEscola((int) $matricula->cod_escola)) {
            throw new RuntimeException('A matrícula é de outra escola.');
        }

        DB::table('transporte_escolar.aluno_rota')
            ->where('ref_cod_aluno', $alunoId)
            ->where('rota_id', $rotaId)
            ->update(['cartao' => $codigo === '' ? null : $codigo, 'updated_at' => now()]);
    }

    public static function desvincular(int $alunoId, int $rotaId): void
    {
        $rota = DB::table('transporte_escolar.rota')->where('id', $rotaId)->first();
        $matricula = $rota === null
            ? null
            : RedeConsulta::matriculasAtivas((int) $rota->ano)->where('a.cod_aluno', $alunoId)->first();

        if ($matricula !== null && !VisaoRede::contemEscola((int) $matricula->cod_escola)) {
            throw new RuntimeException('A matrícula é de outra escola.');
        }

        DB::table('transporte_escolar.aluno_rota')
            ->where('ref_cod_aluno', $alunoId)
            ->where('rota_id', $rotaId)
            ->delete();

        $resta = DB::table('transporte_escolar.aluno_rota')->where('ref_cod_aluno', $alunoId)->exists();

        if (!$resta) {
            $limpo = SincroniaCenso::aoDesvincularUltima();
            DB::table('pmieducar.aluno')->where('cod_aluno', $alunoId)->update([
                'tipo_transporte' => $limpo['tipo_transporte'],
                'utiliza_transporte_rural' => $limpo['utiliza_transporte_rural'],
                'rota_transporte' => $limpo['rota_transporte'],
            ]);
        }
    }

    private static function sincronizar(int $alunoId, object $rota): void
    {
        $aluno = DB::table('pmieducar.aluno')->where('cod_aluno', $alunoId)->first();
        $tipoVeiculo = DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->where('i.rota_id', $rota->id)
            ->value('v.tipo_censo');

        $atuais = self::inteiros($aluno->veiculo_transporte_escolar ?? null);
        $dados = SincroniaCenso::aoVincular(
            (int) $aluno->tipo_transporte,
            $rota->tipo_rota === 'R',
            $tipoVeiculo === null ? null : (int) $tipoVeiculo,
            $atuais,
            (string) $rota->descricao,
        );

        DB::table('pmieducar.aluno')->where('cod_aluno', $alunoId)->update([
            'tipo_transporte' => $dados['tipo_transporte'],
            'utiliza_transporte_rural' => $dados['utiliza_transporte_rural'],
            'veiculo_transporte_escolar' => self::arrayPostgres($dados['veiculo_transporte_escolar']),
            'rota_transporte' => $dados['rota_transporte'],
        ]);
    }

    /**
     * @return list<int>
     */
    private static function inteiros(mixed $valor): array
    {
        if (is_array($valor)) {
            return array_map('intval', $valor);
        }

        if (!is_string($valor) || $valor === '' || $valor === '{}') {
            return [];
        }

        $limpo = trim($valor, '{}');

        return $limpo === '' ? [] : array_map('intval', explode(',', $limpo));
    }

    /**
     * @param list<int> $valores
     */
    private static function arrayPostgres(array $valores): string
    {
        return '{' . implode(',', $valores) . '}';
    }
}
