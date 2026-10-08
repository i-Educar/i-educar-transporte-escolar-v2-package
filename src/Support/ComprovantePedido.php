<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ComprovantePedido
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function campos(string $situacao, string $quando, string $aluno, string $escola, string $turno, string $contatoEscola, string $contatoSetor, string $link): array
    {
        return [
            ['Situacao', $situacao],
            ['Pedido em', $quando],
            ['Aluno', NomeExibicao::exibir($aluno, false)],
            ['Unidade', NomeExibicao::exibir($escola, false)],
            ['Turno', $turno],
            ['Responsavel da escola', self::texto($contatoEscola)],
            ['Setor de transporte', self::texto($contatoSetor)],
            ['Acompanhar', $link],
        ];
    }

    public static function telefone(string $gravado, string $informado): bool
    {
        $gravado = preg_replace('/\D/', '', $gravado) ?? '';
        $informado = preg_replace('/\D/', '', $informado) ?? '';

        if (strlen($gravado) < 4 || strlen($informado) < 4) {
            return false;
        }

        return substr($gravado, -4) === substr($informado, -4);
    }

    private static function texto(string $valor): string
    {
        $valor = trim($valor);

        return $valor === '' ? 'Nao informado' : $valor;
    }
}
