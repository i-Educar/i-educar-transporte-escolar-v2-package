<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Quando o veículo leva menos alunos do que a rota tem, cada viagem
 * repete o mesmo percurso, uma depois da outra.
 */
final class CapacidadeViagem
{
    /**
     * @param list<object> $paradas
     * @return list<string>
     */
    public static function linhas(int $alunos, int $passageiros, int $minutos, array $paradas): array
    {
        if ($passageiros < 1 || $alunos <= $passageiros) {
            return [];
        }

        $viagens = (int) ceil($alunos / $passageiros);
        $duracao = $minutos > 0 ? $minutos : self::duracao($paradas);
        $saida = self::minutosDoDia(self::primeiraHora($paradas));
        $percurso = self::percurso($paradas);
        $linhas = [
            'O veículo leva ' . $passageiros . ' passageiros e a rota tem ' . $alunos . ' alunos. São ' . $viagens . ' viagens.',
        ];

        for ($numero = 1; $numero <= $viagens; $numero++) {
            $neste = $numero === $viagens ? $alunos - ($passageiros * ($viagens - 1)) : $passageiros;
            $quando = 'tempo do percurso ainda não calculado';

            if ($saida !== null && $duracao !== null && $duracao > 0) {
                $inicio = self::formatar($saida + ($duracao * ($numero - 1)));
                $fim = self::formatar($saida + ($duracao * $numero));
                $quando = $inicio . '–' . $fim . ' · ' . $duracao . ' min';
            }

            $linhas[] = 'Viagem ' . $numero . ': ' . $neste . ' alunos · ' . $quando . ($percurso !== '' ? ' · ' . $percurso : '');
        }

        return $linhas;
    }

    /**
     * @param list<object> $paradas
     */
    private static function percurso(array $paradas): string
    {
        $nomes = [];

        foreach ($paradas as $parada) {
            $nome = trim((string) ($parada->descricao ?? ''));

            if ($nome !== '') {
                $nomes[] = $nome;
            }
        }

        return implode(' → ', $nomes);
    }

    /**
     * @param list<object> $paradas
     */
    private static function primeiraHora(array $paradas): ?string
    {
        foreach ($paradas as $parada) {
            $hora = trim((string) ($parada->hora ?? ''));

            if ($hora !== '') {
                return $hora;
            }
        }

        return null;
    }

    /**
     * @param list<object> $paradas
     */
    private static function duracao(array $paradas): ?int
    {
        $horas = [];

        foreach ($paradas as $parada) {
            $minutos = self::minutosDoDia($parada->hora ?? null);

            if ($minutos !== null) {
                $horas[] = $minutos;
            }
        }

        if (count($horas) < 2) {
            return null;
        }

        $diff = max($horas) - min($horas);

        return $diff > 0 ? $diff : null;
    }

    private static function minutosDoDia(mixed $hora): ?int
    {
        if (!is_string($hora) || !preg_match('/^(\d{1,2}):(\d{2})/', trim($hora), $partes)) {
            return null;
        }

        return ((int) $partes[1] * 60) + (int) $partes[2];
    }

    private static function formatar(int $minutos): string
    {
        $minutos = $minutos % (24 * 60);

        if ($minutos < 0) {
            $minutos += 24 * 60;
        }

        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
