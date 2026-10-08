<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AplicarFila;
use iEducar\Packages\BuritiTransporteEscolar\Services\PacoteOffline;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\DiaLetivo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OfflineController
{
    public function index(): View
    {
        $usuario = Auth::id();
        $dispositivo = DB::table('transporte_escolar.dispositivo')->where('ref_usuario', $usuario)->first();
        $atrasados = Acesso::permite(Processos::GASTOS, 'ver')
            ? DB::table('transporte_escolar.dispositivo')->orderBy('ultima_descarga_em')->get()
            : collect();

        $pendentes = Acesso::permite(Processos::GASTOS, 'ver')
            ? DB::table('transporte_escolar.fila_pendente as f')
                ->join('transporte_escolar.dispositivo as d', 'd.id', '=', 'f.dispositivo_id')
                ->leftJoin('transporte_escolar.motorista as m', 'm.id', '=', 'd.motorista_id')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
                ->orderBy('f.dia')
                ->get(['d.nome', 'p.nome as motorista', 'f.dia', 'f.quantidade'])
            : collect();

        return view('transporte-escolar::offline', [
            'dispositivo' => $dispositivo,
            'atrasados' => $atrasados,
            'pendentes' => $pendentes,
            'diaLetivo' => $this->diaLetivo(),
            'ano' => (int) date('Y'),
        ]);
    }

    public function pacote(Request $request): JsonResponse
    {
        $ano = (int) $request->input('ano', date('Y'));

        return response()->json(PacoteOffline::montar($ano));
    }

    public function fila(Request $request): JsonResponse
    {
        $usuario = Auth::id();
        $dispositivo = DB::table('transporte_escolar.dispositivo')->where('ref_usuario', $usuario)->first();

        if ($dispositivo === null) {
            $id = DB::table('transporte_escolar.dispositivo')->insertGetId([
                'nome' => 'Navegador ' . $usuario,
                'token' => Str::random(40),
                'ref_usuario' => $usuario,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $dispositivoId = (int) $id;
        } else {
            $dispositivoId = (int) $dispositivo->id;
        }

        $resultado = AplicarFila::receber($dispositivoId, $request->input('eventos', []));
        DB::table('transporte_escolar.dispositivo')->where('id', $dispositivoId)->update([
            'ultima_descarga_em' => now(),
            'updated_at' => now(),
        ]);

        return response()->json($resultado + [
            'pacote' => PacoteOffline::montar((int) $request->input('ano', date('Y'))),
            'descarga_em' => now()->toIso8601String(),
        ]);
    }

    public function pendente(Request $request): JsonResponse
    {
        $usuario = Auth::id();
        $dispositivo = DB::table('transporte_escolar.dispositivo')->where('ref_usuario', $usuario)->first();

        if ($dispositivo === null) {
            $id = DB::table('transporte_escolar.dispositivo')->insertGetId([
                'nome' => 'Navegador ' . $usuario,
                'token' => Str::random(40),
                'ref_usuario' => $usuario,
                'motorista_id' => $request->input('motorista_id') ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $id = (int) $dispositivo->id;

            if ($request->filled('motorista_id')) {
                DB::table('transporte_escolar.dispositivo')->where('id', $id)->update([
                    'motorista_id' => (int) $request->input('motorista_id'),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('transporte_escolar.fila_pendente')->where('dispositivo_id', $id)->delete();

        foreach ((array) $request->input('dias', []) as $dia => $quantidade) {
            $quantidade = (int) $quantidade;

            if ($quantidade < 1 || !is_string($dia)) {
                continue;
            }

            DB::table('transporte_escolar.fila_pendente')->insert([
                'dispositivo_id' => $id,
                'dia' => $dia,
                'quantidade' => $quantidade,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['gravados' => true]);
    }

    private function diaLetivo(): ?bool
    {
        $ano = (int) date('Y');
        $escolas = VisaoRede::escolas();
        $calendarios = DB::table('pmieducar.calendario_ano_letivo')
            ->where('ano', $ano)
            ->where('ativo', 1)
            ->whereNull('data_exclusao');

        if ($escolas === []) {
            return null;
        }

        if (is_array($escolas)) {
            $calendarios->whereIn('ref_cod_escola', $escolas);
        }

        $existe = $calendarios->exists();
        $tipos = DB::table('pmieducar.calendario_dia as d')
            ->join('pmieducar.calendario_ano_letivo as a', 'a.cod_calendario_ano_letivo', '=', 'd.ref_cod_calendario_ano_letivo')
            ->leftJoin('pmieducar.calendario_dia_motivo as m', 'm.cod_calendario_dia_motivo', '=', 'd.ref_cod_calendario_dia_motivo')
            ->where('a.ano', $ano)
            ->where('d.mes', (int) date('n'))
            ->where('d.dia', (int) date('j'))
            ->where('d.ativo', 1)
            ->whereNull('d.data_exclusao')
            ->when(is_array($escolas), fn ($q) => $q->whereIn('a.ref_cod_escola', $escolas))
            ->pluck('m.tipo')
            ->all();

        return DiaLetivo::eLetivo($existe, $tipos);
    }
}
