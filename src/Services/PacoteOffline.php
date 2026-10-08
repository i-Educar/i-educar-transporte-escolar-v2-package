<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Facades\DB;

final class PacoteOffline
{
    /**
     * @return array<string, mixed>
     */
    public static function montar(int $ano): array
    {
        $rotas = RedeConsulta::rotas($ano)->get();
        $ids = $rotas->pluck('id')->all();
        $vazio = $ids === [];

        $alunos = $vazio ? collect() : DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->whereIn('ar.rota_id', $ids)
            ->get(['ar.rota_id', 'ar.ref_cod_aluno', 'ar.turno', 'p.nome']);

        $pontos = $vazio ? collect() : DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'i.ponto_id')
            ->whereIn('i.rota_id', $ids)
            ->orderBy('i.seq')
            ->get(['i.rota_id', 'i.seq', 'p.id', 'p.descricao', 'p.latitude', 'p.longitude']);

        $veiculos = DB::table('transporte_escolar.veiculo')->where('ativo', true)->get(['id', 'placa', 'descricao', 'passageiros']);

        return [
            'versao' => now()->toIso8601String(),
            'visao' => VisaoRede::rotulo(),
            'rotas' => $rotas,
            'alunos' => $alunos->map(static fn ($aluno): array => [
                'rota_id' => (int) $aluno->rota_id,
                'aluno_id' => (int) $aluno->ref_cod_aluno,
                'nome' => $aluno->nome,
                'turno' => $aluno->turno,
                'qr' => 'TE-' . $aluno->ref_cod_aluno,
            ]),
            'pontos' => $pontos,
            'veiculos' => $veiculos,
        ];
    }
}
