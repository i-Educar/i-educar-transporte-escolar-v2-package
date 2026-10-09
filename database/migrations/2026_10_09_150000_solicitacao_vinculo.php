<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transporte_escolar.solicitacao')) {
            return;
        }

        Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
            if (!Schema::hasColumn('transporte_escolar.solicitacao', 'ref_cod_aluno')) {
                $table->unsignedInteger('ref_cod_aluno')->nullable();
            }

            if (!Schema::hasColumn('transporte_escolar.solicitacao', 'documentos_em')) {
                $table->timestamp('documentos_em')->nullable();
            }

            if (!Schema::hasColumn('transporte_escolar.solicitacao', 'vinculo_rota_id')) {
                $table->foreignId('vinculo_rota_id')->nullable()->constrained('transporte_escolar.rota');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transporte_escolar.solicitacao')) {
            return;
        }

        Schema::table('transporte_escolar.solicitacao', function (Blueprint $table) {
            if (Schema::hasColumn('transporte_escolar.solicitacao', 'vinculo_rota_id')) {
                $table->dropConstrainedForeignId('vinculo_rota_id');
            }

            if (Schema::hasColumn('transporte_escolar.solicitacao', 'documentos_em')) {
                $table->dropColumn('documentos_em');
            }

            if (Schema::hasColumn('transporte_escolar.solicitacao', 'ref_cod_aluno')) {
                $table->dropColumn('ref_cod_aluno');
            }
        });
    }
};
