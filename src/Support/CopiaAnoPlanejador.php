<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A cópia casa o itinerário pelo id da rota de origem.
 * A posição da lista não define o destino.
 */
final class CopiaAnoPlanejador
{
    /**
     * @param list<array{id: int, descricao: string}> $rotas
     * @param list<array{rota_id: int, seq: int, ponto_id: int, veiculo_id: ?int, hora: ?string, tipo: string}> $itinerarios
     * @param list<array{aluno_id: int, rota_id: int, ponto_id: ?int, turno: ?string, matricula_ativa: bool}> $alunos
     * @param list<string> $descricoesNoDestino
     * @return array{rotas: list<array<string, mixed>>, ignoradas: list<string>, alunos_recusados: list<int>}
     */
    public static function planejar(array $rotas, array $itinerarios, array $alunos, array $descricoesNoDestino): array
    {
        $ocupadas = array_fill_keys($descricoesNoDestino, true);
        $porRota = [];

        foreach ($itinerarios as $item) {
            $porRota[(int) $item['rota_id']][] = $item;
        }

        $alunosPorRota = [];

        foreach ($alunos as $aluno) {
            $alunosPorRota[(int) $aluno['rota_id']][] = $aluno;
        }

        $planos = [];
        $ignoradas = [];
        $recusados = [];

        foreach ($rotas as $rota) {
            $id = (int) $rota['id'];
            $descricao = (string) $rota['descricao'];

            if (isset($ocupadas[$descricao])) {
                $ignoradas[] = $descricao;

                continue;
            }

            $ocupadas[$descricao] = true;
            $aceitos = [];

            foreach ($alunosPorRota[$id] ?? [] as $aluno) {
                if (!$aluno['matricula_ativa']) {
                    $recusados[] = (int) $aluno['aluno_id'];

                    continue;
                }

                $aceitos[] = $aluno;
            }

            $planos[] = [
                'origem_id' => $id,
                'descricao' => $descricao,
                'itinerarios' => $porRota[$id] ?? [],
                'alunos' => $aceitos,
            ];
        }

        return [
            'rotas' => $planos,
            'ignoradas' => $ignoradas,
            'alunos_recusados' => $recusados,
        ];
    }
}
