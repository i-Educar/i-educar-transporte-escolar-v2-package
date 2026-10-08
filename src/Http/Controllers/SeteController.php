<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\ExportadorSete;
use iEducar\Packages\BuritiTransporteEscolar\Services\ImportadorSete;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeteController
{
    public function index(): View
    {
        $linhas = DB::table('transporte_escolar.importacao_linha')->orderByDesc('id');
        $escolas = VisaoRede::escolas();

        if (is_array($escolas)) {
            $nomes = DB::table('pmieducar.escola as e')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
                ->whereIn('e.cod_escola', $escolas === [] ? [0] : $escolas)
                ->pluck('p.nome');

            $linhas->where(function ($consulta) use ($nomes) {
                if ($nomes->isEmpty()) {
                    $consulta->whereRaw('1 = 0');

                    return;
                }

                foreach ($nomes as $nome) {
                    $consulta->orWhere('payload', 'ilike', '%' . $nome . '%');
                }
            });
        }

        return view('transporte-escolar::sete.index', [
            'linhas' => $linhas->limit(80)->get(),
            'podeImportar' => Acesso::permite(Processos::SETE, 'gravar'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
            'ano' => ['required', 'integer'],
        ]);

        try {
            $resultado = ImportadorSete::arquivo(
                $dados['arquivo']->getRealPath(),
                $dados['arquivo']->getClientOriginalName(),
                (int) $dados['ano'],
            );
        } catch (RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return back()->with('ok', $resultado['ok'] . ' linhas gravadas, ' . $resultado['erros'] . ' divergências.');
    }

    public function exportar(string $entidade): StreamedResponse
    {
        abort_unless(in_array($entidade, ['alunos', 'veiculos', 'motoristas', 'monitores'], true), 404);

        return ExportadorSete::csv($entidade);
    }
}
