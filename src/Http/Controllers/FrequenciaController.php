<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Codigos;
use iEducar\Packages\BuritiTransporteEscolar\Support\CpfAnonimizado;
use iEducar\Packages\BuritiTransporteEscolar\Support\IdentidadeMunicipio;
use iEducar\Packages\BuritiTransporteEscolar\Support\PdfSimples;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FrequenciaController
{
    public function index(Request $request): RedirectResponse
    {
        $ano = (int) $request->input('ano', date('Y'));
        $mes = (int) $request->input('mes', date('n'));

        return redirect('/transporte-escolar/viagens?ano=' . $ano . '&mes=' . ($mes < 1 || $mes > 12 ? (int) date('n') : $mes));
    }

    public function pdf(int $viagem): Response
    {
        $registro = $this->viagemVisivel($viagem);
        $alunos = DB::table('transporte_escolar.aluno_rota as ar')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'ar.ref_cod_aluno')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->where('ar.rota_id', $registro->rota_id)
            ->orderBy('f.cpf')
            ->get(['ar.ref_cod_aluno', 'f.cpf']);
        $presentes = array_map('intval', DB::table('transporte_escolar.embarque')
            ->where('viagem_id', $viagem)
            ->where('tipo', 'embarque')
            ->pluck('ref_cod_aluno')
            ->all());
        $hora = $registro->hora ? substr((string) $registro->hora, 0, 5) : '';
        $sentido = Codigos::SENTIDO[$registro->sentido] ?? $registro->sentido;
        $linhas = [];

        foreach ($alunos as $aluno) {
            $linhas[] = [
                'cpf' => CpfAnonimizado::exibir($aluno->cpf),
                'presente' => in_array((int) $aluno->ref_cod_aluno, $presentes, true),
            ];
        }

        $pdf = PdfSimples::lista([
            'municipio' => IdentidadeMunicipio::rotulo(),
            'titulo' => 'FREQUENCIA DO TRANSPORTE',
            'protocolo' => 'V-' . $viagem,
            'subtitulo' => trim($registro->descricao . '  ' . \Illuminate\Support\Carbon::parse($registro->data)->format('d/m/Y') . ($hora !== '' ? '  ' . $hora : '') . '  ' . $sentido),
            'linhas' => $linhas,
            'logo' => IdentidadeMunicipio::arquivo(),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="frequencia-viagem-' . $viagem . '.pdf"',
        ]);
    }

    private function viagemVisivel(int $viagem): object
    {
        $ids = RedeConsulta::rotas()->pluck('r.id');
        $registro = DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->where('v.id', $viagem)
            ->when($ids->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('v.rota_id', $ids))
            ->first(['v.*', 'r.descricao']);
        abort_if($registro === null, 404);

        return $registro;
    }
}
