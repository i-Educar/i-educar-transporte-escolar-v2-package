<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ModeloAviso
{
    public static function texto(string $modelo, string $aluno, string $rota, string $acao, string $reserva): string
    {
        $modelo = trim($modelo);

        if ($modelo === '') {
            return $reserva;
        }

        return strtr($modelo, [
            '{aluno}' => trim($aluno),
            '{rota}' => trim($rota),
            '{acao}' => trim($acao),
        ]);
    }
}
