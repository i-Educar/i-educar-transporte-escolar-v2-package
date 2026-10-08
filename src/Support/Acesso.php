<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class Acesso
{
    public static function permite(int $processo, string $acao): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $menuId = DB::table('menus')->where('process', $processo)->value('id');

        if ($menuId === null) {
            return false;
        }

        $linha = DB::table('pmieducar.menu_tipo_usuario')
            ->where('menu_id', $menuId)
            ->where('ref_cod_tipo_usuario', $user->ref_cod_tipo_usuario)
            ->first();

        if ($linha === null) {
            return false;
        }

        return match ($acao) {
            'gravar' => (int) $linha->cadastra === 1,
            'excluir' => (int) $linha->exclui === 1,
            default => (int) $linha->visualiza === 1,
        };
    }
}
