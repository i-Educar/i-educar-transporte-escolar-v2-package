<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
            $table->string('matricula_funcional', 30)->nullable();
        });

        Schema::table('transporte_escolar.monitor', function (Blueprint $table) {
            $table->string('matricula_funcional', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
            $table->dropColumn('matricula_funcional');
        });

        Schema::table('transporte_escolar.monitor', function (Blueprint $table) {
            $table->dropColumn('matricula_funcional');
        });
    }
};
