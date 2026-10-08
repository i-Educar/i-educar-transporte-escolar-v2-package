<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.motorista') && !Schema::hasColumn('transporte_escolar.motorista', 'encargo_percent')) {
            Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
                $table->decimal('encargo_percent', 5, 2)->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.monitor') && !Schema::hasColumn('transporte_escolar.monitor', 'encargo_percent')) {
            Schema::table('transporte_escolar.monitor', function (Blueprint $table) {
                $table->decimal('encargo_percent', 5, 2)->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.embarque') && !Schema::hasColumn('transporte_escolar.embarque', 'fora_da_cerca')) {
            Schema::table('transporte_escolar.embarque', function (Blueprint $table) {
                $table->boolean('fora_da_cerca')->default(false);
            });
        }

        if (Schema::hasTable('transporte_escolar.ponto') && !Schema::hasColumn('transporte_escolar.ponto', 'raio_metros')) {
            Schema::table('transporte_escolar.ponto', function (Blueprint $table) {
                $table->unsignedSmallInteger('raio_metros')->nullable();
            });
        }

        if (!Schema::hasTable('transporte_escolar.aviso_familia')) {
            Schema::create('transporte_escolar.aviso_familia', function (Blueprint $table) {
                $table->id();
                $table->foreignId('embarque_id')->unique()->constrained('transporte_escolar.embarque')->cascadeOnDelete();
                $table->unsignedInteger('ref_cod_aluno');
                $table->string('token', 64)->unique();
                $table->string('texto');
                $table->timestamp('lido_em')->nullable();
                $table->timestamps();
                $table->foreign('ref_cod_aluno')->references('cod_aluno')->on('pmieducar.aluno');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.aviso_familia');

        if (Schema::hasColumn('transporte_escolar.ponto', 'raio_metros')) {
            Schema::table('transporte_escolar.ponto', function (Blueprint $table) {
                $table->dropColumn('raio_metros');
            });
        }

        if (Schema::hasColumn('transporte_escolar.embarque', 'fora_da_cerca')) {
            Schema::table('transporte_escolar.embarque', function (Blueprint $table) {
                $table->dropColumn('fora_da_cerca');
            });
        }

        if (Schema::hasColumn('transporte_escolar.monitor', 'encargo_percent')) {
            Schema::table('transporte_escolar.monitor', function (Blueprint $table) {
                $table->dropColumn('encargo_percent');
            });
        }

        if (Schema::hasColumn('transporte_escolar.motorista', 'encargo_percent')) {
            Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
                $table->dropColumn('encargo_percent');
            });
        }
    }
};
