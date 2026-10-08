<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class FilaIdempotente
{
    /**
     * Evento de viagem não reescreve aluno, rota nem veículo.
     *
     * @param list<string> $recebidos
     * @param list<string> $jaGravados
     * @return list<string>
     */
    public static function novos(array $recebidos, array $jaGravados): array
    {
        return array_values(array_diff($recebidos, $jaGravados));
    }

    public static function alteraCadastroMestre(string $tipo): bool
    {
        return !in_array($tipo, ['embarque', 'desembarque', 'falta', 'odometro', 'abastecimento', 'ocorrencia'], true);
    }
}
