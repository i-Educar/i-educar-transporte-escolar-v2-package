<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FotoIdentificacao
{
    /**
     * @param  list<int>  $donos
     * @return array<int, list<object>>
     */
    public static function mapa(string $tipo, array $donos): array
    {
        $donos = array_values(array_filter(array_map('intval', $donos)));

        if ($donos === [] || !Schema::hasTable('transporte_escolar.foto_identificacao')) {
            return [];
        }

        $mapa = [];

        foreach (DB::table('transporte_escolar.foto_identificacao')->where('tipo', $tipo)->whereIn('dono_id', $donos)->orderBy('id')->get(['id', 'dono_id', 'papel', 'nome']) as $foto) {
            $mapa[(int) $foto->dono_id][] = $foto;
        }

        return $mapa;
    }

    public static function acrescentar(Request $request, string $tipo, int $dono, string $campo): void
    {
        if ($dono <= 0 || !$request->hasFile($campo) || !Schema::hasTable('transporte_escolar.foto_identificacao')) {
            return;
        }

        $request->validate([
            $campo => ['array', 'max:8'],
            $campo . '.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        foreach ($request->file($campo) as $arquivo) {
            self::gravar($tipo, $dono, 'foto', $arquivo);
        }
    }

    public static function substituir(Request $request, string $tipo, int $dono, string $campo): void
    {
        if ($dono <= 0 || !$request->hasFile($campo) || !Schema::hasTable('transporte_escolar.foto_identificacao')) {
            return;
        }

        $request->validate([
            $campo => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        self::remover($tipo, $dono, 'carteirinha');
        self::gravar($tipo, $dono, 'carteirinha', $request->file($campo));
    }

    public static function mostrar(int $id): StreamedResponse
    {
        abort_unless(Schema::hasTable('transporte_escolar.foto_identificacao'), 404);
        $foto = DB::table('transporte_escolar.foto_identificacao')->where('id', $id)->first();
        abort_if($foto === null || !str_starts_with((string) $foto->caminho, 'transporte-escolar/fotos/'), 404);
        abort_unless(Acesso::permite(self::processo((string) $foto->tipo), 'ver'), 403);

        return Storage::response($foto->caminho);
    }

    private static function gravar(string $tipo, int $dono, string $papel, $arquivo): void
    {
        DB::table('transporte_escolar.foto_identificacao')->insert([
            'tipo' => $tipo,
            'dono_id' => $dono,
            'papel' => $papel,
            'caminho' => $arquivo->store('transporte-escolar/fotos'),
            'nome' => $arquivo->getClientOriginalName(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function remover(string $tipo, int $dono, string $papel): void
    {
        $linhas = DB::table('transporte_escolar.foto_identificacao')
            ->where('tipo', $tipo)
            ->where('dono_id', $dono)
            ->where('papel', $papel)
            ->get();

        foreach ($linhas as $linha) {
            if (str_starts_with((string) $linha->caminho, 'transporte-escolar/fotos/')) {
                Storage::delete($linha->caminho);
            }
        }

        DB::table('transporte_escolar.foto_identificacao')
            ->where('tipo', $tipo)
            ->where('dono_id', $dono)
            ->where('papel', $papel)
            ->delete();
    }

    private static function processo(string $tipo): int
    {
        return match ($tipo) {
            'motorista' => Processos::MOTORISTAS,
            'monitor' => Processos::MONITORES,
            'aluno' => Processos::ALUNOS,
            default => Processos::VEICULOS,
        };
    }
}
