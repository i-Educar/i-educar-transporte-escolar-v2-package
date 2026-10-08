<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transporte_escolar.configuracao')) {
            Schema::create('transporte_escolar.configuracao', function (Blueprint $table) {
                $table->id();
                $table->string('chave')->unique();
                $table->text('valor')->nullable();
            });
        }

        if (!Schema::hasTable('transporte_escolar.dispositivo')) {
            Schema::create('transporte_escolar.dispositivo', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 80);
                $table->string('token', 64)->unique();
                $table->unsignedInteger('ref_usuario')->nullable();
                $table->foreignId('motorista_id')->nullable()->constrained('transporte_escolar.motorista');
                $table->timestamp('ultima_descarga_em')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.viagem')) {
            Schema::create('transporte_escolar.viagem', function (Blueprint $table) {
                $table->id();
                $table->date('data');
                $table->foreignId('rota_id')->constrained('transporte_escolar.rota');
                $table->foreignId('veiculo_id')->nullable()->constrained('transporte_escolar.veiculo');
                $table->foreignId('motorista_id')->nullable()->constrained('transporte_escolar.motorista');
                $table->foreignId('monitor_id')->nullable()->constrained('transporte_escolar.monitor');
                $table->char('sentido', 1);
                $table->decimal('km_inicial', 10, 1)->nullable();
                $table->decimal('km_final', 10, 1)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.fila_evento')) {
            Schema::create('transporte_escolar.fila_evento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispositivo_id')->nullable()->constrained('transporte_escolar.dispositivo');
                $table->string('uuid', 64)->unique();
                $table->string('tipo', 20);
                $table->json('payload');
                $table->timestamp('ocorrido_em')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamp('aplicado_em')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.embarque')) {
            Schema::create('transporte_escolar.embarque', function (Blueprint $table) {
                $table->id();
                $table->foreignId('viagem_id')->constrained('transporte_escolar.viagem')->cascadeOnDelete();
                $table->unsignedInteger('ref_cod_aluno');
                $table->string('tipo', 20);
                $table->timestamp('ocorrido_em')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('fila_uuid', 64)->nullable()->unique();
                $table->timestamps();
                $table->unique(['viagem_id', 'ref_cod_aluno', 'tipo']);
                $table->foreign('ref_cod_aluno')->references('cod_aluno')->on('pmieducar.aluno');
            });
        }

        if (!Schema::hasTable('transporte_escolar.abastecimento')) {
            Schema::create('transporte_escolar.abastecimento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('transporte_escolar.veiculo');
                $table->foreignId('fornecedor_id')->nullable()->constrained('transporte_escolar.fornecedor');
                $table->date('data');
                $table->decimal('litros', 10, 2);
                $table->decimal('valor', 12, 2);
                $table->decimal('km', 10, 1)->nullable();
                $table->string('fonte', 20)->default('pnate');
                $table->string('fila_uuid', 64)->nullable()->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.manutencao_plano')) {
            Schema::create('transporte_escolar.manutencao_plano', function (Blueprint $table) {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('transporte_escolar.veiculo')->cascadeOnDelete();
                $table->string('item', 40);
                $table->unsignedInteger('a_cada_km')->nullable();
                $table->unsignedSmallInteger('a_cada_dias')->nullable();
                $table->decimal('ultimo_km', 10, 1)->nullable();
                $table->date('ultima_data')->nullable();
            });
        }

        if (!Schema::hasTable('transporte_escolar.ordem_servico')) {
            Schema::create('transporte_escolar.ordem_servico', function (Blueprint $table) {
                $table->id();
                $table->foreignId('veiculo_id')->constrained('transporte_escolar.veiculo');
                $table->foreignId('fornecedor_id')->nullable()->constrained('transporte_escolar.fornecedor');
                $table->boolean('preventiva')->default(false);
                $table->string('descricao');
                $table->string('peca')->nullable();
                $table->decimal('mao_obra', 12, 2)->nullable();
                $table->decimal('km', 10, 1)->nullable();
                $table->string('nota', 40)->nullable();
                $table->decimal('valor', 12, 2);
                $table->date('data');
                $table->string('fonte', 20);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.gasto')) {
            Schema::create('transporte_escolar.gasto', function (Blueprint $table) {
                $table->id();
                $table->date('data');
                $table->string('categoria', 40);
                $table->string('fonte', 20);
                $table->decimal('valor', 12, 2);
                $table->string('descricao')->nullable();
                $table->foreignId('veiculo_id')->nullable()->constrained('transporte_escolar.veiculo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.contrato')) {
            Schema::create('transporte_escolar.contrato', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('transporte_escolar.empresa');
                $table->string('descricao');
                $table->decimal('valor_mensal', 12, 2);
                $table->date('inicio');
                $table->date('fim')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.parcela_recurso')) {
            Schema::create('transporte_escolar.parcela_recurso', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('ano');
                $table->unsignedTinyInteger('mes');
                $table->string('fonte', 20);
                $table->decimal('valor', 12, 2);
                $table->decimal('saldo_informado', 12, 2)->nullable();
                $table->unique(['ano', 'mes', 'fonte']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.ocorrencia')) {
            Schema::create('transporte_escolar.ocorrencia', function (Blueprint $table) {
                $table->id();
                $table->foreignId('viagem_id')->nullable()->constrained('transporte_escolar.viagem');
                $table->text('texto');
                $table->timestamp('ocorrido_em')->nullable();
                $table->string('fila_uuid', 64)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.ocorrencia');
        Schema::dropIfExists('transporte_escolar.parcela_recurso');
        Schema::dropIfExists('transporte_escolar.contrato');
        Schema::dropIfExists('transporte_escolar.gasto');
        Schema::dropIfExists('transporte_escolar.ordem_servico');
        Schema::dropIfExists('transporte_escolar.manutencao_plano');
        Schema::dropIfExists('transporte_escolar.abastecimento');
        Schema::dropIfExists('transporte_escolar.embarque');
        Schema::dropIfExists('transporte_escolar.fila_evento');
        Schema::dropIfExists('transporte_escolar.viagem');
        Schema::dropIfExists('transporte_escolar.dispositivo');
        Schema::dropIfExists('transporte_escolar.configuracao');
    }
};
