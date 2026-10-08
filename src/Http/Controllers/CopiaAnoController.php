<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CopiaAno;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CopiaAnoController
{
    public function index(): View
    {
        return view('transporte-escolar::copia');
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ano_origem' => ['required', 'integer'],
            'ano_destino' => ['required', 'integer', 'different:ano_origem'],
        ]);

        $resultado = CopiaAno::executar((int) $dados['ano_origem'], (int) $dados['ano_destino']);
        $texto = $resultado['copiadas'] . ' rotas copiadas, ' . $resultado['alunos'] . ' alunos com matrícula ativa no destino.';

        if ($resultado['ignoradas'] !== []) {
            $texto .= ' Já existiam: ' . implode(', ', $resultado['ignoradas']) . '.';
        }

        if ($resultado['alunos_recusados'] !== []) {
            $texto .= ' ' . count($resultado['alunos_recusados']) . ' alunos ficaram de fora por não terem matrícula ativa.';
        }

        return back()->with('ok', $texto);
    }
}
