<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Compara o pedido aceito com a matrícula e com a rota que o setor pretende usar.
 */
final class ConferenciaPedido
{
    /**
     * @param list<string> $rotasAtuais
     * @return list<string>
     */
    public static function avisos(object $pedido, object $matricula, ?object $rota, string $turno, array $rotasAtuais): array
    {
        $avisos = [];
        $nomePedido = self::normalizar((string) $pedido->nome_aluno);
        $nomeMatricula = self::normalizar((string) $matricula->nome);

        if ($nomePedido !== '' && $nomeMatricula !== '' && $nomePedido !== $nomeMatricula) {
            similar_text($nomePedido, $nomeMatricula, $parecido);
            $avisos[] = $parecido >= 70
                ? 'O nome informado no pedido não é idêntico ao da matrícula.'
                : 'O nome do pedido é diferente do nome da matrícula.';
        }

        $nascimentoPedido = substr((string) $pedido->nascimento, 0, 10);
        $nascimentoMatricula = substr((string) ($matricula->data_nasc ?? ''), 0, 10);

        if ($nascimentoMatricula !== '' && $nascimentoPedido !== '' && $nascimentoPedido !== $nascimentoMatricula) {
            $avisos[] = 'A data de nascimento do pedido é diferente da matrícula.';
        }

        if ((int) ($pedido->ref_cod_escola ?? 0) > 0 && (int) $pedido->ref_cod_escola !== (int) $matricula->cod_escola) {
            $avisos[] = 'A escola do pedido não é a escola da matrícula.';
        }

        if ((int) ($pedido->ref_cod_serie ?? 0) > 0 && (int) $pedido->ref_cod_serie !== (int) ($matricula->cod_serie ?? 0)) {
            $avisos[] = 'A série informada no pedido não é a série da matrícula.';
        }

        if ((int) ($pedido->ref_cod_turma ?? 0) > 0 && (int) $pedido->ref_cod_turma !== (int) ($matricula->cod_turma ?? 0)) {
            $avisos[] = 'A turma informada no pedido não é a turma da matrícula.';
        }

        if ($rota !== null && (int) ($pedido->rota_id ?? 0) > 0 && (int) $pedido->rota_id !== (int) $rota->id) {
            $avisos[] = 'A rota escolhida é outra que a vaga pedida pela família.';
        }

        if ($turno !== '' && (string) $pedido->turno !== '' && (string) $pedido->turno !== $turno) {
            $avisos[] = 'O turno escolhido é outro que o do pedido.';
        }

        if ($rota !== null && (string) ($rota->periodo ?? '') !== '' && (string) $rota->periodo !== $turno && $turno !== '') {
            $avisos[] = 'O turno escolhido não é o período da rota.';
        }

        $turnoMatricula = Codigos::periodoTurma(isset($matricula->turma_turno_id) ? (int) $matricula->turma_turno_id : null);

        if ($turnoMatricula !== null && $turno !== '' && $turnoMatricula !== $turno) {
            $avisos[] = 'A matrícula está no turno ' . (Codigos::PERIODO[$turnoMatricula] ?? $turnoMatricula) . ' e a rota é ' . (Codigos::PERIODO[$turno] ?? $turno) . '.';
        }

        $escolas = $rota->escolas ?? [];

        if ($rota !== null && $escolas !== [] && !in_array((int) $matricula->cod_escola, array_map('intval', $escolas), true)) {
            $avisos[] = 'Esta rota não atende a escola da matrícula.';
        }

        if ($rotasAtuais !== []) {
            $avisos[] = 'Este aluno já está em ' . implode(', ', $rotasAtuais) . '.';
        }

        return $avisos;
    }

    private static function normalizar(string $texto): string
    {
        $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = strtolower($convertido === false ? $texto : $convertido);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? '';

        return trim($texto);
    }
}
