<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.aluno_rota') && !Schema::hasColumn('transporte_escolar.aluno_rota', 'cartao')) {
            Schema::table('transporte_escolar.aluno_rota', function (Blueprint $table) {
                $table->string('cartao', 40)->nullable()->unique();
            });
        }

        if (Schema::hasTable('transporte_escolar.dispositivo') && !Schema::hasColumn('transporte_escolar.dispositivo', 'monitor_id')) {
            Schema::table('transporte_escolar.dispositivo', function (Blueprint $table) {
                $table->foreignId('monitor_id')->nullable()->constrained('transporte_escolar.monitor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transporte_escolar.dispositivo') && Schema::hasColumn('transporte_escolar.dispositivo', 'monitor_id')) {
            Schema::table('transporte_escolar.dispositivo', function (Blueprint $table) {
                $table->dropConstrainedForeignId('monitor_id');
            });
        }

        if (Schema::hasTable('transporte_escolar.aluno_rota') && Schema::hasColumn('transporte_escolar.aluno_rota', 'cartao')) {
            Schema::table('transporte_escolar.aluno_rota', function (Blueprint $table) {
                $table->dropColumn('cartao');
            });
        }
    }
};
