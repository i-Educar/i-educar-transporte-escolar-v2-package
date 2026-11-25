<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O botão do login segue a mesma regra da pré-matrícula:
 * há processo de vaga aberto nesta instalação.
 * No multi-tenant, o pacote também precisa valer para a conexão do município.
 */
final class PortalAberto
{
    public static function url(): ?string
    {
        if (! self::mostrar(
            \App\Support\Tenancy\PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE),
            Schema::hasTable('transporte_escolar.processo_vaga'),
            (bool) config('app.multi_tenant'),
        )) {
            return null;
        }

        $portal = url('/transporte-escolar/familia');

        if (! (bool) config('transporte-escolar.public.exigir_processo_aberto', true)) {
            return $portal;
        }

        $hoje = now()->toDateString();
        $consulta = DB::table('transporte_escolar.processo_vaga as p')
            ->leftJoin('transporte_escolar.processo_grupo as g', 'g.id', '=', 'p.grupo_id')
            ->where('p.ativo', true)
            ->where('p.situacao', 'aberto')
            ->whereDate('p.inicio', '<=', $hoje)
            ->whereDate('p.fim', '>=', $hoje)
            ->where(function ($grupo) use ($hoje) {
                $grupo->whereNull('p.grupo_id')
                    ->orWhere(function ($aberto) use ($hoje) {
                        $aberto->where('g.ativo', true)
                            ->where('g.situacao', 'aberto')
                            ->whereDate('g.inicio', '<=', $hoje)
                            ->whereDate('g.fim', '>=', $hoje);
                    });
            });

        if (Schema::hasTable('transporte_escolar.processo_vaga_oferta')) {
            $consulta->join('transporte_escolar.processo_vaga_oferta as o', 'o.processo_id', '=', 'p.id');

            if (Schema::hasColumn('transporte_escolar.processo_vaga_oferta', 'situacao')) {
                $consulta->where(function ($oferta) {
                    $oferta->where('o.situacao', 'aberto')->orWhereNull('o.situacao');
                });
            }
        }

        return $consulta->exists() ? $portal : null;
    }

    public static function mostrar(bool $pacotePermitido, bool $tabelaExiste, bool $multiTenant = true): bool
    {
        if (!$tabelaExiste) {
            return false;
        }

        if (!$multiTenant) {
            return true;
        }

        return $pacotePermitido;
    }
}
