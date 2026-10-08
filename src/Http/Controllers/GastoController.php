<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal;
use iEducar\Packages\BuritiTransporteEscolar\Support\GastoRegra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GastoController
{
    public function index(Request $request): View
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = (int) $request->input('mes', date('n'));
        $aquaviario = DB::table('transporte_escolar.configuracao')->where('chave', 'aquaviario')->value('valor') === '1';
        $geocerca = DB::table('transporte_escolar.configuracao')->where('chave', 'geocerca')->value('valor') === '1';
        $rateio = DB::table('transporte_escolar.configuracao')->where('chave', 'rateio')->value('valor') ?: 'km';
        $vencidos = DB::table('transporte_escolar.veiculo')
            ->where('ativo', true)
            ->whereNotNull('licenciamento_vencimento')
            ->where('licenciamento_vencimento', '<', now()->toDateString())
            ->get(['placa', 'descricao', 'licenciamento_vencimento']);
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        return view('transporte-escolar::gastos.index', [
            'ano' => $ano,
            'mes' => $mes,
            'aquaviario' => $aquaviario,
            'geocerca' => $geocerca,
            'raio' => (int) (DB::table('transporte_escolar.configuracao')->where('chave', 'raio_metros')->value('valor') ?: 150),
            'rateio' => $rateio,
            'vencidos' => $vencidos,
            'custo' => CustoMensal::fechar($ano, $mes),
            'alerta' => CustoMensal::alertaSaldo($ano),
            'fontes' => GastoRegra::FONTES,
            'categorias' => GastoRegra::CATEGORIAS,
            'veiculos' => DB::table('transporte_escolar.veiculo')->orderBy('placa')->get(),
            'fornecedores' => DB::table('transporte_escolar.fornecedor as f')->join('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_idpes')->orderBy('p.nome')->get(['f.id', 'p.nome']),
            'empresas' => DB::table('transporte_escolar.empresa as e')->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')->orderBy('p.nome')->get(['e.id', 'p.nome']),
            'planos' => DB::table('transporte_escolar.manutencao_plano as m')->join('transporte_escolar.veiculo as v', 'v.id', '=', 'm.veiculo_id')->orderBy('v.placa')->get(['m.*', 'v.placa', 'v.descricao']),
            'gastos' => DB::table('transporte_escolar.gasto as g')
                ->leftJoin('transporte_escolar.veiculo as v', 'v.id', '=', 'g.veiculo_id')
                ->leftJoin('transporte_escolar.comprovante as c', 'c.gasto_id', '=', 'g.id')
                ->whereBetween('g.data', [$inicio, $fim])
                ->orderByDesc('g.data')
                ->get(['g.id', 'g.data', 'g.categoria', 'g.fonte', 'g.valor', 'g.descricao', 'v.placa', 'c.id as comprovante_id']),
            'contratos' => DB::table('transporte_escolar.contrato as c')
                ->join('transporte_escolar.empresa as e', 'e.id', '=', 'c.empresa_id')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
                ->orderByDesc('c.inicio')
                ->get(['c.*', 'p.nome as empresa']),
            'parcelas' => DB::table('transporte_escolar.parcela_recurso')->where('ano', $ano)->orderBy('mes')->get(),
        ]);
    }

    public function gasto(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'data' => ['required', 'date'],
            'categoria' => ['required', 'string'],
            'fonte' => ['required', 'string'],
            'valor' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string'],
            'veiculo_id' => ['nullable', 'integer'],
            'comprovante' => ['nullable', 'file', 'max:5120'],
        ]);
        $motivo = GastoRegra::motivo($dados['categoria'], $dados['fonte'], $this->temAquaviario());

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        $id = DB::table('transporte_escolar.gasto')->insertGetId([
            'data' => $dados['data'],
            'categoria' => $dados['categoria'],
            'fonte' => $dados['fonte'],
            'valor' => $dados['valor'],
            'descricao' => $dados['descricao'] ?? null,
            'veiculo_id' => $dados['veiculo_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->hasFile('comprovante')) {
            $arquivo = $request->file('comprovante');
            DB::table('transporte_escolar.comprovante')->insert([
                'gasto_id' => $id,
                'nome' => $arquivo->getClientOriginalName(),
                'caminho' => $arquivo->store('transporte-escolar/comprovantes'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('ok', 'Gasto lançado.');
    }

    public function abastecimento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'data' => ['required', 'date'],
            'litros' => ['required', 'numeric', 'min:0'],
            'valor' => ['required', 'numeric', 'min:0'],
            'km' => ['nullable', 'numeric'],
            'fonte' => ['required', 'string'],
        ]);

        if (!isset(GastoRegra::FONTES[$dados['fonte']])) {
            return back()->with('erro', 'Fonte de recurso desconhecida.');
        }

        DB::table('transporte_escolar.abastecimento')->insert([
            'veiculo_id' => $dados['veiculo_id'],
            'data' => $dados['data'],
            'litros' => $dados['litros'],
            'valor' => $dados['valor'],
            'km' => $dados['km'] ?? null,
            'fonte' => $dados['fonte'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Abastecimento lançado.');
    }

    public function plano(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'item' => ['required', 'in:oleo,filtro,pneu,freio,revisao'],
            'a_cada_km' => ['nullable', 'integer'],
            'a_cada_dias' => ['nullable', 'integer'],
        ]);

        DB::table('transporte_escolar.manutencao_plano')->insert([
            'veiculo_id' => $dados['veiculo_id'],
            'item' => $dados['item'],
            'a_cada_km' => $dados['a_cada_km'] ?? null,
            'a_cada_dias' => $dados['a_cada_dias'] ?? null,
            'ultima_data' => now()->toDateString(),
        ]);

        return back()->with('ok', 'Plano de manutenção gravado.');
    }

    public function ordem(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'descricao' => ['required', 'string'],
            'valor' => ['required', 'numeric', 'min:0'],
            'data' => ['required', 'date'],
            'fonte' => ['required', 'string'],
            'preventiva' => ['nullable', 'boolean'],
            'peca' => ['nullable', 'string'],
            'mao_obra' => ['nullable', 'numeric'],
            'km' => ['nullable', 'numeric'],
            'nota' => ['nullable', 'string'],
            'fornecedor_id' => ['nullable', 'integer'],
        ]);
        $motivo = GastoRegra::motivo('manutencao_rodoviaria', $dados['fonte'], true);

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        DB::table('transporte_escolar.ordem_servico')->insert([
            'veiculo_id' => $dados['veiculo_id'],
            'fornecedor_id' => $dados['fornecedor_id'] ?? null,
            'preventiva' => $request->boolean('preventiva'),
            'descricao' => $dados['descricao'],
            'peca' => $dados['peca'] ?? null,
            'mao_obra' => $dados['mao_obra'] ?? null,
            'km' => $dados['km'] ?? null,
            'nota' => $dados['nota'] ?? null,
            'valor' => $dados['valor'],
            'data' => $dados['data'],
            'fonte' => $dados['fonte'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Ordem de serviço gravada.');
    }

    public function contrato(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'empresa_id' => ['required', 'integer'],
            'descricao' => ['required', 'string'],
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'inicio' => ['required', 'date'],
            'fim' => ['nullable', 'date'],
        ]);

        DB::table('transporte_escolar.contrato')->insert($dados + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Contrato gravado. A medição do mês usa as viagens realizadas.');
    }

    public function parcela(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ano' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'fonte' => ['required', 'string'],
            'valor' => ['required', 'numeric'],
            'saldo_informado' => ['nullable', 'numeric'],
        ]);

        if (!isset(GastoRegra::FONTES[$dados['fonte']])) {
            return back()->with('erro', 'Fonte de recurso desconhecida.');
        }

        DB::table('transporte_escolar.parcela_recurso')->updateOrInsert(
            ['ano' => $dados['ano'], 'mes' => $dados['mes'], 'fonte' => $dados['fonte']],
            ['valor' => $dados['valor'], 'saldo_informado' => $dados['saldo_informado'] ?? null],
        );

        return back()->with('ok', 'Parcela informada. Isso não substitui o extrato do banco.');
    }

    public function aquaviario(Request $request): RedirectResponse
    {
        DB::table('transporte_escolar.configuracao')->updateOrInsert(
            ['chave' => 'aquaviario'],
            ['valor' => $request->boolean('aquaviario') ? '1' : '0'],
        );

        return back()->with('ok', 'Transporte aquaviário atualizado.');
    }

    public function geocerca(Request $request): RedirectResponse
    {
        DB::table('transporte_escolar.configuracao')->updateOrInsert(
            ['chave' => 'geocerca'],
            ['valor' => $request->boolean('geocerca') ? '1' : '0'],
        );
        DB::table('transporte_escolar.configuracao')->updateOrInsert(
            ['chave' => 'raio_metros'],
            ['valor' => (string) max(0, (int) $request->input('raio_metros', 150))],
        );

        return back()->with('ok', 'Cerca do ponto atualizada.');
    }

    public function rateio(Request $request): RedirectResponse
    {
        DB::table('transporte_escolar.configuracao')->updateOrInsert(
            ['chave' => 'rateio'],
            ['valor' => $request->input('rateio') === 'alunos' ? 'alunos' : 'km'],
        );

        return back()->with('ok', 'Forma de rateio do salário atualizada.');
    }

    public function comprovante(int $comprovante)
    {
        $arquivo = DB::table('transporte_escolar.comprovante')->where('id', $comprovante)->first();
        abort_if($arquivo === null || !Storage::exists($arquivo->caminho), 404);

        return Storage::download($arquivo->caminho, $arquivo->nome);
    }

    private function temAquaviario(): bool
    {
        return DB::table('transporte_escolar.configuracao')->where('chave', 'aquaviario')->value('valor') === '1';
    }
}
