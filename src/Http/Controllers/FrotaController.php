<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FrotaController
{
    public function index(Request $request): View
    {
        $recurso = (string) $request->route('recurso', $request->input('recurso'));
        $processo = $this->processo($recurso);

        return view('transporte-escolar::frota.index', [
            'recurso' => $recurso,
            'titulo' => $this->titulo($recurso),
            'linhas' => $this->linhas($recurso),
            'podeGravar' => Acesso::permite($processo, 'gravar'),
            'codigos' => Codigos::class,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $recurso = (string) $request->input('recurso');

        match ($recurso) {
            'empresas' => $this->empresa($request),
            'fornecedores' => $this->fornecedor($request),
            'motoristas' => $this->motorista($request),
            'monitores' => $this->monitor($request),
            'veiculos' => $this->veiculo($request),
            'pontos' => DB::table('transporte_escolar.ponto')->insert([
                'descricao' => $request->validate(['descricao' => ['required', 'string', 'max:70']])['descricao'],
                'origem' => 'ieducar',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            default => abort(404),
        };

        return back()->with('ok', 'Registro gravado a partir do cadastro de pessoas do i-Educar.');
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
                'origem' => 'ieducar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
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
                'origem' => 'ieducar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
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
            'empresas' => DB::table('transporte_escolar.empresa as e')->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')->leftJoin('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')->orderBy('p.nome')->get(['p.nome', 'j.cnpj', 'e.observacao']),
            'fornecedores' => DB::table('transporte_escolar.fornecedor as f')->join('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_idpes')->orderBy('p.nome')->get(['p.nome', 'f.observacao']),
            'motoristas' => DB::table('transporte_escolar.motorista as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['p.nome', 'm.cnh', 'm.tipo_cnh', 'm.vencimento_cnh', 'm.vinculo', 'm.salario_mensal']),
            'monitores' => DB::table('transporte_escolar.monitor as m')->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')->orderBy('p.nome')->get(['p.nome', 'm.turno', 'm.salario_mensal']),
            'veiculos' => DB::table('transporte_escolar.veiculo')->orderBy('placa')->get(),
            'pontos' => DB::table('transporte_escolar.ponto')->orderBy('descricao')->get(),
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
