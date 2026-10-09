<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.responsavel_transporte')) {
            return;
        }

        Schema::create('transporte_escolar.responsavel_transporte', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ref_cod_aluno')->unique();
            $table->string('nome', 150);
            $table->string('telefone', 20)->nullable();
            $table->timestamps();
            $table->foreign('ref_cod_aluno')->references('cod_aluno')->on('pmieducar.aluno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.responsavel_transporte');
    }
};
