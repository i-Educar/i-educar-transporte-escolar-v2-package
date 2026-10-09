<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\DocumentoTexto;
use iEducar\Packages\BuritiTransporteEscolar\Support\GastoRegra;
use iEducar\Packages\BuritiTransporteEscolar\Support\IdentidadeMunicipio;
use iEducar\Packages\BuritiTransporteEscolar\Support\PdfSimples;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProtocoloDocumento;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoController
{
    public function marca(): BinaryFileResponse|Response
    {
        if (!\App\Support\Tenancy\PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE)) {
            abort(404);
        }

        $arquivo = IdentidadeMunicipio::arquivo();

        if ($arquivo === null) {
            abort(404);
        }

        return response()->file($arquivo);
    }

    public function ordens(): View
    {
        return view('transporte-escolar::documentos.ordens', [
            'ordens' => $this->listaOrdens(),
            'veiculos' => $this->veiculos(),
            'fornecedores' => $this->fornecedores(),
            'fontes' => GastoRegra::FONTES,
            'podeGravar' => Acesso::permite(Processos::GASTOS, 'gravar'),
            'proximas' => SituacaoDocumento::ORDEM,
        ]);
    }

    public function gravarOrdem(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'fornecedor_id' => ['nullable', 'integer'],
            'descricao' => ['required', 'string', 'max:500'],
            'peca' => ['nullable', 'string', 'max:120'],
            'valor' => ['required', 'numeric', 'min:0'],
            'mao_obra' => ['nullable', 'numeric', 'min:0'],
            'data' => ['required', 'date'],
            'fonte' => ['required', 'string'],
            'solicitante' => ['nullable', 'string', 'max:120'],
            'preventiva' => ['nullable', 'boolean'],
        ]);

        if (!isset(GastoRegra::FONTES[$dados['fonte']])) {
            return back()->with('erro', 'Fonte de recurso desconhecida.');
        }

        $aquaviario = DB::table('transporte_escolar.configuracao')->where('chave', 'aquaviario')->value('valor') === '1';
        $motivo = GastoRegra::motivo('manutencao_rodoviaria', $dados['fonte'], $aquaviario);

        if ($motivo !== null) {
            return back()->with('erro', $motivo);
        }

        DB::transaction(function () use ($dados, $request): void {
            $ano = (int) date('Y');
            $sequencia = (int) DB::table('transporte_escolar.ordem_servico')->where('protocolo', 'like', "OS-{$ano}-%")->count() + 1;

            DB::table('transporte_escolar.ordem_servico')->insert([
                'veiculo_id' => $dados['veiculo_id'],
                'fornecedor_id' => $dados['fornecedor_id'] ?: null,
                'preventiva' => $request->boolean('preventiva'),
                'descricao' => $dados['descricao'],
                'peca' => $dados['peca'] ?? null,
                'mao_obra' => $dados['mao_obra'] ?? null,
                'valor' => $dados['valor'],
                'data' => $dados['data'],
                'fonte' => $dados['fonte'],
                'protocolo' => ProtocoloDocumento::montar('OS', $ano, $sequencia),
                'situacao' => 'solicitada',
                'solicitante' => $dados['solicitante'] ?: $this->solicitante(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('ok', 'Ordem solicitada. O PDF descreve o serviço a realizar.');
    }

    public function situacaoOrdem(Request $request, int $ordem): RedirectResponse
    {
        $dados = $request->validate(['situacao' => ['required', 'string']]);
        $atual = DB::table('transporte_escolar.ordem_servico')->where('id', $ordem)->first();

        if ($atual === null || !SituacaoDocumento::pode(SituacaoDocumento::ORDEM, (string) $atual->situacao, $dados['situacao'])) {
            return back()->with('erro', 'Esta ordem não pode mudar para essa situação.');
        }

        DB::table('transporte_escolar.ordem_servico')->where('id', $ordem)->update([
            'situacao' => $dados['situacao'],
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Situação da ordem atualizada.');
    }

    public function pdfOrdem(int $ordem): Response
    {
        $linha = $this->listaOrdens()->firstWhere('id', $ordem);

        if ($linha === null) {
            abort(404);
        }

        $pdf = PdfSimples::impresso([
            'municipio' => IdentidadeMunicipio::rotulo(),
            'titulo' => 'ORDEM DE SERVICO - TRANSPORTE ESCOLAR',
            'protocolo' => (string) $linha->protocolo,
            'campos' => [
                ['Situacao', SituacaoDocumento::frase((string) $linha->situacao)],
                ['Veiculo', trim(($linha->placa ?: $linha->veiculo) . ' ' . ($linha->veiculo_descricao ?? ''))],
                ['Data prevista', date('d/m/Y', strtotime((string) $linha->data))],
                ['Servico a realizar', (string) $linha->descricao],
                ['Peca', (string) ($linha->peca ?: '-')],
                ['Mao de obra prevista', $linha->mao_obra === null ? '-' : 'R$ ' . number_format((float) $linha->mao_obra, 2, ',', '.')],
                ['Valor previsto', 'R$ ' . number_format((float) $linha->valor, 2, ',', '.')],
                ['Fonte', GastoRegra::FONTES[$linha->fonte] ?? (string) $linha->fonte],
                ['Fornecedor', (string) ($linha->fornecedor ?: '-')],
                ['Solicitante', (string) ($linha->solicitante ?: '-')],
                ['Emitido em', date('d/m/Y H:i')],
            ],
            'assinaturas' => ['Solicitante', 'Autorizacao da rede', 'Execucao do servico'],
            'observacao' => 'Este documento descreve o servico a realizar e serve de controle da execucao. Nao substitui nota fiscal nem o extrato da conta do PNATE.',
            'logo' => IdentidadeMunicipio::arquivo(),
        ], 2, true);

        return $this->pdf($pdf, (string) $linha->protocolo);
    }

    public function csvOrdens(): Response
    {
        $linhas = ["protocolo;situacao;data;veiculo;servico;valor;solicitante"];

        foreach ($this->listaOrdens() as $ordem) {
            $linhas[] = implode(';', [
                $ordem->protocolo,
                SituacaoDocumento::frase((string) $ordem->situacao),
                $ordem->data,
                $ordem->placa ?: $ordem->veiculo,
                str_replace([';', "\n"], [',', ' '], (string) $ordem->descricao),
                number_format((float) $ordem->valor, 2, ',', '.'),
                $ordem->solicitante,
            ]);
        }

        return response(implode("\n", $linhas), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ordens-de-servico.csv"',
        ]);
    }

    public function bilhetes(): View
    {
        return view('transporte-escolar::documentos.bilhetes', [
            'bilhetes' => $this->listaBilhetes(),
            'veiculos' => $this->veiculos(),
            'fontes' => GastoRegra::FONTES,
            'podeGravar' => Acesso::permite(Processos::GASTOS, 'gravar'),
        ]);
    }

    public function gravarBilhete(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'veiculo_id' => ['required', 'integer'],
            'data' => ['required', 'date'],
            'litros_autorizados' => ['required', 'numeric', 'min:0.01'],
            'fonte' => ['required', 'string'],
            'posto' => ['nullable', 'string', 'max:120'],
            'motorista' => ['nullable', 'string', 'max:120'],
            'solicitante' => ['nullable', 'string', 'max:120'],
        ]);

        if (!isset(GastoRegra::FONTES[$dados['fonte']])) {
            return back()->with('erro', 'Fonte de recurso desconhecida.');
        }

        DB::transaction(function () use ($dados): void {
            $ano = (int) date('Y');
            $sequencia = (int) DB::table('transporte_escolar.abastecimento')->where('protocolo', 'like', "AB-{$ano}-%")->count() + 1;

            DB::table('transporte_escolar.abastecimento')->insert([
                'veiculo_id' => $dados['veiculo_id'],
                'data' => $dados['data'],
                'litros' => 0,
                'valor' => 0,
                'km' => null,
                'fonte' => $dados['fonte'],
                'protocolo' => ProtocoloDocumento::montar('AB', $ano, $sequencia),
                'situacao' => 'solicitado',
                'litros_autorizados' => $dados['litros_autorizados'],
                'posto' => $dados['posto'] ?? null,
                'motorista' => $dados['motorista'] ?? null,
                'solicitante' => $dados['solicitante'] ?: $this->solicitante(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('ok', 'Bilhete solicitado. O PDF traz o protocolo e o canhoto.');
    }

    public function situacaoBilhete(Request $request, int $bilhete): RedirectResponse
    {
        $dados = $request->validate([
            'situacao' => ['required', 'string'],
            'litros' => ['nullable', 'numeric', 'min:0'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'km' => ['nullable', 'numeric', 'min:0'],
        ]);
        $atual = DB::table('transporte_escolar.abastecimento')->where('id', $bilhete)->first();

        if ($atual === null || !SituacaoDocumento::pode(SituacaoDocumento::BILHETE, (string) $atual->situacao, $dados['situacao'])) {
            return back()->with('erro', 'Este bilhete não pode mudar para essa situação.');
        }

        $update = ['situacao' => $dados['situacao'], 'updated_at' => now()];

        if ($dados['situacao'] === 'abastecido') {
            if (($dados['litros'] ?? null) === null || ($dados['valor'] ?? null) === null) {
                return back()->with('erro', 'Ao confirmar o abastecimento, informe os litros e o valor do canhoto.');
            }

            $update['litros'] = $dados['litros'];
            $update['valor'] = $dados['valor'];
            $update['km'] = $dados['km'] ?? null;
        }

        DB::table('transporte_escolar.abastecimento')->where('id', $bilhete)->update($update);

        return back()->with('ok', 'Situação do bilhete atualizada.');
    }

    public function pdfBilhete(int $bilhete): Response
    {
        $linha = $this->listaBilhetes()->firstWhere('id', $bilhete);

        if ($linha === null) {
            abort(404);
        }

        $confirmado = ($linha->situacao ?? '') === 'abastecido';
        $campos = [
            ['Situacao', SituacaoDocumento::frase((string) $linha->situacao)],
            ['Veiculo', trim(($linha->placa ?: '') . ' ' . ($linha->veiculo ?? ''))],
            ['Data', date('d/m/Y', strtotime((string) $linha->data))],
            ['Litros autorizados', number_format((float) ($linha->litros_autorizados ?? $linha->litros), 2, ',', '.') . ' L'],
            ['Motorista', (string) ($linha->motorista ?: '-')],
            ['Posto', (string) ($linha->posto ?: '-')],
            ['Fonte', GastoRegra::FONTES[$linha->fonte] ?? (string) $linha->fonte],
            ['Solicitante', (string) ($linha->solicitante ?: '-')],
        ];

        if ($confirmado) {
            $campos[] = ['Litros abastecidos', number_format((float) $linha->litros, 2, ',', '.') . ' L'];
            $campos[] = ['Valor pago', 'R$ ' . number_format((float) $linha->valor, 2, ',', '.')];
            $campos[] = ['Quilometragem', $linha->km === null ? '-' : number_format((float) $linha->km, 1, ',', '.')];
        }

        $pdf = PdfSimples::impresso([
            'municipio' => IdentidadeMunicipio::rotulo(),
            'titulo' => 'BILHETE DE ABASTECIMENTO - TRANSPORTE ESCOLAR',
            'protocolo' => (string) $linha->protocolo,
            'campos' => $campos,
            'assinaturas' => ['Posto ou frentista', 'Motorista'],
            'observacao' => 'Conferir o protocolo antes de abastecer. A via do posto fica no estabelecimento. O canhoto volta para a secretaria.',
            'logo' => IdentidadeMunicipio::arquivo(),
        ], 2, true);

        return $this->pdf($pdf, (string) $linha->protocolo);
    }

    public function csvBilhetes(): Response
    {
        $linhas = ["protocolo;situacao;data;veiculo;litros_autorizados;litros;valor;posto;solicitante"];

        foreach ($this->listaBilhetes() as $bilhete) {
            $linhas[] = implode(';', [
                $bilhete->protocolo,
                SituacaoDocumento::frase((string) $bilhete->situacao),
                $bilhete->data,
                $bilhete->placa,
                number_format((float) $bilhete->litros_autorizados, 2, ',', '.'),
                number_format((float) $bilhete->litros, 2, ',', '.'),
                number_format((float) $bilhete->valor, 2, ',', '.'),
                str_replace(';', ',', (string) $bilhete->posto),
                $bilhete->solicitante,
            ]);
        }

        return response(implode("\n", $linhas), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bilhetes-abastecimento.csv"',
        ]);
    }

    private function pdf(string $conteudo, string $nome): Response
    {
        $arquivo = $nome !== '' ? $nome : 'documento';

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $arquivo . '.pdf"',
        ]);
    }

    private function listaOrdens()
    {
        return DB::table('transporte_escolar.ordem_servico as o')
            ->leftJoin('transporte_escolar.veiculo as v', 'v.id', '=', 'o.veiculo_id')
            ->leftJoin('transporte_escolar.fornecedor as f', 'f.id', '=', 'o.fornecedor_id')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_idpes')
            ->orderByDesc('o.id')
            ->get([
                'o.*',
                'v.placa',
                'v.descricao as veiculo',
                'v.descricao as veiculo_descricao',
                'p.nome as fornecedor',
            ]);
    }

    private function listaBilhetes()
    {
        return DB::table('transporte_escolar.abastecimento as a')
            ->leftJoin('transporte_escolar.veiculo as v', 'v.id', '=', 'a.veiculo_id')
            ->orderByDesc('a.id')
            ->get(['a.*', 'v.placa', 'v.descricao as veiculo']);
    }

    private function veiculos()
    {
        return DB::table('transporte_escolar.veiculo')->where('ativo', true)->orderBy('placa')->get();
    }

    private function fornecedores()
    {
        return DB::table('transporte_escolar.fornecedor as f')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'f.ref_idpes')
            ->orderBy('p.nome')
            ->get(['f.id', 'p.nome']);
    }

    private function solicitante(): string
    {
        $user = Auth::user();

        if ($user === null) {
            return 'Servidor';
        }

        foreach (['name', 'nome'] as $campo) {
            if (!empty($user->{$campo})) {
                return (string) $user->{$campo};
            }
        }

        return 'Servidor ' . $user->getKey();
    }
}
