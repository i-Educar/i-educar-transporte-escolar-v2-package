<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\AtribuicaoAluno;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\FotoIdentificacao;
use iEducar\Packages\BuritiTransporteEscolar\Support\ListaEspera;
use iEducar\Packages\BuritiTransporteEscolar\Support\Pagina;
use iEducar\Packages\BuritiTransporteEscolar\Support\ResponsaveisAluno;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class AlunoRotaController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $busca = trim((string) $request->input('q', ''));
        [$ordem, $direcao, $coluna] = $this->ordenacao($request);
        $ids = RedeConsulta::rotas($ano)->pluck('r.id')->all() ?: [0];
        $temResponsavel = Schema::hasTable('transporte_escolar.responsavel_transporte');
        $responsavelSql = $temResponsavel
            ? "COALESCE(NULLIF(rt.nome, ''), f.nome_responsavel)"
            : 'f.nome_responsavel';
        $vinculos = DB::table('transporte_escolar.aluno_rota as ar')
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
            ->leftJoin(DB::raw('(SELECT idpes, min(fone)::text AS fone FROM cadastro.fone_pessoa GROUP BY idpes) tel'), 'tel.idpes', '=', 'a.ref_idpes')
            ->when($temResponsavel, function ($consulta) {
                $consulta->leftJoin('transporte_escolar.responsavel_transporte as rt', 'rt.ref_cod_aluno', '=', 'ar.ref_cod_aluno');
            })
            ->where('ar.ano', $ano)
            ->whereIn('r.id', $ids)
            ->when($busca !== '', function ($consulta) use ($busca, $temResponsavel) {
                $consulta->where(function ($q) use ($busca, $temResponsavel) {
                    $q->where('p.nome', 'ilike', '%' . $busca . '%')
                        ->orWhere('r.descricao', 'ilike', '%' . $busca . '%')
                        ->orWhere('ar.cartao', 'ilike', '%' . $busca . '%')
                        ->orWhere('f.nome_responsavel', 'ilike', '%' . $busca . '%')
                        ->orWhere('m.cod_matricula', ctype_digit($busca) ? (int) $busca : 0)
                        ->orWhereRaw("regexp_replace(coalesce(f.cpf::text, ''), '\\D', '', 'g') = ?", [preg_replace('/\D/', '', $busca) ?: '0']);

                    if ($temResponsavel) {
                        $q->orWhere('rt.nome', 'ilike', '%' . $busca . '%');
                    }
                });
            })
            ->selectRaw(
                'DISTINCT ON (ar.ref_cod_aluno, ar.rota_id) ar.ref_cod_aluno, ar.rota_id, p.nome, r.descricao, ar.turno, ar.cartao, ar.residencia_latitude, ar.residencia_longitude, m.cod_matricula, r.periodo, t.turma_turno_id, pe.nome as escola, s.nm_serie, t.nm_turma, ' . $responsavelSql . ' as responsavel, tel.fone, NULL::bigint as solicitacao_id, NULL::varchar as protocolo, \'aluno\'::varchar as origem',
            )
            ->orderBy('ar.ref_cod_aluno')
            ->orderBy('ar.rota_id')
            ->orderByRaw('m.cod_matricula DESC NULLS LAST');
        $semMatricula = DB::table('transporte_escolar.solicitacao as s')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 's.vinculo_rota_id')
            ->where('s.situacao', 'conferida')
            ->whereNull('s.ref_cod_aluno')
            ->where('s.ano', $ano)
            ->whereIn('r.id', $ids)
            ->when($busca !== '', function ($consulta) use ($busca) {
                $consulta->where(function ($q) use ($busca) {
                    $q->where('s.nome_aluno', 'ilike', '%' . $busca . '%')
                        ->orWhere('s.protocolo', 'ilike', '%' . $busca . '%')
                        ->orWhere('r.descricao', 'ilike', '%' . $busca . '%');
                });
            })
            ->selectRaw("NULL::integer as ref_cod_aluno, s.vinculo_rota_id as rota_id, s.nome_aluno as nome, r.descricao, r.periodo as turno, NULL::varchar as cartao, NULL::numeric as residencia_latitude, NULL::numeric as residencia_longitude, NULL::integer as cod_matricula, r.periodo, NULL::integer as turma_turno_id, NULL::varchar as escola, NULL::varchar as nm_serie, NULL::varchar as nm_turma, NULL::varchar as responsavel, NULL::text as fone, s.id as solicitacao_id, s.protocolo, 'sem'::varchar as origem");
        $alunos = DB::query()->fromSub($vinculos, 'alunos')->unionAll($semMatricula);
        $consulta = DB::query()->fromSub($alunos, 'lista')->orderByRaw($coluna . ' ' . $direcao . ' NULLS LAST')->orderBy('rota_id');
        $total = (clone $consulta)->count();
        $pagina = Pagina::fatia($total, (int) $request->input('pagina', 1));
        $lista = $consulta->offset($pagina['inicio'])->limit($pagina['tamanho'])->get();
        $responsaveis = ResponsaveisAluno::mapa($lista->pluck('ref_cod_aluno')->filter()->all());

        foreach ($lista as $linha) {
            if ($linha->origem === 'sem') {
                $linha->id = $linha->solicitacao_id;
                $linha->avisos = ['Sem matrícula ativa neste ano. A matrícula é feita no i-Educar.'];
                $linha->responsaveis = [];
                $linha->transporte_nome = '';
                $linha->transporte_telefone = '';

                continue;
            }

            $bloco = $responsaveis[(int) $linha->ref_cod_aluno] ?? ['ieducar' => [], 'transporte' => ['nome' => '', 'telefone' => '']];
            $linha->responsaveis = $bloco['ieducar'];
            $linha->transporte_nome = $bloco['transporte']['nome'];
            $linha->transporte_telefone = $bloco['transporte']['telefone'];
            $linha->avisos = $this->avisosVinculo($linha);
        }

        return view('transporte-escolar::alunos.index', [
            'ano' => $ano,
            'busca' => $busca,
            'ordem' => $ordem,
            'direcao' => $direcao,
            'admin' => VisaoRede::escolas() === null,
            'rotas' => $rotas = RedeConsulta::rotas($ano)->get(),
            'espera' => ListaEspera::porRota($rotas->pluck('id')->all()),
            'linhas' => $lista,
            'vinculos' => $lista->where('origem', 'aluno')->values(),
            'semMatricula' => $lista->where('origem', 'sem')->values(),
            'fotos' => FotoIdentificacao::mapa('aluno', $lista->pluck('ref_cod_aluno')->filter()->all()),
            'pagina' => $pagina,
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function ordenacao(Request $request): array
    {
        $colunas = [
            'matricula' => 'cod_matricula',
            'aluno' => 'nome',
            'rota' => 'descricao',
            'cartao' => 'cartao',
            'responsavel' => 'responsavel',
        ];
        $ordem = (string) $request->input('ordem', 'aluno');

        if (!isset($colunas[$ordem])) {
            $ordem = 'aluno';
        }

        $direcao = $request->input('direcao') === 'desc' ? 'desc' : 'asc';

        return [$ordem, $direcao, $colunas[$ordem]];
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'rota_id' => ['required', 'integer'],
            'rota_origem' => ['required', 'integer'],
            'turno' => ['required', 'in:manha,tarde,noite,integral'],
            'cartao' => ['nullable', 'string', 'max:40'],
            'residencia_latitude' => ['nullable', 'numeric'],
            'residencia_longitude' => ['nullable', 'numeric'],
            'responsavel_transporte' => ['nullable', 'string', 'max:150'],
            'responsavel_transporte_telefone' => ['nullable', 'string', 'max:20'],
        ]);

        if ((int) $dados['rota_id'] !== (int) $dados['rota_origem']) {
            $recado = ListaEspera::recado((int) $dados['rota_id']);

            if ($recado !== null) {
                return back()->with('erro', $recado);
            }
        }

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
            FotoIdentificacao::substituir($request, 'aluno', (int) $dados['aluno_id'], 'carteirinha');
            ResponsaveisAluno::gravar(
                (int) $dados['aluno_id'],
                (string) ($dados['responsavel_transporte'] ?? ''),
                (string) ($dados['responsavel_transporte_telefone'] ?? ''),
            );
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', 'Vínculo do aluno atualizado.');
    }

    public function buscar(Request $request): JsonResponse
    {
        $ano = (int) $request->input('ano', date('Y'));
        $q = trim((string) $request->input('q', ''));
        $digitos = preg_replace('/\D/', '', $q) ?? '';

        if (mb_strlen($q) < 2) {
            return response()->json(['alunos' => []]);
        }

        $linhas = RedeConsulta::matriculasAtivas($ano)
            ->addSelect('m.cod_matricula')
            ->where(function ($consulta) use ($q, $digitos) {
                $consulta->where('p.nome', 'ilike', '%' . $q . '%');

                if ($digitos !== '' && ctype_digit($digitos)) {
                    $consulta->orWhere('m.cod_matricula', (int) $digitos)
                        ->orWhereRaw("regexp_replace(coalesce(f.cpf::text, ''), '\\D', '', 'g') = ?", [$digitos]);
                }
            })
            ->limit(8)
            ->get();

        return response()->json([
            'alunos' => $linhas->map(static fn ($linha): array => [
                'id' => (int) $linha->cod_aluno,
                'matricula' => (int) $linha->cod_matricula,
                'rotulo' => trim($linha->cod_matricula . ' · ' . $linha->nome . ' · ' . ($linha->escola ?: '') . ' · ' . ($linha->nm_serie ?: '') . ' ' . ($linha->nm_turma ?: '')),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aluno_id' => ['required', 'integer'],
            'matricula_id' => ['nullable', 'integer'],
            'rota_id' => ['required', 'integer'],
            'residencia_latitude' => ['nullable', 'numeric'],
            'residencia_longitude' => ['nullable', 'numeric'],
        ]);
        $rota = DB::table('transporte_escolar.rota')->where('id', (int) $dados['rota_id'])->first();
        $turno = (string) ($rota->periodo ?? '');

        if ($rota === null || !isset(Codigos::PERIODO[$turno])) {
            return back()->with('erro', 'A rota escolhida não tem um turno definido.');
        }

        $recado = ListaEspera::recado((int) $dados['rota_id']);

        if ($recado !== null) {
            return back()->with('erro', $recado);
        }

        try {
            AtribuicaoAluno::vincular(
                (int) $dados['aluno_id'],
                (int) $dados['rota_id'],
                null,
                $turno,
                isset($dados['residencia_latitude']) ? (float) $dados['residencia_latitude'] : null,
                isset($dados['residencia_longitude']) ? (float) $dados['residencia_longitude'] : null,
                isset($dados['matricula_id']) ? (int) $dados['matricula_id'] : null,
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

    /**
     * @return list<string>
     */
    private function avisosVinculo(object $linha): array
    {
        $avisos = [];

        if (empty($linha->cod_matricula)) {
            $avisos[] = 'Sem matrícula ativa neste ano. A matrícula é feita no i-Educar.';
        }

        $turnoMatricula = Codigos::periodoTurma(isset($linha->turma_turno_id) ? (int) $linha->turma_turno_id : null);
        $turnoRota = (string) ($linha->periodo ?: $linha->turno);

        if ($turnoMatricula !== null && $turnoRota !== '' && $turnoMatricula !== $turnoRota) {
            $avisos[] = 'A matrícula está no turno ' . (Codigos::PERIODO[$turnoMatricula] ?? $turnoMatricula) . ' e a rota é ' . (Codigos::PERIODO[$turnoRota] ?? $turnoRota) . '.';
        }

        return $avisos;
    }
}
