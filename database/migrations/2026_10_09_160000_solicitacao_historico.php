<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.processo_vaga') && !Schema::hasColumn('transporte_escolar.processo_vaga', 'documentos_exigidos')) {
            Schema::table('transporte_escolar.processo_vaga', function (Blueprint $table) {
                $table->text('documentos_exigidos')->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.solicitacao_historico')) {
            return;
        }

        Schema::create('transporte_escolar.solicitacao_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('transporte_escolar.solicitacao')->cascadeOnDelete();
            $table->string('situacao', 20);
            $table->timestamp('em');
            $table->string('ator', 160);
            $table->string('lugar', 180);
            $table->text('detalhe')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.solicitacao_historico');

        if (Schema::hasColumn('transporte_escolar.processo_vaga', 'documentos_exigidos')) {
            Schema::table('transporte_escolar.processo_vaga', function (Blueprint $table) {
                $table->dropColumn('documentos_exigidos');
            });
        }
    }
};
