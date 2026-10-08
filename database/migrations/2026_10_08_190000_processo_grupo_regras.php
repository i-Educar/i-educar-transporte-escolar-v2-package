<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transporte_escolar.processo_grupo')) {
            Schema::create('transporte_escolar.processo_grupo', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 120);
                $table->unsignedSmallInteger('ano');
                $table->date('inicio');
                $table->date('fim');
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('transporte_escolar.processo_vaga') && !Schema::hasColumn('transporte_escolar.processo_vaga', 'grupo_id')) {
            Schema::table('transporte_escolar.processo_vaga', function (Blueprint $table) {
                $table->foreignId('grupo_id')->nullable()->constrained('transporte_escolar.processo_grupo');
            });
        }

        if (Schema::hasTable('transporte_escolar.processo_vaga_oferta') && !Schema::hasColumn('transporte_escolar.processo_vaga_oferta', 'idade_min')) {
            Schema::table('transporte_escolar.processo_vaga_oferta', function (Blueprint $table) {
                $table->unsignedSmallInteger('idade_min')->nullable();
                $table->unsignedSmallInteger('idade_max')->nullable();
                $table->json('series')->nullable();
                $table->json('turmas')->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.solicitacao') && !Schema::hasColumn('transporte_escolar.solicitacao', 'ref_cod_serie')) {
            Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
                $table->unsignedInteger('ref_cod_serie')->nullable();
                $table->unsignedInteger('ref_cod_turma')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transporte_escolar.solicitacao', 'ref_cod_serie')) {
            Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
                $table->dropColumn(['ref_cod_serie', 'ref_cod_turma']);
            });
        }

        if (Schema::hasColumn('transporte_escolar.processo_vaga_oferta', 'idade_min')) {
            Schema::table('transporte_escolar.processo_vaga_oferta', function (Blueprint $table) {
                $table->dropColumn(['idade_min', 'idade_max', 'series', 'turmas']);
            });
        }

        if (Schema::hasColumn('transporte_escolar.processo_vaga', 'grupo_id')) {
            Schema::table('transporte_escolar.processo_vaga', function (Blueprint $table) {
                $table->dropConstrainedForeignId('grupo_id');
            });
        }

        Schema::dropIfExists('transporte_escolar.processo_grupo');
    }
};
