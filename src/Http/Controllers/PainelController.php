<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Services\CustoMensal;
use iEducar\Packages\BuritiTransporteEscolar\Services\GerarAvisos;
use iEducar\Packages\BuritiTransporteEscolar\Services\RedeConsulta;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Coordenada;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PainelController
{
    private const CORES = ['#188ad1', '#0f6b4c', '#b86e00', '#5c4d7a', '#9b2c2c', '#1f6f8b', '#c45c26', '#3d6b4f'];
    public function index(): View
    {
        $ano = (int) now()->year;
        $mes = (int) now()->month;
        $ids = RedeConsulta::rotas($ano)->pluck('id');
        $vazio = $ids->isEmpty();
        $podeCusto = Acesso::permite(Processos::GASTOS, 'ver');
        $custo = $podeCusto ? CustoMensal::fechar($ano, $mes) : null;
        $categorias = [];
        $porFonte = ['pnate' => [], 'proprio' => [], 'outros' => []];

        if ($custo !== null) {
            foreach ($custo['linhas'] as $linha) {
                $rotulo = (string) $linha['rotulo'];
                $fonte = in_array($linha['fonte'], ['pnate', 'proprio'], true) ? (string) $linha['fonte'] : 'outros';
                $valor = (float) $linha['valor'];

                if (!isset($categorias[$rotulo])) {
                    $categorias[$rotulo] = ['rotulo' => $rotulo, 'valor' => 0.0, 'fontes' => []];
                }

                $categorias[$rotulo]['valor'] += $valor;
                $categorias[$rotulo]['fontes'][$fonte] = ($categorias[$rotulo]['fontes'][$fonte] ?? 0) + $valor;
                $porFonte[$fonte][$rotulo] = ($porFonte[$fonte][$rotulo] ?? 0) + $valor;
            }

            uasort($categorias, static fn (array $a, array $b): int => $b['valor'] <=> $a['valor']);
            $categorias = array_slice(array_values($categorias), 0, 8);
        }

        $mapa = $this->mapa($ids->all());
        $alunos = $vazio ? 0 : (int) DB::table('transporte_escolar.aluno_rota')->whereIn('rota_id', $ids)->distinct()->count('ref_cod_aluno');
        $viagens = $vazio ? 0 : (int) DB::table('transporte_escolar.viagem')->whereIn('rota_id', $ids)->whereYear('data', $ano)->whereMonth('data', $mes)->count();
        $embarques = $vazio ? 0 : (int) DB::table('transporte_escolar.embarque as e')
            ->join('transporte_escolar.viagem as v', 'v.id', '=', 'e.viagem_id')
            ->whereIn('v.rota_id', $ids)
            ->where('e.tipo', 'embarque')
            ->whereYear('v.data', $ano)
            ->whereMonth('v.data', $mes)
            ->count();
        $veiculos = $vazio ? collect() : DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->whereIn('i.rota_id', $ids)
            ->distinct()
            ->get(['v.id', 'v.placa', 'v.descricao', 'v.passageiros']);
        $escolasAtendidas = $vazio ? collect() : DB::table('transporte_escolar.rota_escola as re')
            ->join('pmieducar.escola as e', 'e.cod_escola', '=', 're.ref_cod_escola')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
            ->whereIn('re.rota_id', $ids)
            ->groupBy('e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude')
            ->orderBy('p.nome')
            ->get(['e.cod_escola', 'p.nome', 'e.latitude', 'e.longitude']);
        $viagensRota = $vazio ? collect() : DB::table('transporte_escolar.viagem as v')
            ->join('transporte_escolar.rota as r', 'r.id', '=', 'v.rota_id')
            ->whereIn('v.rota_id', $ids)
            ->whereYear('v.data', $ano)
            ->whereMonth('v.data', $mes)
            ->groupBy('r.descricao')
            ->orderByDesc(DB::raw('count(*)'))
            ->selectRaw('r.descricao, count(*) as viagens')
            ->get();
        $comTracado = count(array_filter($mapa['rotas'], static fn (array $rota): bool => $rota['pontos'] !== []));
        $media = $ids->count() > 0 ? round($alunos / $ids->count(), 1) : 0;
        $avisos = $this->fila();
        $porTom = array_count_values(array_column($avisos, 'tom'));
        $pedidos = Acesso::permite(Processos::SOLICITACOES, 'ver') ? $this->pedidos() : null;

        return view('transporte-escolar::painel', [
            'ano' => $ano,
            'mes' => $mes,
            'cartoes' => [
                ['tom' => 'azul', 'icone' => 'fa-road', 'valor' => $ids->count(), 'rotulo' => 'Rotas em ' . $ano, 'itens' => array_map(static fn (array $rota): array => ['rotulo' => $rota['nome'], 'valor' => $rota['alunos'] . ' alunos'], $mapa['rotas'])],
                ['tom' => 'destaque', 'icone' => 'fa-users', 'valor' => $alunos, 'rotulo' => 'Alunos na rota', 'itens' => [
                    ['rotulo' => 'Média por rota', 'valor' => str_replace('.', ',', (string) $media)],
                    ['rotulo' => 'Com traçado', 'valor' => $comTracado],
                    ['rotulo' => 'Sem traçado', 'valor' => max(0, $ids->count() - $comTracado)],
                ]],
                ['tom' => 'verde', 'icone' => 'fa-clock-o', 'valor' => $viagens, 'rotulo' => 'Viagens no mês', 'itens' => array_merge(
                    [['rotulo' => 'Embarques', 'valor' => $embarques]],
                    $viagensRota->map(static fn ($rota): array => ['rotulo' => (string) $rota->descricao, 'valor' => (int) $rota->viagens])->all(),
                )],
                ['tom' => 'ambar', 'icone' => 'fa-bus', 'valor' => $veiculos->count(), 'rotulo' => 'Veículos em linha', 'itens' => $veiculos->map(static fn ($veiculo): array => ['rotulo' => (string) ($veiculo->placa ?: $veiculo->descricao), 'valor' => (int) $veiculo->passageiros . ' lugares'])->all()],
                ['tom' => 'roxo', 'icone' => 'fa-university', 'valor' => $escolasAtendidas->count(), 'rotulo' => 'Escolas atendidas', 'itens' => $escolasAtendidas->map(static function ($escola): array {
                    $noMapa = Coordenada::noTerritorio($escola->latitude === null ? null : (string) $escola->latitude, $escola->longitude === null ? null : (string) $escola->longitude) === null
                        && $escola->latitude !== null
                        && $escola->longitude !== null;

                    return ['rotulo' => (string) $escola->nome, 'valor' => $noMapa ? 'No mapa' : 'Sem ponto'];
                })->all()],
                ['tom' => 'alerta', 'icone' => 'fa-inbox', 'valor' => $pedidos ?? $mapa['lotadas'], 'rotulo' => $pedidos !== null ? 'Pedidos em análise' : 'Rotas acima da lotação', 'itens' => array_values(array_filter([
                    $pedidos !== null ? ['rotulo' => 'Pedidos em análise', 'valor' => $pedidos] : null,
                    ['rotulo' => 'Rotas lotadas', 'valor' => $mapa['lotadas']],
                    ['rotulo' => 'Prazos', 'valor' => (int) ($porTom['prazo'] ?? 0)],
                    ['rotulo' => 'Lotação', 'valor' => (int) ($porTom['lotacao'] ?? 0)],
                    ['rotulo' => 'Serviços', 'valor' => (int) ($porTom['servico'] ?? 0)],
                    ['rotulo' => 'Pedidos na fila', 'valor' => (int) ($porTom['pedido'] ?? 0)],
                ]))],
            ],
            'podeCusto' => $podeCusto,
            'custo' => $custo,
            'categorias' => $categorias,
            'porFonte' => $porFonte,
            'teto' => $categorias === [] ? 1 : max(array_column($categorias, 'valor')),
            'mapa' => $mapa,
            'avisos' => $avisos,
        ]);
    }

    private function pedidos(): int
    {
        $escolas = \iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede::escolas();
        $consulta = DB::table('transporte_escolar.solicitacao')->whereIn('situacao', ['recebida', 'em_analise']);

        if ($escolas === []) {
            $consulta->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $consulta->whereIn('ref_cod_escola', $escolas);
        }

        return (int) $consulta->count();
    }

    /**
     * @param list<int> $rotas
     * @return array{escolas: list<array{lat: float, lng: float, nome: string}>, rotas: list<array{id: int, nome: string, cor: string, alunos: int, km: float, tempo: ?int, veiculos: list<string>, motoristas: list<string>, pontos: list<array{lat: float, lng: float, nome: string, tipo: string}>}>, lotadas: int}
     */
    private function mapa(array $rotas): array
    {
        $escolas = [];

        foreach (RedeConsulta::escolas()->get() as $escola) {
            if (Coordenada::noTerritorio($escola->latitude === null ? null : (string) $escola->latitude, $escola->longitude === null ? null : (string) $escola->longitude) !== null) {
                continue;
            }

            $escolas[] = [
                'lat' => (float) $escola->latitude,
                'lng' => (float) $escola->longitude,
                'nome' => (string) $escola->nome,
            ];
        }

        if ($rotas === []) {
            return ['escolas' => $escolas, 'rotas' => [], 'lotadas' => 0];
        }

        $cadastro = DB::table('transporte_escolar.rota')->whereIn('id', $rotas)->orderBy('descricao')->get(['id', 'descricao', 'km_pav', 'km_npav', 'km_calculado', 'tempo_estimado_min']);
        $equipe = [];

        foreach (DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->leftJoin('transporte_escolar.motorista as m', 'm.id', '=', 'v.motorista_id')
            ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->whereIn('i.rota_id', $rotas)
            ->whereNotNull('i.veiculo_id')
            ->get(['i.rota_id', 'v.placa', 'v.descricao as veiculo', 'p.nome as motorista']) as $item) {
            $nomeVeiculo = trim((string) ($item->placa ?: $item->veiculo));
            $equipe[$item->rota_id]['veiculos'][$nomeVeiculo] = $nomeVeiculo;

            if ($item->motorista) {
                $equipe[$item->rota_id]['motoristas'][(string) $item->motorista] = (string) $item->motorista;
            }
        }
        $alunos = DB::table('transporte_escolar.aluno_rota')->whereIn('rota_id', $rotas)->selectRaw('rota_id, count(*) as alunos')->groupBy('rota_id')->pluck('alunos', 'rota_id');
        $lugares = DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->whereIn('i.rota_id', $rotas)
            ->selectRaw('i.rota_id, min(v.passageiros) as lugares')
            ->groupBy('i.rota_id')
            ->pluck('lugares', 'rota_id');
        $paradas = DB::table('transporte_escolar.itinerario as i')
            ->join('transporte_escolar.ponto as p', 'p.id', '=', 'i.ponto_id')
            ->whereIn('i.rota_id', $rotas)
            ->whereNotNull('p.latitude')
            ->whereNotNull('p.longitude')
            ->orderBy('i.rota_id')
            ->orderBy('i.seq')
            ->limit(800)
            ->get(['i.rota_id', 'i.tipo', 'p.latitude', 'p.longitude', 'p.descricao']);
        $porRota = [];

        foreach ($paradas as $parada) {
            if (Coordenada::noTerritorio((string) $parada->latitude, (string) $parada->longitude) !== null) {
                continue;
            }

            $porRota[$parada->rota_id][] = [
                'lat' => (float) $parada->latitude,
                'lng' => (float) $parada->longitude,
                'nome' => (string) $parada->descricao,
                'tipo' => (string) $parada->tipo,
            ];
        }

        $linhas = [];
        $lotadas = 0;

        foreach ($cadastro as $indice => $rota) {
            $quantidade = (int) ($alunos[$rota->id] ?? 0);
            $capacidade = isset($lugares[$rota->id]) ? (int) $lugares[$rota->id] : null;

            if ($capacidade !== null && $quantidade > $capacidade) {
                $lotadas++;
            }

            $km = (float) $rota->km_pav + (float) $rota->km_npav;
            $linhas[] = [
                'id' => (int) $rota->id,
                'nome' => (string) $rota->descricao,
                'cor' => self::CORES[$indice % count(self::CORES)],
                'alunos' => $quantidade,
                'km' => round($km > 0 ? $km : (float) $rota->km_calculado, 1),
                'tempo' => $rota->tempo_estimado_min === null ? null : (int) $rota->tempo_estimado_min,
                'veiculos' => array_values($equipe[$rota->id]['veiculos'] ?? []),
                'motoristas' => array_values($equipe[$rota->id]['motoristas'] ?? []),
                'pontos' => $porRota[$rota->id] ?? [],
            ];
        }

        return ['escolas' => $escolas, 'rotas' => $linhas, 'lotadas' => $lotadas];
    }

    /**
     * @return list<object>
     */
    private function fila(): array
    {
        if (!Schema::hasTable('transporte_escolar.notificacao')) {
            return [];
        }

        GerarAvisos::sincronizar();
        $usuario = (int) Auth::id();
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

        if ($menus === []) {
            return [];
        }

        return DB::table('transporte_escolar.notificacao as n')
            ->leftJoin('transporte_escolar.notificacao_leitura as l', function ($join) use ($usuario) {
                $join->on('l.notificacao_id', '=', 'n.id')->where('l.ref_usuario', $usuario);
            })
            ->where('n.chave', 'like', GerarAvisos::escopo() . ':%')
            ->whereIn('n.menu', $menus)
            ->where('n.ativa', true)
            ->orderByDesc('n.updated_at')
            ->limit(12)
            ->get(['n.id', 'n.titulo', 'n.texto', 'n.url', 'n.tom', 'n.icone', 'l.lida_em'])
            ->all();
    }
}
