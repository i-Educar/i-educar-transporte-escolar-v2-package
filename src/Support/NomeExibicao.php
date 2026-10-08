<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class NomeExibicao
{
    public static function exibir(string $nome, bool $completo): string
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome) ?? '');

        if ($nome === '' || $completo) {
            return $nome;
        }

        $partes = explode(' ', $nome);

        if (count($partes) === 1) {
            return $partes[0];
        }

        $ultimo = $partes[count($partes) - 1];

        return $partes[0] . ' ' . mb_substr($ultimo, 0, 1) . '.';
    }
}
