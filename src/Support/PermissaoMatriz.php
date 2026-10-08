<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Nivel do tipo de usuário no i-Educar 2.12:
 * 1 poli-institucional (vê o menu inteiro pelo núcleo),
 * 2 institucional (rede),
 * 4 escola,
 * 8 biblioteca (sem transporte).
 */
final class PermissaoMatriz
{
    /**
     * @return array<int, array{visualiza: bool, cadastra: bool, exclui: bool}>
     */
    public static function paraNivel(int $nivel): array
    {
        if (in_array($nivel, [1, 2], true)) {
            $rede = self::todos(true, true, true);
            $rede[Processos::CACS] = ['visualiza' => true, 'cadastra' => false, 'exclui' => false];
            $rede[Processos::AUDITORIA] = ['visualiza' => true, 'cadastra' => false, 'exclui' => false];
            $rede[Processos::SOLICITACOES] = ['visualiza' => true, 'cadastra' => true, 'exclui' => false];

            return $rede;
        }

        if ($nivel === 4) {
            $ver = self::todos(true, false, false);
            $ver[Processos::ALUNOS] = ['visualiza' => true, 'cadastra' => true, 'exclui' => true];
            $ver[Processos::VIAGENS] = ['visualiza' => true, 'cadastra' => true, 'exclui' => false];
            $ver[Processos::OFFLINE] = ['visualiza' => true, 'cadastra' => true, 'exclui' => false];
            $ver[Processos::AVISOS] = ['visualiza' => true, 'cadastra' => false, 'exclui' => false];
            $ver[Processos::SOLICITACOES] = ['visualiza' => true, 'cadastra' => true, 'exclui' => false];
            $ver[Processos::COPIA] = ['visualiza' => false, 'cadastra' => false, 'exclui' => false];
            $ver[Processos::SETE] = ['visualiza' => false, 'cadastra' => false, 'exclui' => false];
            $ver[Processos::GASTOS] = ['visualiza' => false, 'cadastra' => false, 'exclui' => false];
            $ver[Processos::CACS] = ['visualiza' => false, 'cadastra' => false, 'exclui' => false];
            $ver[Processos::AUDITORIA] = ['visualiza' => false, 'cadastra' => false, 'exclui' => false];

            return $ver;
        }

        return self::todos(false, false, false);
    }

    /**
     * @return array<int, array{visualiza: bool, cadastra: bool, exclui: bool}>
     */
    private static function todos(bool $ver, bool $gravar, bool $excluir): array
    {
        $linha = ['visualiza' => $ver, 'cadastra' => $gravar, 'exclui' => $excluir];
        $matriz = [];

        foreach (array_keys(Processos::titulos()) as $processo) {
            $matriz[$processo] = $linha;
        }

        return $matriz;
    }
}
