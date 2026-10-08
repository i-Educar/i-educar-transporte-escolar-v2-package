<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\Auth;

final class VisaoRede
{
    /**
     * null = rede inteira (poli-institucional e institucional).
     * lista = escolas do usuário (perfil escola).
     * lista vazia = sem acesso de dados (biblioteca e demais).
     *
     * @return list<int>|null
     */
    public static function escolas(): ?array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $nivel = (int) ($user->type->nivel ?? $user->type->level ?? 0);

        if (in_array($nivel, [1, 2], true)) {
            return null;
        }

        if ($nivel !== 4) {
            return [];
        }

        return $user->schools()->pluck('cod_escola')->map(static fn ($id): int => (int) $id)->all();
    }

    public static function rotulo(): string
    {
        $escolas = self::escolas();

        if ($escolas === null) {
            return 'Perfil institucional: a rede inteira.';
        }

        if ($escolas === []) {
            return 'Este perfil não enxerga escolas no transporte.';
        }

        return 'Perfil escolar: só as escolas vinculadas ao seu usuário.';
    }

    public static function contemEscola(int $escolaId): bool
    {
        $escolas = self::escolas();

        return $escolas === null || in_array($escolaId, $escolas, true);
    }
}
