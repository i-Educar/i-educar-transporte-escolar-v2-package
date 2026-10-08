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
        DB::table('transporte_escolar.processo_vaga')->where('situacao', 'bloqueado')->update(['situacao' => 'pausado', 'ativo' => false]);
        DB::table('transporte_escolar.processo_grupo')->where('situacao', 'bloqueado')->update(['situacao' => 'pausado', 'ativo' => false]);

        $funcao = DB::selectOne("select 1 as ok from pg_proc p join pg_namespace n on n.oid = p.pronamespace where n.nspname = 'public' and p.proname = 'audit'");

        if ($funcao === null) {
            return;
        }

        $tabelas = DB::select("select table_name from information_schema.tables where table_schema = 'transporte_escolar' and table_type = 'BASE TABLE'");

        foreach ($tabelas as $tabela) {
            $nome = $tabela->table_name;

            if (!preg_match('/^[a-z0-9_]+$/', $nome)) {
                continue;
            }

            DB::unprepared('drop trigger if exists transporte_' . $nome . '_audit on transporte_escolar.' . $nome);
            DB::unprepared('create trigger transporte_' . $nome . '_audit after insert or update or delete on transporte_escolar.' . $nome . ' for each row execute procedure public.audit()');
        }

        $pai = Menu::query()->where('process', Processos::RELATORIOS)->value('parent_id')
            ?? Menu::query()->where('process', Processos::RAIZ)->value('id');

        if ($pai === null) {
            return;
        }

        Menu::query()->updateOrCreate(['process' => Processos::AUDITORIA], [
            'parent_id' => $pai,
            'title' => 'Auditoria',
            'description' => 'Inclusão, alteração e exclusão do transporte escolar.',
            'link' => '/transporte-escolar/auditoria',
            'order' => 9,
            'type' => 3,
            'active' => true,
        ]);

        $menuId = Menu::query()->where('process', Processos::AUDITORIA)->value('id');
        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);

        foreach ($tipos as $tipo) {
            $flags = PermissaoMatriz::paraNivel((int) $tipo->nivel)[Processos::AUDITORIA];

            if (!$flags['visualiza']) {
                continue;
            }

            DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                ['visualiza' => 1, 'cadastra' => 0, 'exclui' => 0],
            );
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $tabelas = DB::select("select table_name from information_schema.tables where table_schema = 'transporte_escolar' and table_type = 'BASE TABLE'");

        foreach ($tabelas as $tabela) {
            $nome = $tabela->table_name;

            if (!preg_match('/^[a-z0-9_]+$/', $nome)) {
                continue;
            }

            DB::unprepared('drop trigger if exists transporte_' . $nome . '_audit on transporte_escolar.' . $nome);
        }
    }
};
