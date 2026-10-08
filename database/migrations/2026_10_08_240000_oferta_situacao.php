<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.processo_vaga_oferta') && !Schema::hasColumn('transporte_escolar.processo_vaga_oferta', 'situacao')) {
            Schema::table('transporte_escolar.processo_vaga_oferta', function (Blueprint $table) {
                $table->string('situacao', 20)->default('aberto');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transporte_escolar.processo_vaga_oferta') && Schema::hasColumn('transporte_escolar.processo_vaga_oferta', 'situacao')) {
            Schema::table('transporte_escolar.processo_vaga_oferta', function (Blueprint $table) {
                $table->dropColumn('situacao');
            });
        }
    }
};
