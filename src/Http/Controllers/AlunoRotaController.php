<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AtribuicaoAluno;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class AlunoRotaController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $busca = trim((string) $request->input('q', ''));
        $matriculas = RedeConsulta::matriculasAtivas($ano);

        if ($busca !== '') {
            $matriculas->where(function ($q) use ($busca) {
                $q->where('p.nome', 'ilike', '%' . $busca . '%')
                    ->orWhereRaw("regexp_replace(f.cpf::text, '\\D', '', 'g') = ?", [preg_replace('/\D/', '', $busca)]);
            });
        }

        return view('transporte-escolar::alunos.index', [
            'ano' => $ano,
            'busca' => $busca,
            'matriculas' => $busca === '' ? collect() : $matriculas->limit(30)->get(),
            'rotas' => RedeConsulta::rotas($ano)->get(),
            'vinculos' => DB::table('transporte_escolar.aluno_rota as ar')
                ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
                ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                ->where('ar.ano', $ano)
                ->whereIn('r.id', RedeConsulta::rotas($ano)->pluck('r.id')->all() ?: [0])
                ->orderBy('p.nome')
                ->get(['ar.ref_cod_aluno', 'ar.rota_id', 'p.nome', 'r.descricao', 'ar.turno', 'ar.residencia_latitude', 'ar.residencia_longitude']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'rota_id' => ['required', 'integer'],
            'turno' => ['nullable', 'string', 'max:40'],
            'residencia_latitude' => ['nullable', 'numeric'],
            'residencia_longitude' => ['nullable', 'numeric'],
        ]);

        try {
            AtribuicaoAluno::vincular(
                (int) $dados['aluno_id'],
                (int) $dados['rota_id'],
                null,
                $dados['turno'] ?? null,
                isset($dados['residencia_latitude']) ? (float) $dados['residencia_latitude'] : null,
                isset($dados['residencia_longitude']) ? (float) $dados['residencia_longitude'] : null,
            );
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', 'Aluno vinculado. A ficha do Censo foi atualizada com a rota.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'rota_id' => ['required', 'integer'],
        ]);

        try {
            AtribuicaoAluno::desvincular((int) $dados['aluno_id'], (int) $dados['rota_id']);
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', 'Aluno retirado da rota.');
    }
}
