<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class DiaLetivo
{
    /**
     * @param list<?string> $tiposMotivo null e "e" contam como letivo
     */
    public static function eLetivo(bool $calendarioDoAnoExiste, array $tiposMotivo): ?bool
    {
        if (!$calendarioDoAnoExiste) {
            return null;
        }

        foreach ($tiposMotivo as $tipo) {
            if ($tipo === null || $tipo === 'e') {
                return true;
            }
        }

        return false;
    }
}
