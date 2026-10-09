<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\GerarAvisos;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificacaoController
{
    public function index(Request $request): View
    {
        GerarAvisos::sincronizar();
        $tom = (string) $request->input('tom', '');
        $situacao = (string) $request->input('situacao', 'abertas');
        $consulta = $this->consulta();

        if (in_array($tom, ['prazo', 'pedido', 'lotacao', 'servico'], true)) {
            $consulta->where('n.tom', $tom);
        }

        if ($situacao === 'lidas') {
            $consulta->whereNotNull('l.lida_em');
        } else {
            $situacao = 'abertas';
            $consulta->where('n.ativa', true);
        }

        return view('transporte-escolar::notificacoes.index', [
            'linhas' => $consulta->orderByDesc('n.updated_at')->limit(80)->get(),
            'tom' => $tom,
            'situacao' => $situacao,
            'rotulos' => $this->rotulos(),
        ]);
    }

    public function show(int $notificacao): View
    {
        $linha = $this->visivel($notificacao);
        $this->marcar((int) Auth::id(), $linha->id);

        return view('transporte-escolar::notificacoes.show', [
            'linha' => $this->visivel($notificacao),
            'rotulos' => $this->rotulos(),
        ]);
    }

    public function lida(int $notificacao): RedirectResponse
    {
        $linha = $this->visivel($notificacao);
        $this->marcar((int) Auth::id(), $linha->id);

        return back()->with('ok', 'Leitura registrada. A fila continua até a pendência acabar.');
    }

    public function pulso(): JsonResponse
    {
        GerarAvisos::sincronizar();
        $linhas = $this->consulta()->whereNull('l.lida_em')->where('n.ativa', true)->orderByDesc('n.updated_at')->limit(5)->get();

        return response()->json([
            'nao_lidas' => $this->consulta()->whereNull('l.lida_em')->where('n.ativa', true)->count(),
            'recentes' => $linhas->map(fn ($linha) => [
                'id' => (int) $linha->id,
                'titulo' => $linha->titulo,
                'texto' => $linha->texto,
                'tom' => $linha->tom,
                'icone' => $linha->icone,
            ])->all(),
        ]);
    }

    private function consulta()
    {
        $usuario = (int) Auth::id();

        return DB::table('transporte_escolar.notificacao as n')
            ->leftJoin('transporte_escolar.notificacao_leitura as l', function ($join) use ($usuario) {
                $join->on('l.notificacao_id', '=', 'n.id')->where('l.ref_usuario', $usuario);
            })
            ->where('n.chave', 'like', GerarAvisos::escopo() . ':%')
            ->whereIn('n.menu', $this->menus())
            ->select(['n.*', 'l.lida_em']);
    }

    private function visivel(int $id): object
    {
        $linha = $this->consulta()->where('n.id', $id)->first();
        abort_if($linha === null, 404);

        return $linha;
    }

    private function marcar(int $usuario, int $id): void
    {
        if ($usuario <= 0) {
            return;
        }

        DB::table('transporte_escolar.notificacao_leitura')->updateOrInsert(
            ['notificacao_id' => $id, 'ref_usuario' => $usuario],
            ['lida_em' => now()],
        );
    }

    /**
     * @return list<string>
     */
    private function menus(): array
    {
        $menus = [];
        $mapa = [
            'frota' => Processos::VEICULOS,
            'alunos' => Processos::ALUNOS,
            'solicitacoes' => Processos::SOLICITACOES,
            'gastos' => Processos::GASTOS,
        ];

        foreach ($mapa as $menu => $processo) {
            if (Acesso::permite($processo, 'ver')) {
                $menus[] = $menu;
            }
        }

        return $menus === [] ? ['nenhum'] : $menus;
    }

    /**
     * @return array<string, string>
     */
    private function rotulos(): array
    {
        return [
            'prazo' => 'Prazo',
            'pedido' => 'Pedido',
            'lotacao' => 'Lotação',
            'servico' => 'Serviço',
        ];
    }
}
