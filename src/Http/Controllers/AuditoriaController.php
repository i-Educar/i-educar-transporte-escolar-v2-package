<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\AuditoriaTransporte;
use iEducar\Packages\BuritiTransporteEscolar\Support\Pagina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuditoriaController
{
    public function index(Request $request): View
    {
        $tabela = $this->tabela($request);
        $operacao = $this->operacao($request);
        $consulta = $this->consulta($tabela, $operacao);
        $fatia = Pagina::fatia((int) (clone $consulta)->count(), $request->input('pagina'));
        $linhas = $consulta->offset($fatia['inicio'])->limit($fatia['tamanho'])->get()->map(fn ($linha) => $this->ler($linha));

        return view('transporte-escolar::auditoria.index', [
            'linhas' => $linhas,
            'tabela' => $tabela,
            'operacao' => $operacao,
            'tabelas' => DB::table('public.ieducar_audit')->where('schema', 'transporte_escolar')->distinct()->orderBy('table')->pluck('table'),
            'pagina' => $fatia['pagina'],
            'paginas' => $fatia['paginas'],
            'total' => $fatia['total'],
        ]);
    }

    public function csv(Request $request): Response
    {
        $tabela = $this->tabela($request);
        $operacao = $this->operacao($request);
        $saida = fopen('php://temp', 'w+');
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($saida, ['quando', 'tabela', 'operacao', 'campos', 'alteracao'], ';');

        foreach ($this->consulta($tabela, $operacao)->get() as $linha) {
            $lida = $this->ler($linha);
            fputcsv($saida, [
                $lida->date,
                $lida->table,
                $lida->operacao,
                implode(', ', $lida->campos),
                $lida->resumo,
            ], ';');
        }

        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="auditoria-transporte.csv"',
        ]);
    }

    private function consulta(string $tabela, string $operacao)
    {
        $consulta = DB::table('public.ieducar_audit')
            ->where('schema', 'transporte_escolar')
            ->orderByDesc('id');

        if ($tabela !== '') {
            $consulta->where('table', $tabela);
        }

        if ($operacao === 'Inclusão') {
            $consulta->whereNull('before')->whereNotNull('after');
        } elseif ($operacao === 'Exclusão') {
            $consulta->whereNotNull('before')->whereNull('after');
        } elseif ($operacao === 'Alteração') {
            $consulta->whereNotNull('before')->whereNotNull('after');
        }

        return $consulta;
    }

    private function ler(object $linha): object
    {
        $antes = $linha->before ? json_decode($linha->before, true) : null;
        $depois = $linha->after ? json_decode($linha->after, true) : null;
        $antes = is_array($antes) ? $antes : null;
        $depois = is_array($depois) ? $depois : null;
        $linha->operacao = AuditoriaTransporte::operacao($antes !== null, $depois !== null);
        $linha->campos = AuditoriaTransporte::campos($antes, $depois);
        $linha->resumo = AuditoriaTransporte::resumo($antes, $depois);
        $linha->antes_texto = $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
        $linha->depois_texto = $depois ? json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';

        return $linha;
    }

    private function tabela(Request $request): string
    {
        $tabela = trim((string) $request->input('tabela'));

        if ($tabela !== '' && !preg_match('/^[a-z0-9_]+$/', $tabela)) {
            return '';
        }

        return $tabela;
    }

    private function operacao(Request $request): string
    {
        $operacao = (string) $request->input('operacao');

        return in_array($operacao, ['Inclusão', 'Alteração', 'Exclusão'], true) ? $operacao : '';
    }
}
