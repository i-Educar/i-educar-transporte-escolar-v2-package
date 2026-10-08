<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS transporte_escolar.notificacao_modelo (
            id bigserial PRIMARY KEY,
            codigo varchar(40) NOT NULL,
            titulo varchar(120) NOT NULL,
            corpo text NOT NULL,
            ativo boolean NOT NULL DEFAULT true,
            created_at timestamp(0),
            updated_at timestamp(0)
        )');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS notificacao_modelo_codigo_unique ON transporte_escolar.notificacao_modelo (codigo)');

        DB::statement('CREATE TABLE IF NOT EXISTS transporte_escolar.notificacao_inscricao (
            id bigserial PRIMARY KEY,
            token varchar(64) NOT NULL,
            ref_cod_aluno integer NOT NULL,
            endpoint text NOT NULL,
            chave_p256dh text,
            chave_auth text,
            ativo boolean NOT NULL DEFAULT true,
            created_at timestamp(0),
            updated_at timestamp(0)
        )');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS notificacao_inscricao_token_unique ON transporte_escolar.notificacao_inscricao (token)');

        DB::statement('CREATE TABLE IF NOT EXISTS transporte_escolar.notificacao_fila (
            id bigserial PRIMARY KEY,
            inscricao_id bigint NOT NULL,
            aviso_id bigint NOT NULL,
            titulo varchar(160) NOT NULL,
            corpo text NOT NULL,
            entregue_em timestamp(0),
            created_at timestamp(0)
        )');

        $agora = now();
        foreach ([
            ['embarque', 'Embarque', '{aluno} embarcou na rota {rota}.'],
            ['desembarque', 'Desembarque', '{aluno} desembarcou na rota {rota}.'],
            ['fora_cerca', 'Fora do ponto', 'O registro ficou fora da cerca do ponto.'],
        ] as [$codigo, $titulo, $corpo]) {
            if (!DB::table('transporte_escolar.notificacao_modelo')->where('codigo', $codigo)->exists()) {
                DB::table('transporte_escolar.notificacao_modelo')->insert([
                    'codigo' => $codigo,
                    'titulo' => $titulo,
                    'corpo' => $corpo,
                    'ativo' => true,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
            }
        }

        if (Schema::hasTable('transporte_escolar.configuracao') && !DB::table('transporte_escolar.configuracao')->where('chave', 'vapid_publica')->exists()) {
            $par = \iEducar\Packages\BuritiTransporteEscolar\Support\ChavePush::par();
            DB::table('transporte_escolar.configuracao')->insert([
                ['chave' => 'vapid_publica', 'valor' => $par['publica']],
                ['chave' => 'vapid_privada', 'valor' => $par['privada']],
            ]);
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS transporte_escolar.notificacao_fila');
        DB::statement('DROP TABLE IF EXISTS transporte_escolar.notificacao_inscricao');
        DB::statement('DROP TABLE IF EXISTS transporte_escolar.notificacao_modelo');
    }
};
