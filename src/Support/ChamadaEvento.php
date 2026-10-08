<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * O aplicativo futuro manda o mesmo evento online ou depois, quando a rede volta.
 * O cartão RFID aponta o aluno. Motorista e monitor apontam o aluno direto.
 */
final class ChamadaEvento
{
    /**
     * @param array<string, mixed> $evento
     * @param array<string, int> $cartoes
     * @return array{ok: bool, erro: ?string, aluno_id: ?int, tipo: ?string, uuid: ?string}
     */
    public static function interpretar(array $evento, array $cartoes): array
    {
        $uuid = trim((string) ($evento['uuid'] ?? ''));
        $tipo = (string) ($evento['tipo'] ?? '');
        $origem = (string) ($evento['origem'] ?? '');

        if ($uuid === '' || !in_array($tipo, ['embarque', 'falta', 'desembarque'], true)) {
            return self::recusa('O evento precisa de identificador e de presença, falta ou desembarque.');
        }

        if (!in_array($origem, ['motorista', 'monitor', 'rfid'], true)) {
            return self::recusa('A origem precisa ser motorista, monitor ou rfid.');
        }

        $aluno = (int) ($evento['aluno_id'] ?? 0);
        $cartao = trim((string) ($evento['cartao'] ?? ''));

        if ($aluno < 1 && $cartao !== '') {
            $aluno = (int) ($cartoes[$cartao] ?? 0);

            if ($aluno < 1) {
                return self::recusa('Cartão não cadastrado.');
            }
        }

        if ($aluno < 1 || (int) ($evento['viagem_id'] ?? 0) < 1) {
            return self::recusa('Informe a viagem e o aluno ou o cartão.');
        }

        return ['ok' => true, 'erro' => null, 'aluno_id' => $aluno, 'tipo' => $tipo, 'uuid' => $uuid];
    }

    public static function viagemDoDispositivo(?int $motoristaDispositivo, ?int $monitorDispositivo, ?int $motoristaViagem, ?int $monitorViagem): bool
    {
        if ($motoristaDispositivo === null && $monitorDispositivo === null) {
            return false;
        }

        if ($motoristaDispositivo !== null && $motoristaDispositivo === $motoristaViagem) {
            return true;
        }

        return $monitorDispositivo !== null && $monitorDispositivo === $monitorViagem;
    }

    /**
     * @return array{ok: bool, erro: string, aluno_id: null, tipo: null, uuid: null}
     */
    private static function recusa(string $erro): array
    {
        return ['ok' => false, 'erro' => $erro, 'aluno_id' => null, 'tipo' => null, 'uuid' => null];
    }
}
