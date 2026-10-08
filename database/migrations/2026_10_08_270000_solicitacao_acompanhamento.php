<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('transporte_escolar.solicitacao', 'acompanhamento')) {
            DB::statement('ALTER TABLE transporte_escolar.solicitacao ADD COLUMN acompanhamento varchar(12)');
            DB::statement('ALTER TABLE transporte_escolar.solicitacao ADD COLUMN contato_escola varchar(160)');
            DB::statement('ALTER TABLE transporte_escolar.solicitacao ADD COLUMN contato_setor varchar(160)');
            DB::statement('CREATE UNIQUE INDEX transporte_escolar_solicitacao_acompanhamento_unique ON transporte_escolar.solicitacao (acompanhamento)');
        }

        foreach (DB::table('transporte_escolar.solicitacao')->whereNull('acompanhamento')->pluck('id') as $id) {
            DB::table('transporte_escolar.solicitacao')->where('id', $id)->update([
                'acompanhamento' => $this->codigo(),
            ]);
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transporte_escolar.solicitacao DROP COLUMN IF EXISTS contato_setor');
        DB::statement('ALTER TABLE transporte_escolar.solicitacao DROP COLUMN IF EXISTS contato_escola');
        DB::statement('ALTER TABLE transporte_escolar.solicitacao DROP COLUMN IF EXISTS acompanhamento');
    }

    private function codigo(): string
    {
        do {
            $codigo = Str::lower(Str::random(10));
        } while (DB::table('transporte_escolar.solicitacao')->where('acompanhamento', $codigo)->exists());

        return $codigo;
    }
};
