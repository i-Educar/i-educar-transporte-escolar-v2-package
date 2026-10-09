<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\CarteirinhaPdf;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\DataTela;
use iEducar\Packages\BuritiTransporteEscolar\Support\FotoIdentificacao;
use iEducar\Packages\BuritiTransporteEscolar\Support\IdentidadeMunicipio;
use iEducar\Packages\BuritiTransporteEscolar\Support\ResponsaveisAluno;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CarteirinhaController
{
    public function motoristas(Request $request): Response
    {
        return $this->equipe($request, 'motorista');
    }

    public function monitores(Request $request): Response
    {
        return $this->equipe($request, 'monitor');
    }

    public function alunos(Request $request): Response
    {
        $ano = (int) $request->input('ano', date('Y'));
        $busca = trim((string) $request->input('q', ''));
        $todas = $request->input('modo') === 'todas';
        $pares = $this->pares($request);
        $rotas = RedeConsulta::rotas($ano)->pluck('r.id')->all() ?: [0];

        if (!$todas && $pares === []) {
            abort(422, 'Selecione ao menos um aluno.');
        }

        $consulta = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'ar.rota_id')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->leftJoin('transporte_escolar.ponto as pt', 'pt.id', '=', 'ar.ponto_id')
            ->leftJoin(DB::raw('(SELECT idpes, min(fone)::text AS fone FROM cadastro.fone_pessoa GROUP BY idpes) tel'), 'tel.idpes', '=', 'a.ref_idpes')
            ->where('ar.ano', $ano)
            ->whereIn('r.id', $rotas)
            ->when($busca !== '', function ($q) use ($busca) {
                $q->where(function ($filtro) use ($busca) {
                    $filtro->where('p.nome', 'ilike', '%' . $busca . '%')
                        ->orWhereRaw("regexp_replace(coalesce(f.cpf::text, ''), '\\D', '', 'g') = ?", [preg_replace('/\D/', '', $busca) ?: '0'])
                        ->orWhereExists(function ($matricula) use ($busca) {
                            $matricula->select(DB::raw('1'))
                                ->from('pmieducar.matricula as m')
                                ->whereColumn('m.ref_cod_aluno', 'a.cod_aluno')
                                ->where('m.ativo', 1)
                                ->where('m.cod_matricula', ctype_digit($busca) ? (int) $busca : 0);
                        });
                });
            })
            ->when(!$todas, function ($q) use ($pares) {
                $q->where(function ($filtro) use ($pares) {
                    foreach ($pares as [$aluno, $rota]) {
                        $filtro->orWhere(function ($um) use ($aluno, $rota) {
                            $um->where('ar.ref_cod_aluno', $aluno)->where('ar.rota_id', $rota);
                        });
                    }
                });
            })
            ->orderBy('p.nome')
            ->limit(401)
            ->select([
                'ar.ref_cod_aluno',
                'ar.rota_id',
                'ar.turno',
                'ar.ponto_id',
                'p.nome',
                'f.cpf',
                'f.data_nasc',
                'f.nome_responsavel',
                'tel.fone',
                'r.descricao',
                'r.ano',
                'r.tipo_rota',
                'r.sentido',
                'r.periodo',
                'pt.descricao as ponto',
            ]);
        $linhas = $consulta->get();
        abort_if($linhas->isEmpty(), 422, 'Nenhum aluno encontrado para a carteirinha.');
        abort_if($linhas->count() > 400, 422, 'Há mais de 400 alunos nesse recorte. Filtre a lista antes de emitir.');
        $frota = $this->frota($linhas->pluck('rota_id')->all());
        $responsaveis = ResponsaveisAluno::mapa($linhas->pluck('ref_cod_aluno')->all());
        $horarios = $this->horarios($linhas->pluck('rota_id')->all(), $linhas->pluck('ponto_id')->filter()->all());
        $municipio = IdentidadeMunicipio::rotulo();
        $pessoas = [];

        foreach ($linhas as $linha) {
            $veiculo = $frota[(int) $linha->rota_id] ?? null;
            $bloco = $responsaveis[(int) $linha->ref_cod_aluno] ?? ['ieducar' => [], 'transporte' => ['nome' => '', 'telefone' => '']];
            $nomes = array_map(static fn (array $item): string => trim($item['papel'] . ': ' . $item['nome'] . ($item['telefone'] !== '' ? ' ' . $item['telefone'] : '')), $bloco['ieducar']);

            if ($bloco['transporte']['nome'] !== '') {
                $nomes[] = trim('Transporte: ' . $bloco['transporte']['nome'] . ($bloco['transporte']['telefone'] !== '' ? ' ' . $bloco['transporte']['telefone'] : ''));
            }
            $hora = $horarios[(int) $linha->rota_id . ':' . (int) $linha->ponto_id] ?? null;
            $pessoas[] = [
                'papel' => 'Aluno',
                'nome' => (string) $linha->nome,
                'municipio' => $municipio,
                'foto' => FotoIdentificacao::retrato('aluno', (int) $linha->ref_cod_aluno),
                'linhas' => [
                    ['Nascimento', DataTela::dia($linha->data_nasc)],
                    ['CPF', $this->cpf($linha->cpf)],
                    ['Responsável', implode(' · ', $nomes)],
                    ['Telefone', (string) ($linha->fone ?: '')],
                ],
                'verso' => [
                    ['Rota', (string) $linha->descricao],
                    ['Ano', (string) $linha->ano],
                    ['Turno', Codigos::PERIODO[$linha->turno] ?? (string) ($linha->turno ?: '')],
                    ['Sentido', Codigos::SENTIDO[$linha->sentido] ?? (string) $linha->sentido],
                    ['Percurso', Codigos::TIPO_ROTA[$linha->tipo_rota] ?? (string) $linha->tipo_rota],
                    ['Período da rota', Codigos::PERIODO[$linha->periodo] ?? (string) $linha->periodo],
                    ['Veículo', $veiculo['veiculo'] ?? ''],
                    ['Motorista', $veiculo['motorista'] ?? ''],
                    ['Embarque', (string) ($linha->ponto ?: '')],
                    ['Horário', $hora ? substr((string) $hora, 0, 5) : ''],
                ],
            ];
        }

        return $this->pdf(CarteirinhaPdf::montar($pessoas), 'carteirinhas-alunos');
    }

    private function equipe(Request $request, string $tipo): Response
    {
        $tabela = $tipo === 'motorista' ? 'transporte_escolar.motorista' : 'transporte_escolar.monitor';
        $todas = $request->input('modo') === 'todas';
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));

        if (!$todas && $ids === []) {
            abort(422, 'Selecione ao menos uma pessoa.');
        }

        $colunas = $tipo === 'motorista'
            ? ['m.id', 'p.nome', 'm.matricula_funcional', 'm.cnh', 'm.tipo_cnh', 'm.vencimento_cnh', 'm.vinculo', 'm.turno']
            : ['m.id', 'p.nome', 'm.matricula_funcional', 'm.turno'];
        $linhas = DB::table($tabela . ' as m')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'm.ref_idpes')
            ->leftJoin(DB::raw('(SELECT idpes, min(fone)::text AS fone FROM cadastro.fone_pessoa GROUP BY idpes) tel'), 'tel.idpes', '=', 'm.ref_idpes')
            ->when(!$todas, fn ($q) => $q->whereIn('m.id', $ids))
            ->orderBy('p.nome')
            ->limit(401)
            ->get(array_merge($colunas, ['f.cpf', 'tel.fone']));
        abort_if($linhas->isEmpty(), 422, 'Nenhuma pessoa encontrada para a carteirinha.');
        abort_if($linhas->count() > 400, 422, 'Há mais de 400 pessoas. Filtre antes de emitir todas.');
        $municipio = IdentidadeMunicipio::rotulo();
        $papel = $tipo === 'motorista' ? 'Motorista' : 'Monitor';
        $pessoas = [];

        foreach ($linhas as $linha) {
            $campos = [
                ['Matrícula', (string) ($linha->matricula_funcional ?: '')],
                ['CPF', $this->cpf($linha->cpf)],
                ['Telefone', (string) ($linha->fone ?: '')],
                ['Turno', (string) ($linha->turno ?: '')],
            ];

            if ($tipo === 'motorista') {
                $campos[] = ['CNH', (string) ($linha->cnh ?: '')];
                $campos[] = ['Categoria', (string) ($linha->tipo_cnh ?: '')];
                $campos[] = ['Validade da CNH', DataTela::dia($linha->vencimento_cnh)];
                $campos[] = ['Vínculo', Codigos::VINCULO[$linha->vinculo] ?? (string) ($linha->vinculo ?: '')];
            }

            $pessoas[] = [
                'papel' => $papel,
                'nome' => (string) $linha->nome,
                'municipio' => $municipio,
                'foto' => FotoIdentificacao::retrato($tipo, (int) $linha->id),
                'linhas' => $campos,
                'verso' => null,
            ];
        }

        return $this->pdf(CarteirinhaPdf::montar($pessoas), 'carteirinhas-' . $tipo . 's');
    }

    /**
     * @param list<int> $rotas
     * @return array<int, array{veiculo: string, motorista: string}>
     */
    private function frota(array $rotas): array
    {
        $rotas = array_values(array_unique(array_filter(array_map('intval', $rotas))));

        if ($rotas === []) {
            return [];
        }

        $mapa = [];

        foreach (DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->leftJoin('transporte_escolar.motorista as m', 'm.id', '=', 'v.motorista_id')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->whereIn('i.rota_id', $rotas)
            ->whereNotNull('i.veiculo_id')
            ->orderBy('i.seq')
            ->get(['i.rota_id', 'v.placa', 'v.descricao', 'p.nome as motorista']) as $linha) {
            $rota = (int) $linha->rota_id;

            if (isset($mapa[$rota])) {
                continue;
            }

            $veiculo = trim((string) $linha->placa . ($linha->descricao ? ' · ' . $linha->descricao : ''));
            $mapa[$rota] = ['veiculo' => $veiculo, 'motorista' => (string) ($linha->motorista ?: '')];
        }

        return $mapa;
    }

    /**
     * @param list<int> $rotas
     * @param list<int> $pontos
     * @return array<string, string>
     */
    private function horarios(array $rotas, array $pontos): array
    {
        $rotas = array_values(array_unique(array_filter(array_map('intval', $rotas))));
        $pontos = array_values(array_unique(array_filter(array_map('intval', $pontos))));

        if ($rotas === [] || $pontos === []) {
            return [];
        }

        $mapa = [];

        foreach (DB::table('transporte_escolar.itinerario')
            ->whereIn('rota_id', $rotas)
            ->whereIn('ponto_id', $pontos)
            ->whereNotNull('hora')
            ->orderBy('seq')
            ->get(['rota_id', 'ponto_id', 'hora']) as $linha) {
            $chave = (int) $linha->rota_id . ':' . (int) $linha->ponto_id;
            $mapa[$chave] ??= (string) $linha->hora;
        }

        return $mapa;
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function pares(Request $request): array
    {
        $pares = [];

        foreach ((array) $request->input('vinculos', []) as $item) {
            if (preg_match('/^(\d+):(\d+)$/', (string) $item, $partes) === 1) {
                $pares[] = [(int) $partes[1], (int) $partes[2]];
            }
        }

        return $pares;
    }

    private function cpf(mixed $valor): string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor) ?? '';

        if (strlen($digitos) !== 11) {
            return $digitos;
        }

        return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
    }

    private function pdf(string $conteudo, string $nome): Response
    {
        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nome . '.pdf"',
        ]);
    }
}
