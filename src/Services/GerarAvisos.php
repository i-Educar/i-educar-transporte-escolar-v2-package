<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Monta os informes do dia e grava um por assunto, sem duplicar a cada consulta.
 */
final class GerarAvisos
{
    /**
     * @return list<array{codigo: string, menu: string, tom: string, icone: string, titulo: string, texto: string, url: string, detalhe: string}>
     */
    public static function lista(): array
    {
        $limite = now()->addDays(30)->toDateString();
        $rotas = RedeConsulta::rotas()->pluck('r.id');
        $avisos = [];

        $cnh = DB::table('transporte_escolar.motorista as m')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
            ->whereNotNull('m.vencimento_cnh')
            ->where('m.vencimento_cnh', '<=', $limite)
            ->orderBy('m.vencimento_cnh')
            ->get(['m.id', 'p.nome', 'm.vencimento_cnh']);

        foreach ($cnh as $item) {
            $data = Carbon::parse($item->vencimento_cnh)->format('d/m/Y');
            $avisos[] = self::item(
                'cnh-' . $item->id,
                'frota',
                'prazo',
                'fa-id-card',
                'CNH a vencer',
                'CNH de ' . $item->nome . ' vence em ' . $data . '.',
                '/transporte-escolar/motoristas',
                'O motorista ' . $item->nome . ' precisa renovar a CNH até ' . $data . '. Sem CNH vigente, a viagem nova não abre.',
            );
        }

        if (Schema::hasColumn('transporte_escolar.motorista', 'curso_escolar_validade')) {
            $cursos = DB::table('transporte_escolar.motorista as m')
                ->join('cadastro.pessoa as p', 'p.idpes', '=', 'm.ref_idpes')
                ->whereNotNull('m.curso_escolar_validade')
                ->where('m.curso_escolar_validade', '<=', $limite)
                ->orderBy('m.curso_escolar_validade')
                ->get(['m.id', 'p.nome', 'm.curso_escolar_validade']);

            foreach ($cursos as $item) {
                $data = Carbon::parse($item->curso_escolar_validade)->format('d/m/Y');
                $avisos[] = self::item(
                    'curso-' . $item->id,
                    'frota',
                    'prazo',
                    'fa-graduation-cap',
                    'Curso escolar a vencer',
                    'Curso escolar de ' . $item->nome . ' vence em ' . $data . '.',
                    '/transporte-escolar/motoristas',
                    'O curso de transporte escolar de ' . $item->nome . ' vence em ' . $data . '. A viagem nova exige esse curso vigente.',
                );
            }
        }

        $veiculos = DB::table('transporte_escolar.veiculo')
            ->where(function ($q) use ($limite) {
                foreach (['licenciamento_vencimento', 'seguro_vencimento', 'ipva_vencimento', 'vistoria_vencimento'] as $coluna) {
                    $q->orWhere(function ($inner) use ($coluna, $limite) {
                        $inner->whereNotNull($coluna)->where($coluna, '<=', $limite);
                    });
                }
            })
            ->orderBy('placa')
            ->get();

        foreach ($veiculos as $veiculo) {
            $nome = $veiculo->placa ?: $veiculo->descricao;
            $avisos[] = self::item(
                'documento-' . $veiculo->id,
                'frota',
                'prazo',
                'fa-bus',
                'Documento do veículo',
                'Veículo ' . $nome . ' com documento a vencer em 30 dias.',
                '/transporte-escolar/veiculos',
                'Confira licenciamento, seguro, IPVA e vistoria de ' . $nome . '. Documento vencido impede a viagem nova.',
            );
        }

        if (Schema::hasTable('transporte_escolar.autorizacao_veiculo')) {
            $autorizacoes = DB::table('transporte_escolar.autorizacao_veiculo as a')
                ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'a.veiculo_id')
                ->where('a.validade', '<=', $limite)
                ->orderBy('a.validade')
                ->get(['a.id', 'v.placa', 'v.descricao', 'a.validade', 'a.orgao']);

            foreach ($autorizacoes as $item) {
                $nome = $item->placa ?: $item->descricao;
                $orgao = $item->orgao === 'ciretran' ? 'CIRETRAN' : 'DETRAN';
                $data = Carbon::parse($item->validade)->format('d/m/Y');
                $avisos[] = self::item(
                    'autorizacao-' . $item->id,
                    'frota',
                    'prazo',
                    'fa-check-square-o',
                    'Autorização ' . $orgao,
                    'Autorização ' . $orgao . ' de ' . $nome . ' vence em ' . $data . '.',
                    '/transporte-escolar/conformidade',
                    'A autorização ' . $orgao . ' do veículo ' . $nome . ' vence em ' . $data . '. Sem ela vigente, a viagem nova não abre.',
                );
            }
        }

        $lotacao = DB::table('transporte_escolar.rota as r')
            ->join('transporte_escolar.itinerario as i', 'i.rota_id', '=', 'r.id')
            ->join('transporte_escolar.veiculo as v', 'v.id', '=', 'i.veiculo_id')
            ->leftJoin('transporte_escolar.aluno_rota as ar', 'ar.rota_id', '=', 'r.id')
            ->when($rotas->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'), fn ($q) => $q->whereIn('r.id', $rotas))
            ->groupBy('r.id', 'r.descricao', 'r.ano', 'v.id', 'v.placa', 'v.passageiros')
            ->havingRaw('count(distinct ar.ref_cod_aluno) > v.passageiros')
            ->get([
                'r.id',
                'r.descricao',
                'r.ano',
                'v.placa',
                'v.passageiros',
                DB::raw('count(distinct ar.ref_cod_aluno) as alunos'),
            ]);

        foreach ($lotacao as $item) {
            $avisos[] = self::item(
                'lotacao-' . $item->id . '-' . $item->placa,
                'alunos',
                'lotacao',
                'fa-users',
                'Lotação acima do veículo',
                $item->descricao . ' (' . $item->ano . '): ' . $item->alunos . ' alunos para ' . $item->passageiros . ' lugares.',
                '/transporte-escolar/alunos?ano=' . $item->ano,
                'A rota ' . $item->descricao . ' tem ' . $item->alunos . ' alunos e o veículo ' . ($item->placa ?: 'sem placa') . ' tem ' . $item->passageiros . ' lugares.',
            );
        }

        $escolas = VisaoRede::escolas();
        $pedidos = DB::table('transporte_escolar.solicitacao')->whereIn('situacao', ['recebida', 'em_analise']);

        if ($escolas === []) {
            $pedidos->whereRaw('1 = 0');
        } elseif (is_array($escolas)) {
            $pedidos->whereIn('ref_cod_escola', $escolas);
        }

        $abertos = (int) $pedidos->count();

        if ($abertos > 0) {
            $avisos[] = self::item(
                'pedidos-abertos',
                'solicitacoes',
                'pedido',
                'fa-inbox',
                'Pedido da família',
                $abertos === 1 ? '1 pedido da família aguarda análise.' : $abertos . ' pedidos da família aguardam análise.',
                '/transporte-escolar/solicitacoes',
                'Há ' . $abertos . ' pedido(s) recebido(s) ou em análise. A escola encaminha. Aceitar ou recusar é da rede.',
            );
        }

        if (VisaoRede::escolas() === null) {
            $ordens = (int) DB::table('transporte_escolar.ordem_servico')->whereIn('situacao', ['solicitada', 'autorizada', 'em_execucao'])->count();

            if ($ordens > 0) {
                $avisos[] = self::item(
                    'ordens-abertas',
                    'gastos',
                    'servico',
                    'fa-wrench',
                    'Ordem de serviço aberta',
                    $ordens === 1 ? '1 ordem de serviço ainda não foi concluída.' : $ordens . ' ordens de serviço ainda não foram concluídas.',
                    '/transporte-escolar/ordens',
                    'Há ' . $ordens . ' ordem(ns) solicitada(s), autorizada(s) ou em execução.',
                );
            }

            $bilhetes = (int) DB::table('transporte_escolar.abastecimento')->whereIn('situacao', ['solicitado', 'autorizado'])->count();

            if ($bilhetes > 0) {
                $avisos[] = self::item(
                    'bilhetes-abertos',
                    'gastos',
                    'servico',
                    'fa-tint',
                    'Bilhete de abastecimento',
                    $bilhetes === 1 ? '1 bilhete de abastecimento aguarda confirmação.' : $bilhetes . ' bilhetes de abastecimento aguardam confirmação.',
                    '/transporte-escolar/bilhetes',
                    'Há ' . $bilhetes . ' bilhete(s) solicitado(s) ou autorizado(s), ainda sem abastecimento confirmado.',
                );
            }

            if (Schema::hasTable('transporte_escolar.estoque_item')) {
                $baixos = (int) DB::table('transporte_escolar.estoque_item as i')
                    ->leftJoin('transporte_escolar.estoque_lote as l', 'l.item_id', '=', 'i.id')
                    ->where('i.ativo', true)
                    ->groupBy('i.id', 'i.minimo')
                    ->havingRaw('coalesce(sum(l.quantidade), 0) < i.minimo')
                    ->select('i.id')
                    ->get()
                    ->count();

                if ($baixos > 0) {
                    $avisos[] = self::item(
                        'estoque-baixo',
                        'gastos',
                        'servico',
                        'fa-cubes',
                        'Estoque abaixo do mínimo',
                        $baixos === 1 ? '1 item da frota está abaixo do mínimo.' : $baixos . ' itens da frota estão abaixo do mínimo.',
                        '/transporte-escolar/estoque',
                        'O depósito da frota tem ' . $baixos . ' item(ns) ativo(s) com saldo menor que o mínimo cadastrado.',
                    );
                }
            }
        }

        return $avisos;
    }

    public static function sincronizar(): void
    {
        if (!Schema::hasTable('transporte_escolar.notificacao')) {
            return;
        }

        $escopo = self::escopo();
        $marca = 'notificacao_sync_' . $escopo;
        $ultima = DB::table('transporte_escolar.configuracao')->where('chave', $marca)->value('valor');

        if (is_string($ultima) && $ultima !== '' && Carbon::parse($ultima)->gt(now()->subSeconds(25))) {
            return;
        }

        $codigos = [];

        foreach (self::lista() as $aviso) {
            $codigo = $escopo . ':' . $aviso['codigo'];
            $codigos[] = $codigo;
            $gravar = [
                'menu' => $aviso['menu'],
                'tom' => $aviso['tom'],
                'icone' => $aviso['icone'],
                'titulo' => $aviso['titulo'],
                'texto' => $aviso['texto'],
                'url' => $aviso['url'],
                'detalhe' => $aviso['detalhe'],
                'ativa' => true,
                'updated_at' => now(),
            ];
            $existe = DB::table('transporte_escolar.notificacao')->where('chave', $codigo)->exists();

            if ($existe) {
                DB::table('transporte_escolar.notificacao')->where('chave', $codigo)->update($gravar);
            } else {
                DB::table('transporte_escolar.notificacao')->insert($gravar + ['chave' => $codigo, 'created_at' => now()]);
            }
        }

        DB::table('transporte_escolar.notificacao')
            ->where('chave', 'like', $escopo . ':%')
            ->when($codigos !== [], fn ($q) => $q->whereNotIn('chave', $codigos))
            ->update(['ativa' => false, 'updated_at' => now()]);

        DB::table('transporte_escolar.configuracao')->updateOrInsert(
            ['chave' => $marca],
            ['valor' => now()->toDateTimeString()],
        );
    }

    public static function escopo(): string
    {
        $escolas = VisaoRede::escolas();

        if ($escolas === null) {
            return 'rede';
        }

        if ($escolas === []) {
            return 'nenhuma';
        }

        $ids = $escolas;
        sort($ids);

        return 'escola-' . substr(sha1(implode(',', $ids)), 0, 12);
    }

    /**
     * @return array{codigo: string, menu: string, tom: string, icone: string, titulo: string, texto: string, url: string, detalhe: string}
     */
    private static function item(string $codigo, string $menu, string $tom, string $icone, string $titulo, string $texto, string $url, string $detalhe): array
    {
        return compact('codigo', 'menu', 'tom', 'icone', 'titulo', 'texto', 'url', 'detalhe');
    }
}
