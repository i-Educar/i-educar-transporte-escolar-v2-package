<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificacaoModeloController
{
    public function index(): View
    {
        return view('transporte-escolar::avisos.modelos', [
            'modelos' => DB::table('transporte_escolar.notificacao_modelo')->orderBy('codigo')->get(),
            'cadastro' => url('/transporte-escolar/avisos/cadastro'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'titulo' => ['required', 'string', 'max:120'],
            'corpo' => ['required', 'string', 'max:500'],
        ]);

        if (DB::table('transporte_escolar.notificacao_modelo')->where('codigo', $dados['codigo'])->exists()) {
            return back()->with('erro', 'Já existe um modelo com este código.');
        }

        DB::table('transporte_escolar.notificacao_modelo')->insert([
            'codigo' => $dados['codigo'],
            'titulo' => $dados['titulo'],
            'corpo' => $dados['corpo'],
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Modelo de notificação criado.');
    }

    public function atualizar(Request $request, int $modelo): RedirectResponse
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'corpo' => ['required', 'string', 'max:500'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $existe = DB::table('transporte_escolar.notificacao_modelo')->where('id', $modelo)->exists();

        if (!$existe) {
            return back()->with('erro', 'Modelo não encontrado.');
        }

        DB::table('transporte_escolar.notificacao_modelo')->where('id', $modelo)->update([
            'titulo' => $dados['titulo'],
            'corpo' => $dados['corpo'],
            'ativo' => $request->boolean('ativo'),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Modelo atualizado.');
    }
}
