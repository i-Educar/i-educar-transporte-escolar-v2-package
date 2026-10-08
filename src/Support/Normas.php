<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Referência curta. O texto integral da norma não é copiado para o pacote.
 */
final class Normas
{
    /**
     * @return list<array{titulo: string, uso: string, url: string}>
     */
    public static function lista(): array
    {
        return [
            [
                'titulo' => 'Constituição, art. 208, e LDB',
                'uso' => 'O transporte existe para o aluno matriculado conseguir chegar à escola.',
                'url' => 'https://www.planalto.gov.br/ccivil_03/leis/l9394.htm',
            ],
            [
                'titulo' => 'Lei 10.880/2004 — PNATE',
                'uso' => 'Repasse federal automático para o transporte da educação básica.',
                'url' => 'https://www.planalto.gov.br/ccivil_03/_ato2004-2006/2004/lei/l10.880.htm',
            ],
            [
                'titulo' => 'Resolução CD/FNDE 18/2021 e 5/2024',
                'uso' => 'Despesa permitida, SETE obrigatório na operação com PNATE e prestação no SiGPC.',
                'url' => 'https://www.gov.br/fnde/pt-br/acesso-a-informacao/acoes-e-programas/programas/pnate',
            ],
            [
                'titulo' => 'CTB, arts. 136 a 139',
                'uso' => 'Autorização, identificação e equipamentos do transporte escolar.',
                'url' => 'https://www.planalto.gov.br/ccivil_03/leis/l9503compilado.htm',
            ],
            [
                'titulo' => 'Caminho da Escola',
                'uso' => 'Compra de veículo padronizado. A manutenção desses veículos entra no custo local.',
                'url' => 'https://www.gov.br/fnde/pt-br/acesso-a-informacao/acoes-e-programas/programas/caminho-da-escola',
            ],
        ];
    }
}
