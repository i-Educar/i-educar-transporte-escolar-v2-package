<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O botão do login segue a mesma regra da pré-matrícula:
 * só aparece quando o pacote vale neste município e há processo de vaga aberto.
 */
final class PortalAberto
{
    public static function url(): ?string
    {
        if (!self::mostrar(
            \App\Support\Tenancy\PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE),
            Schema::hasTable('transporte_escolar.processo_vaga'),
        )) {
            return null;
        }

        $hoje = now()->toDateString();
        $aberto = DB::table('transporte_escolar.processo_vaga')
            ->where('ativo', true)
            ->whereDate('inicio', '<=', $hoje)
            ->whereDate('fim', '>=', $hoje)
            ->exists();

        return $aberto ? url('/transporte-escolar/familia') : null;
    }

    public static function mostrar(bool $pacotePermitido, bool $tabelaExiste): bool
    {
        return $pacotePermitido && $tabelaExiste;
    }
}
