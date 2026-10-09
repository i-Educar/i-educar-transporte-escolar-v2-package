<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Services\RegistrarAviso;
use iEducar\Packages\BuritiTransporteEscolar\Services\TrilhaKm;
use iEducar\Packages\BuritiTransporteEscolar\Support\AptidaoViagem;
use iEducar\Packages\BuritiTransporteEscolar\Support\LoteViagem;
use iEducar\Packages\BuritiTransporteEscolar\Support\QrCodigo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ViagemController
{
    public function index(): View
    {
        $ano = (int) request('ano', date('Y'));
        $mes = (int) request('mes', date('n'));
        $ids = RedeConsulta::rotas()->pluck('r.id');

        $viagens = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->leftJoin('transporte_escolar.veiculo as ve', 've.id', '=', 'v.veiculo_id')
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->whereYear('v.data', $ano)
            ->whereMonth('v.data', $mes)
            ->orderBy('v.data')
            ->orderBy('v.hora')
            ->get(['v.id', 'v.data', 'v.hora', 'v.sentido', 'v.km_inicial', 'v.km_final', 'r.descricao', 've.placa']);

        return view('transporte-escolar::viagens.index', [
            'viagens' => $viagens,
            'rotas' => RedeConsulta::rotas()->get(),
            'veiculos' => DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get(),
            'motoristas' => DB::table('transporte_escolar.motorista as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['m.id', 'p.nome']),
            'monitores' => DB::table('transporte_escolar.monitor as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['m.id', 'p.nome']),
            'sentidos' => Codigos::SENTIDO,
            'ano' => $ano,
            'mes' => $mes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'rota_id' => ['required', 'integer'],
            'veiculo_id' => ['nullable', 'integer'],
            'motorista_id' => ['nullable', 'integer'],
            'monitor_id' => ['nullable', 'integer'],
            'sentido' => ['required', 'in:I,V,C'],
            'km_inicial' => ['nullable', 'numeric'],
            'km_final' => ['nullable', 'numeric'],
        ]);

        abort_if(RedeConsulta::rotas()->where('r.id', $dados['rota_id'])->doesntExist(), 404);
        $papel = $this->papelInapto(isset($dados['veiculo_id']) ? (int) $dados['veiculo_id'] : null, isset($dados['motorista_id']) ? (int) $dados['motorista_id'] : null);

        if ($papel !== null) {
            return back()->with('erro', $papel);
        }

        if (KmRealizado::deOdometro(
            isset($dados['km_inicial']) ? (float) $dados['km_inicial'] : null,
            isset($dados['km_final']) ? (float) $dados['km_final'] : null,
        ) === null && isset($dados['km_inicial'], $dados['km_final'])) {
            return back()->with('erro', 'O km final não pode ser menor que o inicial.');
        }

        $id = DB::table('transporte_escolar.viagem')->insertGetId([
            'data' => $dados['data'],
            'rota_id' => $dados['rota_id'],
            'veiculo_id' => $dados['veiculo_id'] ?? null,
            'motorista_id' => $dados['motorista_id'] ?? null,
            'monitor_id' => $dados['monitor_id'] ?? null,
            'sentido' => $dados['sentido'],
            'hora' => $request->input('hora') ?: null,
            'km_inicial' => $dados['km_inicial'] ?? null,
            'km_final' => $dados['km_final'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/transporte-escolar/viagens/' . $id);
    }

    public function lote(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'rota_id' => ['required', 'integer'],
            'veiculo_id' => ['nullable', 'integer'],
            'motorista_id' => ['nullable', 'integer'],
            'monitor_id' => ['nullable', 'integer'],
        ]);
        $rota = RedeConsulta::rotas()->where('r.id', $dados['rota_id'])->first();
        abort_if($rota === null, 404);
        $papel = $this->papelInapto(isset($dados['veiculo_id']) ? (int) $dados['veiculo_id'] : null, isset($dados['motorista_id']) ? (int) $dados['motorista_id'] : null);

        if ($papel !== null) {
            return back()->with('erro', $papel);
        }

        $escolas = DB::table('transporte_escolar.rota_escola')->where('rota_id', $rota->id)->pluck('ref_cod_escola');
        $calendarios = DB::table('pmieducar.calendario_ano_letivo')
            ->where('ano', $dados['ano'])
            ->where('ativo', 1)
            ->whereNull('data_exclusao')
            ->when($escolas->isNotEmpty(), fn ($q) => $q->whereIn('ref_cod_escola', $escolas));
        $temCalendario = $calendarios->exists();
        $dias = [];

        if ($temCalendario) {
            $linhas = DB::table('pmieducar.calendario_dia as d')
                ->join('pmieducar.calendario_ano_letivo as a', 'a.cod_calendario_ano_letivo', '=', 'd.ref_cod_calendario_ano_letivo')
                ->leftJoin('pmieducar.calendario_dia_motivo as m', 'm.cod_calendario_dia_motivo', '=', 'd.ref_cod_calendario_dia_motivo')
                ->where('a.ano', $dados['ano'])
                ->where('d.mes', $dados['mes'])
                ->where('d.ativo', 1)
                ->whereNull('d.data_exclusao')
                ->when($escolas->isNotEmpty(), fn ($q) => $q->whereIn('a.ref_cod_escola', $escolas))
                ->get(['d.dia', 'd.mes', 'm.tipo']);

            foreach ($linhas->groupBy(fn ($linha) => $linha->mes . '-' . $linha->dia) as $grupo) {
                $primeiro = $grupo->first();
                $dias[] = [
                    'dia' => (int) $primeiro->dia,
                    'mes' => (int) $primeiro->mes,
                    'tipos' => $grupo->pluck('tipo')->all(),
                ];
            }
        }

        $paradas = DB::table('transporte_escolar.itinerario')->where('rota_id', $rota->id)->get(['tipo', 'hora']);
        $sentidos = LoteViagem::sentidos((string) $rota->sentido, $paradas->pluck('tipo')->all());
        $criadas = 0;
        $existentes = 0;

        foreach (LoteViagem::datas((int) $dados['ano'], (int) $dados['mes'], $temCalendario, $dias) as $data) {
            foreach ($sentidos as $sentido) {
                $jaExiste = DB::table('transporte_escolar.viagem')
                    ->where('rota_id', $rota->id)
                    ->where('data', $data)
                    ->where('sentido', $sentido)
                    ->exists();

                if ($jaExiste) {
                    $existentes++;
                    continue;
                }

                $hora = $paradas->where('tipo', $sentido)->pluck('hora')->filter()->sort()->first();
                DB::table('transporte_escolar.viagem')->insert([
                    'data' => $data,
                    'hora' => LoteViagem::horario($hora === null ? null : (string) $hora, (string) $rota->periodo),
                    'rota_id' => $rota->id,
                    'veiculo_id' => $dados['veiculo_id'] ?? null,
                    'motorista_id' => $dados['motorista_id'] ?? null,
                    'monitor_id' => $dados['monitor_id'] ?? null,
                    'sentido' => $sentido,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $criadas++;
            }
        }

        return redirect('/transporte-escolar/viagens?ano=' . $dados['ano'] . '&mes=' . $dados['mes'])
            ->with('ok', $criadas . ' viagens abertas nos dias letivos. ' . $existentes . ' já existiam.');
    }

    public function show(int $viagem): View
    {
        $registro = $this->visivel($viagem);
        $alunos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.pessoa as resp', 'resp.idpes', '=', 'f.idpes_responsavel')
            ->where('ar.rota_id', $registro->rota_id)
            ->orderBy('p.nome')
            ->select(['ar.ref_cod_aluno', 'p.nome'])
            ->selectRaw('coalesce(resp.nome, f.nome_responsavel) as responsavel')
            ->selectRaw("(select trim(to_char(ddd, 'FM999')) || ' ' || fone::text from cadastro.fone_pessoa where idpes = coalesce(f.idpes_responsavel, a.ref_idpes) order by tipo desc limit 1) as telefone")
            ->get();
        $eventos = DB::table('transporte_escolar.embarque')->where('viagem_id', $viagem)->get()->keyBy(fn ($item) => $item->ref_cod_aluno . ':' . $item->tipo);

        return view('transporte-escolar::viagens.show', [
            'viagem' => $registro,
            'alunos' => $alunos,
            'eventos' => $eventos,
            'km' => KmRealizado::deOdometro(
                $registro->km_inicial === null ? null : (float) $registro->km_inicial,
                $registro->km_final === null ? null : (float) $registro->km_final,
            ),
            'qrs' => $alunos->mapWithKeys(fn ($aluno) => [$aluno->ref_cod_aluno => QrCodigo::svg('TE-' . $aluno->ref_cod_aluno)]),
        ]);
    }

    public function evento(Request $request, int $viagem): RedirectResponse
    {
        $this->visivel($viagem);
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'tipo' => ['required', 'in:embarque,desembarque,falta'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $pertence = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.viagem as v', 'v.rota_id', '=', 'ar.rota_id')
            ->where('v.id', $viagem)
            ->where('ar.ref_cod_aluno', $dados['aluno_id'])
            ->exists();

        if (!$pertence) {
            return back()->with('erro', 'Este aluno não está na rota da viagem.');
        }

        if ($dados['tipo'] === 'falta') {
            DB::table('transporte_escolar.embarque')
                ->where('viagem_id', $viagem)
                ->where('ref_cod_aluno', $dados['aluno_id'])
                ->where('tipo', 'embarque')
                ->delete();

            return back();
        }

        DB::table('transporte_escolar.embarque')
            ->where('viagem_id', $viagem)
            ->where('ref_cod_aluno', $dados['aluno_id'])
            ->where('tipo', 'falta')
            ->delete();

        DB::table('transporte_escolar.embarque')->updateOrInsert(
            ['viagem_id' => $viagem, 'ref_cod_aluno' => $dados['aluno_id'], 'tipo' => $dados['tipo']],
            [
                'ocorrido_em' => now(),
                'latitude' => $dados['latitude'] ?? null,
                'longitude' => $dados['longitude'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        RegistrarAviso::aposEvento(
            $viagem,
            (int) $dados['aluno_id'],
            $dados['tipo'],
            isset($dados['latitude']) ? (float) $dados['latitude'] : null,
            isset($dados['longitude']) ? (float) $dados['longitude'] : null,
        );
        TrilhaKm::atualizar($viagem);

        return back()->with('ok', 'Registro do transporte gravado. A falta na aula continua no diário da escola.');
    }

    private function visivel(int $viagem): object
    {
        $ids = RedeConsulta::rotas()->pluck('r.id');
        $registro = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('v.id', $viagem)
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->first(['v.*', 'r.descricao']);
        abort_if($registro === null, 404);

        return $registro;
    }

    private function papelInapto(?int $veiculoId, ?int $motoristaId): ?string
    {
        if (!Schema::hasTable('transporte_escolar.autorizacao_veiculo')) {
            return null;
        }

        $hoje = now()->toDateString();
        $autorizacao = null;
        $licenciamento = null;
        $cnh = null;
        $curso = null;
        $temVeiculo = $veiculoId !== null && $veiculoId > 0;
        $temMotorista = $motoristaId !== null && $motoristaId > 0;

        if ($temVeiculo) {
            $veiculo = DB::table('transporte_escolar.veiculo')->where('id', $veiculoId)->first();

            if ($veiculo === null) {
                return 'Veículo não encontrado.';
            }

            $licenciamento = $veiculo->licenciamento_vencimento === null ? null : (string) $veiculo->licenciamento_vencimento;
            $autorizacao = DB::table('transporte_escolar.autorizacao_veiculo')->where('veiculo_id', $veiculoId)->max('validade');
            $autorizacao = $autorizacao === null ? null : (string) $autorizacao;
        }

        if ($temMotorista) {
            $motorista = DB::table('transporte_escolar.motorista')->where('id', $motoristaId)->first();

            if ($motorista === null) {
                return 'Motorista não encontrado.';
            }

            $cnh = $motorista->vencimento_cnh === null ? null : (string) $motorista->vencimento_cnh;
            $curso = $hoje;

            if (Schema::hasColumn('transporte_escolar.motorista', 'curso_escolar_validade')) {
                $curso = $motorista->curso_escolar_validade === null ? null : (string) $motorista->curso_escolar_validade;
            }
        }

        return AptidaoViagem::motivo($autorizacao, $licenciamento, $cnh, $curso, $temVeiculo, $temMotorista, $hoje);
    }
}
