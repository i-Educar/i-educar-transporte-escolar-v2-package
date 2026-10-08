<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class AvisoTexto
{
    public static function frase(string $nome, string $tipo, string $rota): string
    {
        $acao = $tipo === 'desembarque' ? 'desembarcou' : 'embarcou';

        return trim($nome) . ' ' . $acao . ' na rota ' . trim($rota) . '.';
    }
}
