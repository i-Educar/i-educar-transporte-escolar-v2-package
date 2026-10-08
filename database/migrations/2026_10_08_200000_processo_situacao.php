<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['transporte_escolar.processo_vaga', 'transporte_escolar.processo_grupo'] as $tabela) {
            if (Schema::hasTable($tabela) && !Schema::hasColumn($tabela, 'situacao')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->string('situacao', 20)->default('aberto');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['transporte_escolar.processo_vaga', 'transporte_escolar.processo_grupo'] as $tabela) {
            if (Schema::hasTable($tabela) && Schema::hasColumn($tabela, 'situacao')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->dropColumn('situacao');
                });
            }
        }
    }
};
