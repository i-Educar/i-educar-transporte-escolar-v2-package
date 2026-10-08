<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AplicarFila;
use iEducar\Packages\BuritiTransporteEscolar\Support\ChamadaEvento;
use iEducar\Packages\BuritiTransporteEscolar\Support\Frequencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrato do aplicativo de chamada. O mesmo endereço recebe a marcação na hora
 * e a fila gravada no dispositivo quando a rede volta. O uuid evita duplicar.
 */
class ChamadaApiController
{
    public function pacote(Request $request): JsonResponse
    {
        $dispositivo = $this->dispositivo($request);

        if ($dispositivo === null) {
            return response()->json(['erro' => 'Token do dispositivo inválido.'], 401);
        }

        $data = (string) $request->input('data', now()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['erro' => 'Informe a data no formato AAAA-MM-DD.'], 422);
        }

        if ($dispositivo->motorista_id === null && $dispositivo->monitor_id === null) {
            return response()->json(['erro' => 'Vincule um motorista ou um monitor a este dispositivo.'], 422);
        }

        $viagens = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->whereDate('v.data', $data)
            ->orderBy('v.hora')
            ->get(['v.id', 'v.data', 'v.hora', 'v.sentido', 'v.rota_id', 'v.motorista_id', 'v.monitor_id', 'r.descricao']);
        $minhas = $viagens->filter(fn ($viagem) => ChamadaEvento::viagemDoDispositivo(
            $dispositivo->motorista_id === null ? null : (int) $dispositivo->motorista_id,
            $dispositivo->monitor_id === null ? null : (int) $dispositivo->monitor_id,
            $viagem->motorista_id === null ? null : (int) $viagem->motorista_id,
            $viagem->monitor_id === null ? null : (int) $viagem->monitor_id,
        ))->values();
        $rotas = $minhas->pluck('rota_id')->unique()->all();
        $alunos = $rotas === [] ? collect() : DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->whereIn('ar.rota_id', $rotas)
            ->orderBy('p.nome')
            ->get(['ar.rota_id', 'ar.ref_cod_aluno', 'ar.cartao', 'p.nome']);
        $ids = $minhas->pluck('id')->all();
        $presentes = $ids === [] ? [] : DB::table('transporte_escolar.embarque')
            ->where('tipo', 'embarque')
            ->whereIn('viagem_id', $ids)
            ->get(['viagem_id', 'ref_cod_aluno'])
            ->map(fn ($linha) => $linha->viagem_id . ':' . $linha->ref_cod_aluno)
            ->all();

        return response()->json([
            'data' => $data,
            'viagens' => $minhas->map(fn ($viagem) => [
                'id' => (int) $viagem->id,
                'rota_id' => (int) $viagem->rota_id,
                'rota' => $viagem->descricao,
                'hora' => $viagem->hora ? substr((string) $viagem->hora, 0, 5) : null,
                'sentido' => $viagem->sentido,
                'alunos' => $alunos->where('rota_id', $viagem->rota_id)->values()->map(fn ($aluno) => [
                    'aluno_id' => (int) $aluno->ref_cod_aluno,
                    'nome' => $aluno->nome,
                    'cartao' => $aluno->cartao,
                    'qr' => 'TE-' . $aluno->ref_cod_aluno,
                    'marca' => Frequencia::marca(in_array($viagem->id . ':' . $aluno->ref_cod_aluno, $presentes, true)),
                ])->all(),
            ])->all(),
        ]);
    }

    public function marcar(Request $request): JsonResponse
    {
        $dispositivo = $this->dispositivo($request);

        if ($dispositivo === null) {
            return response()->json(['erro' => 'Token do dispositivo inválido.'], 401);
        }

        $eventos = $request->input('eventos', []);

        if (!is_array($eventos)) {
            return response()->json(['erro' => 'Envie a lista de eventos.'], 422);
        }

        $cartoes = DB::table('transporte_escolar.aluno_rota')
            ->whereNotNull('cartao')
            ->pluck('ref_cod_aluno', 'cartao')
            ->map(fn ($id) => (int) $id)
            ->all();
        $aceitos = [];
        $recusados = [];

        foreach ($eventos as $evento) {
            if (!is_array($evento)) {
                $recusados[] = ['uuid' => null, 'erro' => 'Evento inválido.'];
                continue;
            }

            $lido = ChamadaEvento::interpretar($evento, $cartoes);

            if (!$lido['ok']) {
                $recusados[] = ['uuid' => $evento['uuid'] ?? null, 'erro' => $lido['erro']];
                continue;
            }

            $viagem = DB::table('transporte_escolar.viagem')->where('id', (int) $evento['viagem_id'])->first();
            $naRota = $viagem !== null && DB::table('transporte_escolar.aluno_rota')
                ->where('rota_id', $viagem->rota_id)
                ->where('ref_cod_aluno', $lido['aluno_id'])
                ->exists();
            $doDispositivo = $viagem !== null && ChamadaEvento::viagemDoDispositivo(
                $dispositivo->motorista_id === null ? null : (int) $dispositivo->motorista_id,
                $dispositivo->monitor_id === null ? null : (int) $dispositivo->monitor_id,
                $viagem->motorista_id === null ? null : (int) $viagem->motorista_id,
                $viagem->monitor_id === null ? null : (int) $viagem->monitor_id,
            );

            if (!$doDispositivo || !$naRota) {
                $recusados[] = ['uuid' => $lido['uuid'], 'erro' => 'Esta viagem ou este aluno não pertence ao dispositivo.'];
                continue;
            }

            $evento['aluno_id'] = $lido['aluno_id'];
            $evento['tipo'] = $lido['tipo'];
            $aceitos[] = $evento;
        }

        $resultado = AplicarFila::receber((int) $dispositivo->id, $aceitos);
        DB::table('transporte_escolar.dispositivo')->where('id', $dispositivo->id)->update([
            'ultima_descarga_em' => now(),
            'updated_at' => now(),
        ]);

        return response()->json($resultado + ['recusados' => $recusados]);
    }

    private function dispositivo(Request $request): ?object
    {
        $token = $request->bearerToken() ?: (string) $request->header('X-Transporte-Token');

        if ($token === '') {
            return null;
        }

        return DB::table('transporte_escolar.dispositivo')->where('token', $token)->first();
    }
}
