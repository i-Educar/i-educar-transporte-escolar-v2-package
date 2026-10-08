<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class UsoPonto
{
    public static function motivoBloqueio(int $itinerarios, int $alunos): ?string
    {
        if ($itinerarios > 0) {
            return 'O ponto está no itinerário de uma rota. Retire o ponto da rota antes de apagar.';
        }

        if ($alunos > 0) {
            return 'Há aluno vinculado a este ponto. Troque o ponto do aluno antes de apagar.';
        }

        return null;
    }
}
