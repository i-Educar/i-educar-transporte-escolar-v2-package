<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transporte_escolar.processo_vaga', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->unsignedSmallInteger('ano');
            $table->date('inicio');
            $table->date('fim');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('transporte_escolar.processo_vaga_oferta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('transporte_escolar.processo_vaga')->cascadeOnDelete();
            $table->foreignId('rota_id')->constrained('transporte_escolar.rota');
            $table->unsignedInteger('ref_cod_escola');
            $table->string('turno', 16);
            $table->unsignedSmallInteger('vagas');
            $table->timestamps();
            $table->unique(['processo_id', 'rota_id', 'ref_cod_escola', 'turno']);
        });

        Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
            $table->foreignId('processo_id')->nullable()->constrained('transporte_escolar.processo_vaga');
            $table->foreignId('rota_id')->nullable()->constrained('transporte_escolar.rota');
            $table->foreignId('oferta_id')->nullable()->constrained('transporte_escolar.processo_vaga_oferta');
        });
    }

    public function down(): void
    {
        Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
            $table->dropConstrainedForeignId('oferta_id');
            $table->dropConstrainedForeignId('rota_id');
            $table->dropConstrainedForeignId('processo_id');
        });
        Schema::dropIfExists('transporte_escolar.processo_vaga_oferta');
        Schema::dropIfExists('transporte_escolar.processo_vaga');
    }
};
