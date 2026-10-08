<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiTransporteEscolar\Support\PermissaoMatriz;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporte_escolar.solicitacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ano');
            $table->unsignedInteger('sequencia');
            $table->string('protocolo', 24)->unique();
            $table->string('nome_aluno');
            $table->date('nascimento');
            $table->string('nome_responsavel');
            $table->string('telefone', 11);
            $table->string('endereco');
            $table->string('turno', 16);
            $table->string('escola_informada');
            $table->integer('ref_cod_escola')->nullable();
            $table->string('situacao', 20);
            $table->string('motivo_situacao')->nullable();
            $table->timestamp('pedido_em');
            $table->timestamp('atualizado_em');
            $table->unique(['ano', 'sequencia']);
        });

        $movimentos = Menu::query()
            ->where('title', 'Movimentações')
            ->whereIn('parent_id', Menu::query()->where('process', Processos::RAIZ)->pluck('id'))
            ->first();

        if ($movimentos === null) {
            return;
        }

        Menu::query()->updateOrCreate(['process' => Processos::SOLICITACOES], [
            'parent_id' => $movimentos->getKey(),
            'title' => 'Pedidos da família',
            'description' => 'A família pede no portal. A escola encaminha. A rede aceita ou recusa.',
            'link' => '/transporte-escolar/solicitacoes',
            'order' => 0,
            'type' => 3,
            'active' => true,
        ]);

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);
        $menuId = Menu::query()->where('process', Processos::SOLICITACOES)->value('id');

        foreach ($tipos as $tipo) {
            $flags = PermissaoMatriz::paraNivel((int) $tipo->nivel)[Processos::SOLICITACOES];

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
        $ids = Menu::query()->where('process', Processos::SOLICITACOES)->pluck('id');
        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $ids)->delete();
        Schema::dropIfExists('transporte_escolar.solicitacao');
    }
};
