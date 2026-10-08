<?php

use iEducar\Packages\BuritiTransporteEscolar\Support\CoreEsquema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CoreEsquema::exigir();

        DB::statement('CREATE SCHEMA IF NOT EXISTS transporte_escolar');

        if (!Schema::hasTable('transporte_escolar.empresa')) {
            Schema::create('transporte_escolar.empresa', function (Blueprint $table) {
                $table->id();
                $table->decimal('ref_idpes', 8, 0);
                $table->string('observacao', 255)->nullable();
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique('ref_idpes');
                $table->foreign('ref_idpes')->references('idpes')->on('cadastro.juridica');
            });
        }

        if (!Schema::hasTable('transporte_escolar.motorista')) {
            Schema::create('transporte_escolar.motorista', function (Blueprint $table) {
                $table->id();
                $table->decimal('ref_idpes', 8, 0);
                $table->foreignId('empresa_id')->nullable()->constrained('transporte_escolar.empresa');
                $table->string('cnh', 15)->nullable();
                $table->string('tipo_cnh', 2)->nullable();
                $table->date('dt_habilitacao')->nullable();
                $table->date('vencimento_cnh')->nullable();
                $table->string('vinculo', 20)->nullable();
                $table->string('turno', 40)->nullable();
                $table->decimal('salario_mensal', 12, 2)->nullable();
                $table->decimal('carga_horaria', 6, 2)->nullable();
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique('ref_idpes');
                $table->foreign('ref_idpes')->references('idpes')->on('cadastro.fisica');
            });
        }

        if (!Schema::hasTable('transporte_escolar.monitor')) {
            Schema::create('transporte_escolar.monitor', function (Blueprint $table) {
                $table->id();
                $table->decimal('ref_idpes', 8, 0);
                $table->string('turno', 40)->nullable();
                $table->decimal('salario_mensal', 12, 2)->nullable();
                $table->decimal('carga_horaria', 6, 2)->nullable();
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique('ref_idpes');
                $table->foreign('ref_idpes')->references('idpes')->on('cadastro.fisica');
            });
        }

        if (!Schema::hasTable('transporte_escolar.fornecedor')) {
            Schema::create('transporte_escolar.fornecedor', function (Blueprint $table) {
                $table->id();
                $table->decimal('ref_idpes', 8, 0);
                $table->string('observacao', 255)->nullable();
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique('ref_idpes');
                $table->foreign('ref_idpes')->references('idpes')->on('cadastro.pessoa');
            });
        }

        if (!Schema::hasTable('transporte_escolar.veiculo')) {
            Schema::create('transporte_escolar.veiculo', function (Blueprint $table) {
                $table->id();
                $table->string('descricao');
                $table->string('placa', 10)->nullable();
                $table->string('renavam', 15)->nullable();
                $table->string('chassi', 30)->nullable();
                $table->string('marca', 50)->nullable();
                $table->unsignedSmallInteger('ano_fabricacao')->nullable();
                $table->unsignedSmallInteger('ano_modelo')->nullable();
                $table->unsignedSmallInteger('passageiros')->default(0);
                $table->char('malha', 1)->default('N');
                $table->unsignedSmallInteger('tipo_censo');
                $table->boolean('exclusivo_escolar')->default(true);
                $table->boolean('adaptado_pcd')->default(false);
                $table->boolean('ativo')->default(true);
                $table->boolean('caminho_da_escola')->default(false);
                $table->decimal('consumo_medio_km_l', 8, 2)->nullable();
                $table->unsignedSmallInteger('qtd_pneus')->nullable();
                $table->date('ipva_vencimento')->nullable();
                $table->date('seguro_vencimento')->nullable();
                $table->date('licenciamento_vencimento')->nullable();
                $table->date('vistoria_vencimento')->nullable();
                $table->foreignId('empresa_id')->nullable()->constrained('transporte_escolar.empresa');
                $table->foreignId('motorista_id')->nullable()->constrained('transporte_escolar.motorista');
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique('placa');
            });
        }

        if (!Schema::hasTable('transporte_escolar.ponto')) {
            Schema::create('transporte_escolar.ponto', function (Blueprint $table) {
                $table->id();
                $table->string('descricao', 70);
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.rota')) {
            Schema::create('transporte_escolar.rota', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('ano');
                $table->string('descricao', 80);
                $table->char('tipo_rota', 1);
                $table->string('periodo', 20);
                $table->char('sentido', 1);
                $table->decimal('km_pav', 8, 2)->nullable();
                $table->decimal('km_npav', 8, 2)->nullable();
                $table->decimal('km_calculado', 8, 2)->nullable();
                $table->unsignedSmallInteger('tempo_estimado_min')->nullable();
                $table->decimal('velocidade_media_kmh', 5, 1)->default(40);
                $table->boolean('terceirizada')->default(false);
                $table->foreignId('empresa_id')->nullable()->constrained('transporte_escolar.empresa');
                $table->string('origem', 20)->default('ieducar');
                $table->string('origem_chave', 120)->nullable();
                $table->timestamps();
                $table->unique(['descricao', 'ano']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.rota_escola')) {
            Schema::create('transporte_escolar.rota_escola', function (Blueprint $table) {
                $table->foreignId('rota_id')->constrained('transporte_escolar.rota')->cascadeOnDelete();
                $table->unsignedInteger('ref_cod_escola');
                $table->primary(['rota_id', 'ref_cod_escola']);
                $table->foreign('ref_cod_escola')->references('cod_escola')->on('pmieducar.escola');
            });
        }

        if (!Schema::hasTable('transporte_escolar.itinerario')) {
            Schema::create('transporte_escolar.itinerario', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rota_id')->constrained('transporte_escolar.rota')->cascadeOnDelete();
                $table->unsignedSmallInteger('seq');
                $table->foreignId('ponto_id')->constrained('transporte_escolar.ponto');
                $table->foreignId('veiculo_id')->nullable()->constrained('transporte_escolar.veiculo');
                $table->time('hora')->nullable();
                $table->char('tipo', 1);
                $table->unique(['rota_id', 'seq']);
            });
        }

        if (!Schema::hasTable('transporte_escolar.tracado')) {
            Schema::create('transporte_escolar.tracado', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rota_id')->unique()->constrained('transporte_escolar.rota')->cascadeOnDelete();
                $table->string('formato', 10);
                $table->text('conteudo');
                $table->decimal('km_calculado', 8, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transporte_escolar.aluno_rota')) {
            Schema::create('transporte_escolar.aluno_rota', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('ref_cod_aluno');
                $table->foreignId('rota_id')->constrained('transporte_escolar.rota')->cascadeOnDelete();
                $table->foreignId('ponto_id')->nullable()->constrained('transporte_escolar.ponto');
                $table->string('turno', 40)->nullable();
                $table->unsignedSmallInteger('ano');
                $table->timestamps();
                $table->unique(['ref_cod_aluno', 'rota_id']);
                $table->foreign('ref_cod_aluno')->references('cod_aluno')->on('pmieducar.aluno');
            });
        }

        if (!Schema::hasTable('transporte_escolar.importacao_linha')) {
            Schema::create('transporte_escolar.importacao_linha', function (Blueprint $table) {
                $table->id();
                $table->string('entidade', 20);
                $table->string('chave', 160)->nullable();
                $table->string('situacao', 10);
                $table->text('mensagem')->nullable();
                $table->unsignedInteger('linha')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte_escolar.importacao_linha');
        Schema::dropIfExists('transporte_escolar.aluno_rota');
        Schema::dropIfExists('transporte_escolar.tracado');
        Schema::dropIfExists('transporte_escolar.itinerario');
        Schema::dropIfExists('transporte_escolar.rota_escola');
        Schema::dropIfExists('transporte_escolar.rota');
        Schema::dropIfExists('transporte_escolar.ponto');
        Schema::dropIfExists('transporte_escolar.veiculo');
        Schema::dropIfExists('transporte_escolar.fornecedor');
        Schema::dropIfExists('transporte_escolar.monitor');
        Schema::dropIfExists('transporte_escolar.motorista');
        Schema::dropIfExists('transporte_escolar.empresa');
    }
};
