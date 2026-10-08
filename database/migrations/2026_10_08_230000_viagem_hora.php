<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporte_escolar.viagem', function (Blueprint $table) {
            $table->time('hora')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transporte_escolar.viagem', function (Blueprint $table) {
            $table->dropColumn('hora');
        });
    }
};
