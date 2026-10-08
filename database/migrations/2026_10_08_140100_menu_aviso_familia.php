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

        $movimentos = Menu::query()->where('parent_id', $raiz->getKey())->where('title', 'Movimentações')->first();

        if ($movimentos === null) {
            return;
        }

        Menu::query()->updateOrCreate(['process' => Processos::AVISOS], [
            'parent_id' => $movimentos->getKey(),
            'title' => 'Aviso à família',
            'description' => 'Link do embarque e do desembarque. Não envia mensagem para fora do i-Educar.',
            'link' => '/transporte-escolar/avisos',
            'order' => 0,
            'type' => 3,
            'active' => true,
        ]);

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);
        $menuId = Menu::query()->where('process', Processos::AVISOS)->value('id');

        foreach ($tipos as $tipo) {
            $flags = PermissaoMatriz::paraNivel((int) $tipo->nivel)[Processos::AVISOS];

            if (!$flags['visualiza']) {
                continue;
            }

            DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                [
                    'visualiza' => 1,
                    'cadastra' => $flags['cadastra'] ? 1 : 0,
                    'exclui' => 0,
                ],
            );
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $ids = Menu::query()->where('process', Processos::AVISOS)->pluck('id');
        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $ids)->delete();
    }
};
