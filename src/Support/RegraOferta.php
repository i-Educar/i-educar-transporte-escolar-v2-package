<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A vaga publicada pode limitar idade em 31 de março do ano letivo,
 * séries e turmas. Lista vazia significa que a regra não restringe.
 */
final class RegraOferta
{
    public static function idade(string $nascimento, int $ano): ?int
    {
        $nasc = \DateTimeImmutable::createFromFormat('!Y-m-d', $nascimento);
        $corte = \DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%d-03-31', $ano));

        if ($nasc === false || $corte === false || $nasc > $corte) {
            return null;
        }

        return $corte->diff($nasc)->y;
    }

    public static function cabeIdade(?int $idade, ?int $minima, ?int $maxima): bool
    {
        if ($minima === null && $maxima === null) {
            return true;
        }

        if ($idade === null) {
            return false;
        }

        if ($minima !== null && $idade < $minima) {
            return false;
        }

        return $maxima === null || $idade <= $maxima;
    }

    /**
     * @param list<int> $permitidos
     */
    public static function cabeLista(?int $escolhido, array $permitidos): bool
    {
        if ($permitidos === []) {
            return true;
        }

        return $escolhido !== null && in_array($escolhido, $permitidos, true);
    }

    /**
     * @return list<int>
     */
    public static function lista(mixed $valor): array
    {
        if (is_string($valor) && $valor !== '') {
            $valor = json_decode($valor, true);
        }

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_map(static fn ($id): int => (int) $id, $valor));
    }
}
