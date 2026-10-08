<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiTransporteEscolar\Support\PermissaoMatriz;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();

        if ($raiz === null) {
            return;
        }

        $movimentos = Menu::query()->firstOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Movimentações'],
            ['order' => 2, 'type' => 2, 'active' => true],
        );
        $relatorios = Menu::query()->firstOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Relatórios'],
            ['order' => 4, 'type' => 2, 'active' => true],
        );

        $links = [
            [Processos::VIAGENS, $movimentos->getKey(), 'Viagens do dia', '/transporte-escolar/viagens', 'Escola grava o embarque das suas rotas. Institucional vê a rede.'],
            [Processos::OFFLINE, $movimentos->getKey(), 'Uso offline', '/transporte-escolar/offline', 'Baixa a rota e sobe a fila quando houver rede. Escola opera as suas rotas.'],
            [Processos::GASTOS, $movimentos->getKey(), 'Gastos e manutenção', '/transporte-escolar/gastos', 'Só perfil institucional. Salário não entra como despesa do PNATE.'],
            [Processos::RELATORIOS, $relatorios->getKey(), 'Relatórios', '/transporte-escolar/relatorios', 'Escola vê as suas rotas. Institucional vê a rede e exporta.'],
            [Processos::CACS, $relatorios->getKey(), 'Painel do CACS', '/transporte-escolar/cacs', 'Só leitura da rede, para o conselho.'],
        ];

        foreach ($links as [$processo, $pai, $titulo, $link, $descricao]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $pai,
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => 0,
                'type' => 3,
                'active' => true,
            ]);
        }

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);

        foreach ($tipos as $tipo) {
            $matriz = PermissaoMatriz::paraNivel((int) $tipo->nivel);

            foreach ([Processos::VIAGENS, Processos::OFFLINE, Processos::GASTOS, Processos::RELATORIOS, Processos::CACS] as $processo) {
                $flags = $matriz[$processo];

                if (!$flags['visualiza'] && !$flags['cadastra'] && !$flags['exclui']) {
                    continue;
                }

                $menuId = Menu::query()->where('process', $processo)->value('id');

                if ($menuId === null) {
                    continue;
                }

                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    [
                        'visualiza' => $flags['visualiza'] ? 1 : 0,
                        'cadastra' => $flags['cadastra'] ? 1 : 0,
                        'exclui' => $flags['exclui'] ? 1 : 0,
                    ],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $ids = Menu::query()->whereIn('process', [
            Processos::VIAGENS,
            Processos::OFFLINE,
            Processos::GASTOS,
            Processos::RELATORIOS,
            Processos::CACS,
        ])->pluck('id');

        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $ids)->delete();
    }
};
