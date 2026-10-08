<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\DB;

final class IdentidadeMunicipio
{
    public static function rotulo(): string
    {
        try {
            $linha = DB::table('pmieducar.instituicao')
                ->orderBy('cod_instituicao')
                ->first(['nm_instituicao', 'cidade', 'ref_sigla_uf']);
        } catch (\Throwable) {
            return 'Prefeitura Municipal';
        }

        if ($linha === null) {
            return 'Prefeitura Municipal';
        }

        $nome = trim((string) $linha->nm_instituicao);

        if ($nome !== '') {
            return $nome;
        }

        $cidade = trim((string) $linha->cidade . ($linha->ref_sigla_uf ? ' / ' . $linha->ref_sigla_uf : ''));

        return $cidade !== '' ? $cidade : 'Prefeitura Municipal';
    }

    public static function arquivo(): ?string
    {
        $nome = basename((string) config('legacy.report.logo_file_name', 'brasil.png'));
        $candidatos = [
            base_path('ieducar/modules/Reports/ReportLogos/' . $nome),
            base_path('packages/portabilis/i-educar-reports-package/ieducar/ReportLogos/' . $nome),
            public_path('intranet/imagens/brasao-republica.png'),
        ];

        foreach ($candidatos as $arquivo) {
            if (is_file($arquivo)) {
                return $arquivo;
            }
        }

        return null;
    }
}
