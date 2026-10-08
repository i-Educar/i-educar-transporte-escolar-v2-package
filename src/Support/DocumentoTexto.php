<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class DocumentoTexto
{
    /**
     * @param array<string, string> $dados
     * @return list<string>
     */
    public static function ordem(array $dados): array
    {
        return [
            $dados['municipio'],
            'ORDEM DE SERVICO - TRANSPORTE ESCOLAR',
            'Protocolo: ' . $dados['protocolo'],
            'Situacao: ' . $dados['situacao'],
            'Veiculo: ' . $dados['veiculo'],
            'Data prevista: ' . $dados['data'],
            'Servico a realizar: ' . $dados['descricao'],
            'Peca: ' . self::vazio($dados['peca'] ?? ''),
            'Mao de obra prevista: ' . self::vazio($dados['mao_obra'] ?? ''),
            'Valor previsto: ' . $dados['valor'],
            'Fonte: ' . $dados['fonte'],
            'Fornecedor: ' . self::vazio($dados['fornecedor'] ?? ''),
            'Solicitante: ' . self::vazio($dados['solicitante'] ?? ''),
            '',
            'Este documento descreve o servico a realizar e serve de controle da execucao.',
            'Nao substitui nota fiscal nem o extrato da conta do PNATE.',
            '',
            'Solicitante: ______________________________',
            'Autorizacao da rede: ______________________',
            'Execucao do servico: ______________________',
        ];
    }

    /**
     * @param array<string, string> $dados
     * @return list<string>
     */
    public static function bilhete(array $dados): array
    {
        return [
            $dados['municipio'],
            'BILHETE DE ABASTECIMENTO - TRANSPORTE ESCOLAR',
            'Protocolo: ' . $dados['protocolo'],
            'Situacao: ' . $dados['situacao'],
            'Veiculo: ' . $dados['veiculo'],
            'Data: ' . $dados['data'],
            'Litros autorizados: ' . $dados['litros_autorizados'],
            'Motorista: ' . self::vazio($dados['motorista'] ?? ''),
            'Posto: ' . self::vazio($dados['posto'] ?? ''),
            'Fonte: ' . $dados['fonte'],
            'Solicitante: ' . self::vazio($dados['solicitante'] ?? ''),
            '',
            'Via do posto. Conferir o protocolo antes de abastecer.',
            '',
            '------------------------------------------------',
            'CANHOTO DE CONTROLE - DESTAQUE E DEVOLVA',
            'Protocolo: ' . $dados['protocolo'],
            'Veiculo: ' . $dados['veiculo'],
            'Litros abastecidos: ____________________',
            'Valor pago: ____________________________',
            'Quilometragem: _________________________',
            'Posto / frentista: _____________________',
            'Motorista: _____________________________',
            'Data do abastecimento: _________________',
        ];
    }

    private static function vazio(string $valor): string
    {
        $valor = trim($valor);

        return $valor === '' ? '-' : $valor;
    }
}
