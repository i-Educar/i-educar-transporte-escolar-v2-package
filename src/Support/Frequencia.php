<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A falta é o padrão da chamada. Presente só existe quando alguém marcou o embarque.
 * No mês, o aluno é frequente se teve ao menos uma marcação na rota.
 */
final class Frequencia
{
    public static function marca(bool $embarcou): string
    {
        return $embarcou ? 'Presente' : 'Falta';
    }

    public static function situacao(int $presencas, int $viagens): string
    {
        if ($viagens < 1) {
            return 'Sem viagem';
        }

        return $presencas > 0 ? 'Frequente' : 'Não frequente';
    }
}
