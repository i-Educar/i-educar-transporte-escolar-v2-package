<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AtribuicaoAluno;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Pagina;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
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
        $ids = RedeConsulta::rotas($ano)->pluck('r.id')->all() ?: [0];
        $vinculos = $this->comContato(
            DB::table('transporte_escolar.aluno_rota as ar')
                ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
                ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
                ->leftJoin('pmieducar.matricula as m', function ($join) use ($ano) {
                    $join->on('m.ref_cod_aluno', '=', 'a.cod_aluno')->where('m.ano', $ano)->where('m.ativo', 1);
                })
                ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'm.ref_ref_cod_escola')
                ->leftJoin('cadastro.pessoa as pe', 'pe.idpes', '=', 'e.ref_idpes')
                ->leftJoin('pmieducar.serie as s', 's.cod_serie', '=', 'm.ref_ref_cod_serie')
                ->leftJoin('pmieducar.matricula_turma as mt', function ($join) {
                    $join->on('mt.ref_cod_matricula', '=', 'm.cod_matricula')->where('mt.ativo', 1);
                })
                ->leftJoin('pmieducar.turma as t', 't.cod_turma', '=', 'mt.ref_cod_turma')
                ->where('ar.ano', $ano)
                ->whereIn('r.id', $ids)
                ->when($busca !== '', function ($consulta) use ($busca) {
                    $consulta->where(function ($q) use ($busca) {
                        $q->where('p.nome', 'ilike', '%' . $busca . '%')
                            ->orWhere('m.cod_matricula', ctype_digit($busca) ? (int) $busca : 0)
                            ->orWhereRaw("regexp_replace(coalesce(f.cpf::text, ''), '\\D', '', 'g') = ?", [preg_replace('/\D/', '', $busca) ?: '0']);
                    });
                })
                ->select(
                    'ar.ref_cod_aluno',
                    'ar.rota_id',
                    'p.nome',
                    'r.descricao',
                    'ar.turno',
                    'ar.cartao',
                    'ar.residencia_latitude',
                    'ar.residencia_longitude',
                    'm.cod_matricula',
                    'pe.nome as escola',
                    's.nm_serie',
                    't.nm_turma',
                    'f.nome_responsavel',
                )
                ->orderBy('p.nome'),
        );
        $total = (clone $vinculos)->count();
        $pagina = Pagina::fatia($total, (int) $request->input('pagina', 1));

        return view('transporte-escolar::alunos.index', [
            'ano' => $ano,
            'busca' => $busca,
            'admin' => VisaoRede::escolas() === null,
            'rotas' => RedeConsulta::rotas($ano)->get(),
            'vinculos' => $vinculos->offset($pagina['inicio'])->limit($pagina['tamanho'])->get(),
            'pagina' => $pagina,
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'rota_id' => ['required', 'integer'],
            'rota_origem' => ['required', 'integer'],
            'turno' => ['nullable', 'string', 'max:40'],
            'cartao' => ['nullable', 'string', 'max:40'],
            'residencia_latitude' => ['nullable', 'numeric'],
            'residencia_longitude' => ['nullable', 'numeric'],
        ]);

        try {
            if ((int) $dados['rota_id'] !== (int) $dados['rota_origem']) {
                AtribuicaoAluno::desvincular((int) $dados['aluno_id'], (int) $dados['rota_origem']);
            }

            AtribuicaoAluno::vincular(
                (int) $dados['aluno_id'],
                (int) $dados['rota_id'],
                null,
                $dados['turno'] ?? null,
                isset($dados['residencia_latitude']) ? (float) $dados['residencia_latitude'] : null,
                isset($dados['residencia_longitude']) ? (float) $dados['residencia_longitude'] : null,
            );
            AtribuicaoAluno::cartao((int) $dados['aluno_id'], (int) $dados['rota_id'], (string) ($dados['cartao'] ?? ''));
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', 'Vínculo do aluno atualizado.');
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

    public function cartao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'rota_id' => ['required', 'integer'],
            'cartao' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            AtribuicaoAluno::cartao((int) $dados['aluno_id'], (int) $dados['rota_id'], (string) ($dados['cartao'] ?? ''));
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', 'Cartão do aluno atualizado.');
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

    private function comContato($consulta)
    {
        return $consulta
            ->leftJoin(DB::raw('(SELECT idpes, min(fone)::text AS fone FROM cadastro.fone_pessoa GROUP BY idpes) tel'), 'tel.idpes', '=', 'a.ref_idpes')
            ->addSelect('tel.fone', 'f.nome_responsavel', 'm.cod_matricula');
    }
}
