<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Coordenada;
use iEducar\Packages\BuritiTransporteEscolar\Support\Pagina;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\UsoPonto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FrotaController
{
    public function index(Request $request): View
    {
        $recurso = (string) $request->route('recurso', $request->input('recurso'));
        $processo = $this->processo($recurso);

        $consulta = $this->linhas($recurso);
        $fatia = Pagina::fatia((int) (clone $consulta)->count(), $request->input('pagina'));

        return view('transporte-escolar::frota.index', [
            'recurso' => $recurso,
            'titulo' => $this->titulo($recurso),
            'linhas' => $consulta->offset($fatia['inicio'])->limit($fatia['tamanho'])->get(),
            'podeGravar' => Acesso::permite($processo, 'gravar'),
            'podeExcluir' => Acesso::permite($processo, 'excluir'),
            'codigos' => Codigos::class,
            'pagina' => $fatia['pagina'],
            'paginas' => $fatia['paginas'],
            'total' => $fatia['total'],
        ]);
    }

    public function csv(Request $request): Response
    {
        $recurso = (string) $request->route('recurso');
        $saida = fopen('php://temp', 'w+');
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $colunas = $this->colunas($recurso);
        fputcsv($saida, array_values($colunas), ';');

        foreach ($this->linhas($recurso)->get() as $linha) {
            $valores = [];

            foreach (array_keys($colunas) as $campo) {
                $valor = $linha->{$campo} ?? '';
                $valores[] = $campo === 'ativo' ? ($valor ? 'Sim' : 'Não') : $valor;
            }

            fputcsv($saida, $valores, ';');
        }

        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transporte-' . $recurso . '.csv"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $recurso = (string) $request->input('recurso');

        if ($recurso === 'pontos') {
            $erro = $this->criarPonto($request);

            return $erro === null
                ? back()->with('ok', 'Ponto gravado com a coordenada informada.')
                : back()->with('erro', $erro);
        }

        match ($recurso) {
            'empresas' => $this->empresa($request),
            'fornecedores' => $this->fornecedor($request),
            'motoristas' => $this->motorista($request),
            'monitores' => $this->monitor($request),
            'veiculos' => $this->veiculo($request),
            default => abort(404),
        };

        return back()->with('ok', 'Registro gravado a partir do cadastro de pessoas do i-Educar.');
    }

    public function atualizar(Request $request, int $registro): RedirectResponse
    {
        match ((string) $request->route('recurso')) {
            'empresas' => $this->atualizarEmpresa($request, $registro),
            'fornecedores' => $this->atualizarFornecedor($request, $registro),
            'motoristas' => $this->atualizarMotorista($request, $registro),
            'monitores' => $this->atualizarMonitor($request, $registro),
            'veiculos' => $this->atualizarVeiculo($request, $registro),
            default => abort(404),
        };

        return back()->with('ok', 'Registro atualizado. A alteração fica na auditoria.');
    }

    private function empresa(Request $request): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $request->input('cnpj'));
        $idpes = DB::table('cadastro.juridica')->whereRaw("regexp_replace(cnpj::text, '\\D', '', 'g') = ?", [$cnpj])->value('idpes');
        abort_if($idpes === null, 422, 'CNPJ não encontrado em cadastro.juridica.');
        DB::table('transporte_escolar.empresa')->updateOrInsert(
            ['ref_idpes' => $idpes],
            ['observacao' => $request->input('observacao'), 'origem' => 'ieducar', 'updated_at' => now(), 'created_at' => now()],
        );
        $this->contato((int) $idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function fornecedor(Request $request): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $request->input('cnpj'));
        $idpes = DB::table('cadastro.juridica')->whereRaw("regexp_replace(cnpj::text, '\\D', '', 'g') = ?", [$cnpj])->value('idpes')
            ?? DB::table('cadastro.fisica')->whereRaw("regexp_replace(cpf::text, '\\D', '', 'g') = ?", [$cnpj])->value('idpes');
        abort_if($idpes === null, 422, 'Documento não encontrado no cadastro de pessoas.');
        DB::table('transporte_escolar.fornecedor')->updateOrInsert(
            ['ref_idpes' => $idpes],
            ['observacao' => $request->input('observacao'), 'origem' => 'ieducar', 'updated_at' => now(), 'created_at' => now()],
        );
        $this->contato((int) $idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function motorista(Request $request): void
    {
        $idpes = $this->fisica((string) $request->input('cpf'));
        DB::table('transporte_escolar.motorista')->updateOrInsert(
            ['ref_idpes' => $idpes],
            [
                'cnh' => $request->input('cnh'),
                'tipo_cnh' => $request->input('tipo_cnh'),
                'dt_habilitacao' => $request->input('dt_habilitacao') ?: null,
                'vencimento_cnh' => $request->input('vencimento_cnh') ?: null,
                'vinculo' => $request->input('vinculo'),
                'turno' => $request->input('turno'),
                'salario_mensal' => $request->input('salario_mensal') ?: null,
                'encargo_percent' => $request->input('encargo_percent') ?: null,
                'carga_horaria' => $request->input('carga_horaria') ?: null,
                'matricula_funcional' => $request->input('matricula_funcional') ?: null,
                'origem' => 'ieducar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $this->contato((int) $idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function monitor(Request $request): void
    {
        $idpes = $this->fisica((string) $request->input('cpf'));
        DB::table('transporte_escolar.monitor')->updateOrInsert(
            ['ref_idpes' => $idpes],
            [
                'turno' => $request->input('turno'),
                'salario_mensal' => $request->input('salario_mensal') ?: null,
                'encargo_percent' => $request->input('encargo_percent') ?: null,
                'carga_horaria' => $request->input('carga_horaria') ?: null,
                'matricula_funcional' => $request->input('matricula_funcional') ?: null,
                'origem' => 'ieducar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $this->contato((int) $idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function veiculo(Request $request): void
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string'],
            'placa' => ['nullable', 'string', 'max:10'],
            'renavam' => ['nullable', 'string'],
            'passageiros' => ['required', 'integer', 'min:0'],
            'tipo_censo' => ['required', 'integer'],
            'malha' => ['required', 'in:P,N'],
        ]);

        abort_unless(isset(Codigos::VEICULO_CENSO[(int) $dados['tipo_censo']]), 422);

        DB::table('transporte_escolar.veiculo')->updateOrInsert(
            ['placa' => $dados['placa'] !== '' ? strtoupper((string) $dados['placa']) : null],
            [
                'descricao' => $dados['descricao'],
                'renavam' => $dados['renavam'] ?? null,
                'passageiros' => $dados['passageiros'],
                'tipo_censo' => $dados['tipo_censo'],
                'malha' => $dados['malha'],
                'adaptado_pcd' => $request->boolean('adaptado_pcd'),
                'caminho_da_escola' => $request->boolean('caminho_da_escola'),
                'consumo_medio_km_l' => $request->input('consumo_medio_km_l') ?: null,
                'qtd_pneus' => $request->input('qtd_pneus') ?: null,
                'ipva_vencimento' => $request->input('ipva_vencimento') ?: null,
                'seguro_vencimento' => $request->input('seguro_vencimento') ?: null,
                'licenciamento_vencimento' => $request->input('licenciamento_vencimento') ?: null,
                'vistoria_vencimento' => $request->input('vistoria_vencimento') ?: null,
                'ativo' => true,
                'origem' => 'ieducar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function atualizarPonto(Request $request, int $ponto): RedirectResponse
    {
        if (DB::table('transporte_escolar.ponto')->where('id', $ponto)->doesntExist()) {
            abort(404);
        }

        $dados = $this->dadosPonto($request);
        if (is_string($dados)) {
            return back()->with('erro', $dados);
        }

        DB::table('transporte_escolar.ponto')->where('id', $ponto)->update($dados + ['updated_at' => now()]);

        return back()->with('ok', 'Ponto atualizado.');
    }

    private function atualizarEmpresa(Request $request, int $registro): void
    {
        $linha = DB::table('transporte_escolar.empresa')->where('id', $registro)->first();
        abort_if($linha === null, 404);
        DB::table('transporte_escolar.empresa')->where('id', $registro)->update([
            'observacao' => $request->input('observacao'),
            'updated_at' => now(),
        ]);
        $this->contato((int) $linha->ref_idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function atualizarFornecedor(Request $request, int $registro): void
    {
        $linha = DB::table('transporte_escolar.fornecedor')->where('id', $registro)->first();
        abort_if($linha === null, 404);
        DB::table('transporte_escolar.fornecedor')->where('id', $registro)->update([
            'observacao' => $request->input('observacao'),
            'updated_at' => now(),
        ]);
        $this->contato((int) $linha->ref_idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function atualizarMotorista(Request $request, int $registro): void
    {
        $linha = DB::table('transporte_escolar.motorista')->where('id', $registro)->first();
        abort_if($linha === null, 404);
        DB::table('transporte_escolar.motorista')->where('id', $registro)->update([
            'cnh' => $request->input('cnh'),
            'tipo_cnh' => $request->input('tipo_cnh'),
            'dt_habilitacao' => $request->input('dt_habilitacao') ?: null,
            'vencimento_cnh' => $request->input('vencimento_cnh') ?: null,
            'vinculo' => $request->input('vinculo'),
            'turno' => $request->input('turno'),
            'salario_mensal' => $request->input('salario_mensal') ?: null,
            'encargo_percent' => $request->input('encargo_percent') ?: null,
            'carga_horaria' => $request->input('carga_horaria') ?: null,
            'matricula_funcional' => $request->input('matricula_funcional') ?: null,
            'updated_at' => now(),
        ]);
        $this->contato((int) $linha->ref_idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function atualizarMonitor(Request $request, int $registro): void
    {
        $linha = DB::table('transporte_escolar.monitor')->where('id', $registro)->first();
        abort_if($linha === null, 404);
        DB::table('transporte_escolar.monitor')->where('id', $registro)->update([
            'turno' => $request->input('turno'),
            'salario_mensal' => $request->input('salario_mensal') ?: null,
            'encargo_percent' => $request->input('encargo_percent') ?: null,
            'carga_horaria' => $request->input('carga_horaria') ?: null,
            'matricula_funcional' => $request->input('matricula_funcional') ?: null,
            'updated_at' => now(),
        ]);
        $this->contato((int) $linha->ref_idpes, (string) $request->input('email'), (string) $request->input('telefone'));
    }

    private function atualizarVeiculo(Request $request, int $registro): void
    {
        abort_if(DB::table('transporte_escolar.veiculo')->where('id', $registro)->doesntExist(), 404);
        $dados = $request->validate([
            'descricao' => ['required', 'string'],
            'placa' => ['nullable', 'string', 'max:10'],
            'renavam' => ['nullable', 'string'],
            'passageiros' => ['required', 'integer', 'min:0'],
            'tipo_censo' => ['required', 'integer'],
            'malha' => ['required', 'in:P,N'],
        ]);
        abort_unless(isset(Codigos::VEICULO_CENSO[(int) $dados['tipo_censo']]), 422);
        DB::table('transporte_escolar.veiculo')->where('id', $registro)->update([
            'descricao' => $dados['descricao'],
            'placa' => $dados['placa'] !== '' ? strtoupper((string) $dados['placa']) : null,
            'renavam' => $dados['renavam'] ?? null,
            'passageiros' => $dados['passageiros'],
            'tipo_censo' => $dados['tipo_censo'],
            'malha' => $dados['malha'],
            'adaptado_pcd' => $request->boolean('adaptado_pcd'),
            'caminho_da_escola' => $request->boolean('caminho_da_escola'),
            'consumo_medio_km_l' => $request->input('consumo_medio_km_l') ?: null,
            'qtd_pneus' => $request->input('qtd_pneus') ?: null,
            'ipva_vencimento' => $request->input('ipva_vencimento') ?: null,
            'seguro_vencimento' => $request->input('seguro_vencimento') ?: null,
            'licenciamento_vencimento' => $request->input('licenciamento_vencimento') ?: null,
            'vistoria_vencimento' => $request->input('vistoria_vencimento') ?: null,
            'ativo' => $request->boolean('ativo'),
            'updated_at' => now(),
        ]);
    }

    private function contato(int $idpes, string $email, string $telefone): void
    {
        DB::table('cadastro.pessoa')->where('idpes', $idpes)->update([
            'email' => trim($email) === '' ? null : trim($email),
        ]);

        $digitos = preg_replace('/\D/', '', $telefone) ?? '';

        if ($digitos === '') {
            return;
        }

        $ddd = strlen($digitos) >= 10 ? (int) substr($digitos, 0, 2) : 0;
        $fone = (int) (strlen($digitos) >= 10 ? substr($digitos, 2) : $digitos);
        $tipo = strlen($digitos) >= 11 ? 3 : 1;
        $existe = DB::table('cadastro.fone_pessoa')->where('idpes', $idpes)->where('tipo', $tipo)->exists();
        $dados = ['ddd' => $ddd, 'fone' => $fone, 'origem_gravacao' => 'M', 'operacao' => $existe ? 'A' : 'I', 'data_rev' => now()];

        if ($existe) {
            DB::table('cadastro.fone_pessoa')->where('idpes', $idpes)->where('tipo', $tipo)->update($dados);

            return;
        }

        DB::table('cadastro.fone_pessoa')->insert($dados + [
            'idpes' => $idpes,
            'tipo' => $tipo,
            'data_cad' => now(),
        ]);
    }

    public function excluirPonto(int $ponto): RedirectResponse
    {
        $motivo = UsoPonto::motivoBloqueio(
            (int) DB::table('transporte_escolar.itinerario')->where('ponto_id', $ponto)->count(),
            (int) DB::table('transporte_escolar.aluno_rota')->where('ponto_id', $ponto)->count(),
        );

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        DB::table('transporte_escolar.ponto')->where('id', $ponto)->delete();

        return back()->with('ok', 'Ponto excluído.');
    }

    private function criarPonto(Request $request): ?string
    {
        $dados = $this->dadosPonto($request);

        if (is_string($dados)) {
            return $dados;
        }

        DB::table('transporte_escolar.ponto')->insert($dados + [
            'origem' => 'ieducar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return null;
    }

    /**
     * @return array<string, mixed>|string
     */
    private function dadosPonto(Request $request): array|string
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:70'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'raio_metros' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $motivo = Coordenada::motivo($dados['latitude'] ?? null, $dados['longitude'] ?? null);

        if ($motivo !== null) {
            return $motivo;
        }

        return [
            'descricao' => $dados['descricao'],
            'latitude' => Coordenada::valor($dados['latitude'] ?? null),
            'longitude' => Coordenada::valor($dados['longitude'] ?? null),
            'raio_metros' => $dados['raio_metros'] ?? null,
        ];
    }

    private function fisica(string $cpf): int
    {
        $digitos = preg_replace('/\D/', '', $cpf);
        $idpes = DB::table('cadastro.fisica')->whereRaw("regexp_replace(cpf::text, '\\D', '', 'g') = ?", [$digitos])->value('idpes');
        abort_if($idpes === null, 422, 'CPF não encontrado em cadastro.fisica.');

        return (int) $idpes;
    }

    private function linhas(string $recurso)
    {
        return match ($recurso) {
            'empresas' => DB::table('transporte_escolar.empresa as e')->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')->orderBy('p.nome')->select(['e.id', 'p.nome', 'p.email', 'j.cnpj', 'e.observacao'])->selectRaw("(select trim(to_char(ddd, 'FM999')) || fone::text from cadastro.fone_pessoa where idpes = p.idpes order by tipo desc limit 1) as telefone"),
            'fornecedores' => DB::table('transporte_escolar.fornecedor as f')->join('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_idpes')->orderBy('p.nome')->select(['f.id', 'p.nome', 'p.email', 'f.observacao'])->selectRaw("(select trim(to_char(ddd, 'FM999')) || fone::text from cadastro.fone_pessoa where idpes = p.idpes order by tipo desc limit 1) as telefone"),
            'motoristas' => DB::table('transporte_escolar.motorista as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->select(['m.id', 'm.ref_idpes', 'p.nome', 'p.email', 'm.cnh', 'm.tipo_cnh', 'm.dt_habilitacao', 'm.vencimento_cnh', 'm.vinculo', 'm.turno', 'm.salario_mensal', 'm.encargo_percent', 'm.carga_horaria', 'm.matricula_funcional'])->selectRaw("(select trim(to_char(ddd, 'FM999')) || fone::text from cadastro.fone_pessoa where idpes = p.idpes order by tipo desc limit 1) as telefone"),
            'monitores' => DB::table('transporte_escolar.monitor as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->select(['m.id', 'p.nome', 'p.email', 'm.turno', 'm.salario_mensal', 'm.encargo_percent', 'm.carga_horaria', 'm.matricula_funcional'])->selectRaw("(select trim(to_char(ddd, 'FM999')) || fone::text from cadastro.fone_pessoa where idpes = p.idpes order by tipo desc limit 1) as telefone"),
            'veiculos' => DB::table('transporte_escolar.veiculo')->orderBy('placa'),
            'pontos' => DB::table('transporte_escolar.ponto')->orderBy('descricao'),
            default => abort(404),
        };
    }

    private function processo(string $recurso): int
    {
        return match ($recurso) {
            'empresas' => Processos::EMPRESAS,
            'motoristas' => Processos::MOTORISTAS,
            'monitores' => Processos::MONITORES,
            'veiculos' => Processos::VEICULOS,
            'fornecedores' => Processos::FORNECEDORES,
            'pontos' => Processos::PONTOS,
            default => abort(404),
        };
    }

    /**
     * @return array<string, string>
     */
    private function colunas(string $recurso): array
    {
        return match ($recurso) {
            'veiculos' => ['placa' => 'placa', 'descricao' => 'descricao', 'passageiros' => 'passageiros', 'ativo' => 'ativo'],
            'motoristas' => ['nome' => 'nome', 'telefone' => 'telefone', 'email' => 'email', 'matricula_funcional' => 'matricula', 'cnh' => 'cnh'],
            'monitores' => ['nome' => 'nome', 'telefone' => 'telefone', 'email' => 'email', 'matricula_funcional' => 'matricula', 'turno' => 'turno'],
            'empresas' => ['nome' => 'nome', 'cnpj' => 'cnpj', 'telefone' => 'telefone', 'email' => 'email'],
            'fornecedores' => ['nome' => 'nome', 'telefone' => 'telefone', 'email' => 'email'],
            'pontos' => ['descricao' => 'ponto', 'latitude' => 'latitude', 'longitude' => 'longitude', 'raio_metros' => 'raio'],
            default => abort(404),
        };
    }

    private function titulo(string $recurso): string
    {
        return match ($recurso) {
            'empresas' => 'Empresas',
            'motoristas' => 'Motoristas',
            'monitores' => 'Monitores',
            'veiculos' => 'Veículos',
            'fornecedores' => 'Fornecedores',
            'pontos' => 'Pontos',
            default => 'Frota',
        };
    }
}
