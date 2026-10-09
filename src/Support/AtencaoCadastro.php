<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Marca o cadastro que a notificação também apontaria:
 * vencimento, prazo de 30 dias ou documento ainda sem data.
 */
final class AtencaoCadastro
{
    /**
     * @param list<string> $autorizacoes
     * @return list<array{tom: string, texto: string}>
     */
    public static function veiculo(object $linha, array $autorizacoes): array
    {
        $avisos = [];

        foreach ([
            'licenciamento_vencimento' => 'Licenciamento',
            'seguro_vencimento' => 'Seguro',
            'ipva_vencimento' => 'IPVA',
            'vistoria_vencimento' => 'Vistoria',
        ] as $campo => $nome) {
            $avisos = array_merge($avisos, self::prazo($linha->{$campo} ?? null, $nome));
        }

        if ($autorizacoes === []) {
            $avisos[] = ['tom' => 'alerta', 'texto' => 'Sem autorização'];
        } else {
            $vigente = null;

            foreach ($autorizacoes as $validade) {
                $dia = substr((string) $validade, 0, 10);

                if ($vigente === null || $dia > $vigente) {
                    $vigente = $dia;
                }
            }

            $avisos = array_merge($avisos, self::prazo($vigente, 'Autorização'));
        }

        return $avisos;
    }

    /**
     * @return list<array{tom: string, texto: string}>
     */
    public static function motorista(object $linha): array
    {
        $avisos = [];

        if (trim((string) ($linha->cnh ?? '')) === '') {
            $avisos[] = ['tom' => 'alerta', 'texto' => 'Sem CNH'];
        }

        return array_merge(
            $avisos,
            self::prazo($linha->vencimento_cnh ?? null, 'CNH'),
            self::prazo($linha->curso_escolar_validade ?? null, 'Curso escolar'),
        );
    }

    /**
     * @return list<array{tom: string, texto: string}>
     */
    public static function ponto(object $linha): array
    {
        if (Coordenada::dentroDoPais(isset($linha->latitude) ? (string) $linha->latitude : null, isset($linha->longitude) ? (string) $linha->longitude : null)) {
            return [];
        }

        return [['tom' => 'alerta', 'texto' => 'Sem localização']];
    }

    /**
     * @return list<array{tom: string, texto: string}>
     */
    public static function documento(?string $numero, string $nome): array
    {
        if (trim((string) $numero) !== '') {
            return [];
        }

        return [['tom' => 'alerta', 'texto' => 'Sem ' . $nome]];
    }

    /**
     * @return list<array{tom: string, texto: string}>
     */
    public static function prazo(mixed $data, string $nome): array
    {
        $texto = substr(trim((string) $data), 0, 10);

        if ($texto === '') {
            return [['tom' => 'alerta', 'texto' => $nome . ' sem data']];
        }

        $hoje = date('Y-m-d');
        $limite = date('Y-m-d', strtotime('+30 days'));

        if ($texto < $hoje) {
            return [['tom' => 'alerta', 'texto' => $nome . ' vencido']];
        }

        if ($texto <= $limite) {
            return [['tom' => 'prazo', 'texto' => $nome . ' a vencer']];
        }

        return [];
    }
}
