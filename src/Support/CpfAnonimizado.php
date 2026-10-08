<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class CpfAnonimizado
{
    public static function exibir(mixed $cpf): string
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf) ?? '';

        if ($digitos === '') {
            return 'sem CPF';
        }

        $digitos = str_pad(substr($digitos, -11), 11, '0', STR_PAD_LEFT);

        return '***.***.***-' . substr($digitos, -2);
    }
}
