<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.aluno_rota') && !Schema::hasColumn('transporte_escolar.aluno_rota', 'residencia_latitude')) {
            Schema::table('transporte_escolar.aluno_rota', function (Blueprint $table) {
                $table->decimal('residencia_latitude', 10, 7)->nullable();
                $table->decimal('residencia_longitude', 10, 7)->nullable();
            });
        }

        if (Schema::hasTable('transporte_escolar.viagem') && !Schema::hasColumn('transporte_escolar.viagem', 'km_trilha')) {
            Schema::table('transporte_escolar.viagem', function (Blueprint $table) {
                $table->decimal('km_trilha', 8, 2)->nullable();
            });
        }

        if (!Schema::hasTable('transporte_escolar.fila_pendente')) {
            Schema::create('transporte_escolar.fila_pendente', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispositivo_id')->constrained('transporte_escolar.dispositivo')->cascadeOnDelete();
                $table->date('dia');
                $table->unsignedSmallInteger('quantidade');
                $table->timestamp('updated_at')->nullable();
                $table->unique(['dispositivo_id', 'dia']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.comprovante')) {
            Schema::create('transporte_escolar.comprovante', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gasto_id')->nullable()->constrained('transporte_escolar.gasto')->cascadeOnDelete();
                $table->string('nome');
                $table->string('caminho');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.comprovante');
        Schema::dropIfExists('transporte_escolar.fila_pendente');

        if (Schema::hasColumn('transporte_escolar.viagem', 'km_trilha')) {
            Schema::table('transporte_escolar.viagem', function (Blueprint $table) {
                $table->dropColumn('km_trilha');
            });
        }

        if (Schema::hasColumn('transporte_escolar.aluno_rota', 'residencia_latitude')) {
            Schema::table('transporte_escolar.aluno_rota', function (Blueprint $table) {
                $table->dropColumn(['residencia_latitude', 'residencia_longitude']);
            });
        }
    }
};
