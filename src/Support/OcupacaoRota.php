<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Na ida, o aluno sobe no ponto da casa e todos descem na última parada.
 * Na volta, todos sobem na primeira parada e cada um desce no próprio ponto.
 */
final class OcupacaoRota
{
    /**
     * @param iterable<int, object> $paradas
     * @param array<int, int> $alunosNoPonto
     * @return list<array{sentido: string, rotulo: string, seq: int, nome: string, hora: string, embarcam: int, desembarcam: int, a_bordo: int}>
     */
    public static function linhas(iterable $paradas, array $alunosNoPonto): array
    {
        $grupos = ['I' => [], 'V' => []];

        foreach ($paradas as $parada) {
            $tipo = ($parada->tipo ?? 'I') === 'V' ? 'V' : 'I';
            $grupos[$tipo][] = $parada;
        }

        $saida = [];

        foreach (['I' => 'Ida', 'V' => 'Volta'] as $tipo => $rotulo) {
            $bloco = $grupos[$tipo];
            $quantidade = count($bloco);

            if ($quantidade === 0) {
                continue;
            }

            if ($tipo === 'I') {
                $saida = array_merge($saida, self::ida($bloco, $alunosNoPonto, $rotulo));
            } else {
                $saida = array_merge($saida, self::volta($bloco, $alunosNoPonto, $rotulo));
            }
        }

        return $saida;
    }

    /**
     * @param list<object> $bloco
     * @param array<int, int> $alunosNoPonto
     * @return list<array{sentido: string, rotulo: string, seq: int, nome: string, hora: string, embarcam: int, desembarcam: int, a_bordo: int}>
     */
    private static function ida(array $bloco, array $alunosNoPonto, string $rotulo): array
    {
        $linhas = [];
        $aBordo = 0;
        $jaSubiu = [];
        $ultima = count($bloco) - 1;

        foreach ($bloco as $indice => $parada) {
            $ponto = (int) $parada->ponto_id;
            $embarcam = 0;

            if ($indice !== $ultima && !isset($jaSubiu[$ponto])) {
                $embarcam = (int) ($alunosNoPonto[$ponto] ?? 0);
                $jaSubiu[$ponto] = true;
            }

            $aBordo += $embarcam;
            $desembarcam = $indice === $ultima ? $aBordo : 0;

            if ($indice === $ultima) {
                $aBordo = 0;
            }

            $linhas[] = self::linha($rotulo, $parada, $embarcam, $desembarcam, $aBordo);
        }

        return $linhas;
    }

    /**
     * @param list<object> $bloco
     * @param array<int, int> $alunosNoPonto
     * @return list<array{sentido: string, rotulo: string, seq: int, nome: string, hora: string, embarcam: int, desembarcam: int, a_bordo: int}>
     */
    private static function volta(array $bloco, array $alunosNoPonto, string $rotulo): array
    {
        $linhas = [];
        $aBordo = 0;
        $restante = $alunosNoPonto;
        unset($restante[(int) $bloco[0]->ponto_id]);
        $ultima = count($bloco) - 1;

        foreach ($bloco as $indice => $parada) {
            $ponto = (int) $parada->ponto_id;

            if ($indice === 0) {
                $embarcam = (int) array_sum($alunosNoPonto);
                $desembarcam = 0;
                $aBordo = $embarcam;
            } else {
                $embarcam = 0;
                $desembarcam = (int) ($restante[$ponto] ?? 0);
                unset($restante[$ponto]);
                $aBordo = max(0, $aBordo - $desembarcam);

                if ($indice === $ultima && $aBordo > 0) {
                    $desembarcam += $aBordo;
                    $aBordo = 0;
                }
            }

            $linhas[] = self::linha($rotulo, $parada, $embarcam, $desembarcam, $aBordo);
        }

        return $linhas;
    }

    /**
     * @return array{sentido: string, rotulo: string, seq: int, nome: string, hora: string, embarcam: int, desembarcam: int, a_bordo: int}
     */
    private static function linha(string $rotulo, object $parada, int $embarcam, int $desembarcam, int $aBordo): array
    {
        $hora = $parada->hora ?? null;

        return [
            'sentido' => $rotulo === 'Volta' ? 'V' : 'I',
            'rotulo' => $rotulo,
            'seq' => (int) ($parada->seq ?? 0),
            'nome' => (string) ($parada->descricao ?? ''),
            'hora' => $hora ? substr((string) $hora, 0, 5) : '—',
            'embarcam' => $embarcam,
            'desembarcam' => $desembarcam,
            'a_bordo' => $aBordo,
        ];
    }
}
