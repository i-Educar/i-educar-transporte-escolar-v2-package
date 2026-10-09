<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.foto_identificacao')) {
            return;
        }

        Schema::create('transporte_escolar.foto_identificacao', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->unsignedBigInteger('dono_id');
            $table->string('papel', 20);
            $table->string('caminho');
            $table->string('nome');
            $table->timestamps();
            $table->index(['tipo', 'dono_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.foto_identificacao');
    }
};
