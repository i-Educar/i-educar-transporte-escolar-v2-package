<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transporte_escolar.notificacao')) {
            Schema::create('transporte_escolar.notificacao', function (Blueprint $table) {
                $table->id();
                $table->string('chave', 80)->unique();
                $table->string('menu', 30);
                $table->string('tom', 20);
                $table->string('icone', 40);
                $table->string('titulo', 160);
                $table->text('texto');
                $table->string('url', 200);
                $table->text('detalhe')->nullable();
                $table->boolean('ativa')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.notificacao_leitura')) {
            Schema::create('transporte_escolar.notificacao_leitura', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notificacao_id')->constrained('transporte_escolar.notificacao')->cascadeOnDelete();
                $table->unsignedInteger('ref_usuario');
                $table->timestamp('lida_em');
                $table->unique(['notificacao_id', 'ref_usuario']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.notificacao_leitura');
        Schema::dropIfExists('transporte_escolar.notificacao');
    }
};
