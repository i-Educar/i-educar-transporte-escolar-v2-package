<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.ordem_servico') && !Schema::hasColumn('transporte_escolar.ordem_servico', 'protocolo')) {
            Schema::table('transporte_escolar.ordem_servico', function (Blueprint $table) {
                $table->string('protocolo', 20)->nullable()->unique();
                $table->string('situacao', 20)->default('concluida');
                $table->string('solicitante', 120)->nullable();
                $table->text('observacao')->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.abastecimento') && !Schema::hasColumn('transporte_escolar.abastecimento', 'protocolo')) {
            Schema::table('transporte_escolar.abastecimento', function (Blueprint $table) {
                $table->string('protocolo', 20)->nullable()->unique();
                $table->string('situacao', 20)->default('abastecido');
                $table->decimal('litros_autorizados', 10, 2)->nullable();
                $table->string('posto', 120)->nullable();
                $table->string('motorista', 120)->nullable();
                $table->string('solicitante', 120)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transporte_escolar.ordem_servico', 'protocolo')) {
            Schema::table('transporte_escolar.ordem_servico', function (Blueprint $table) {
                $table->dropUnique(['protocolo']);
                $table->dropColumn(['protocolo', 'situacao', 'solicitante', 'observacao']);
            });
        }

        if (Schema::hasColumn('transporte_escolar.abastecimento', 'protocolo')) {
            Schema::table('transporte_escolar.abastecimento', function (Blueprint $table) {
                $table->dropUnique(['protocolo']);
                $table->dropColumn(['protocolo', 'situacao', 'litros_autorizados', 'posto', 'motorista', 'solicitante']);
            });
        }
    }
};
