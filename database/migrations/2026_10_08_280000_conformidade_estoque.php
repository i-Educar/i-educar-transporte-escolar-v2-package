<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transporte_escolar.motorista') && !Schema::hasColumn('transporte_escolar.motorista', 'curso_escolar_validade')) {
            Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
                $table->date('curso_escolar_validade')->nullable();
            });
        }

        if (!Schema::hasTable('transporte_escolar.autorizacao_veiculo')) {
            Schema::create('transporte_escolar.autorizacao_veiculo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('transporte_escolar.veiculo');
                $table->string('orgao', 20);
                $table->string('numero', 40);
                $table->date('validade');
                $table->string('anexo_nome')->nullable();
                $table->string('anexo_caminho')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.checklist_veiculo')) {
            Schema::create('transporte_escolar.checklist_veiculo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('transporte_escolar.veiculo');
                $table->string('item', 30);
                $table->boolean('conforme')->default(false);
                $table->string('observacao')->nullable();
                $table->string('anexo_nome')->nullable();
                $table->string('anexo_caminho')->nullable();
                $table->timestamps();
                $table->unique(['veiculo_id', 'item']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.estoque_item')) {
            Schema::create('transporte_escolar.estoque_item', function (Blueprint $table) {
                $table->id();
                $table->string('tipo', 20);
                $table->string('nome');
                $table->string('unidade', 20);
                $table->decimal('minimo', 12, 3)->default(0);
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.estoque_lote')) {
            Schema::create('transporte_escolar.estoque_lote', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->constrained('transporte_escolar.estoque_item');
                $table->string('codigo', 40);
                $table->decimal('quantidade', 12, 3)->default(0);
                $table->date('validade')->nullable();
                $table->timestamps();
                $table->unique(['item_id', 'codigo']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.estoque_movimento')) {
            Schema::create('transporte_escolar.estoque_movimento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lote_id')->constrained('transporte_escolar.estoque_lote');
                $table->string('tipo', 20);
                $table->decimal('quantidade', 12, 3);
                $table->decimal('saldo_anterior', 12, 3);
                $table->decimal('saldo_novo', 12, 3);
                $table->foreignId('ordem_id')->nullable()->constrained('transporte_escolar.ordem_servico');
                $table->foreignId('veiculo_id')->nullable()->constrained('transporte_escolar.veiculo');
                $table->string('motivo')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        $funcao = DB::selectOne("select 1 as ok from pg_proc p join pg_namespace n on n.oid = p.pronamespace where n.nspname = 'public' and p.proname = 'audit'");

        if ($funcao === null) {
            return;
        }

        foreach (['autorizacao_veiculo', 'checklist_veiculo', 'estoque_item', 'estoque_lote', 'estoque_movimento'] as $nome) {
            DB::unprepared('drop trigger if exists transporte_' . $nome . '_audit on transporte_escolar.' . $nome);
            DB::unprepared('create trigger transporte_' . $nome . '_audit after insert or update or delete on transporte_escolar.' . $nome . ' for each row execute procedure public.audit()');
        }
    }

    public function down(): void
    {
        foreach (['estoque_movimento', 'estoque_lote', 'estoque_item', 'checklist_veiculo', 'autorizacao_veiculo'] as $nome) {
            DB::unprepared('drop trigger if exists transporte_' . $nome . '_audit on transporte_escolar.' . $nome);
        }

        Schema::dropIfExists('transporte_escolar.estoque_movimento');
        Schema::dropIfExists('transporte_escolar.estoque_lote');
        Schema::dropIfExists('transporte_escolar.estoque_item');
        Schema::dropIfExists('transporte_escolar.checklist_veiculo');
        Schema::dropIfExists('transporte_escolar.autorizacao_veiculo');

        if (Schema::hasColumn('transporte_escolar.motorista', 'curso_escolar_validade')) {
            Schema::table('transporte_escolar.motorista', function (Blueprint $table) {
                $table->dropColumn('curso_escolar_validade');
            });
        }
    }
};
