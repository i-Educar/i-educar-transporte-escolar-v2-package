<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A trilha vem do gatilho do i-Educar em public.ieducar_audit.
 * Antes vazio é inclusão. Depois vazio é exclusão. Os dois preenchidos são alteração.
 */
final class AuditoriaTransporte
{
    public static function operacao(bool $tinhaAntes, bool $temDepois): string
    {
        if (!$tinhaAntes && $temDepois) {
            return 'Inclusão';
        }

        if ($tinhaAntes && !$temDepois) {
            return 'Exclusão';
        }

        return 'Alteração';
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     * @return list<string>
     */
    public static function campos(?array $antes, ?array $depois): array
    {
        $antes ??= [];
        $depois ??= [];
        $chaves = array_unique(array_merge(array_keys($antes), array_keys($depois)));
        $mudou = [];

        foreach ($chaves as $chave) {
            if (in_array($chave, ['updated_at', 'created_at'], true)) {
                continue;
            }

            $velho = self::texto($antes[$chave] ?? null);
            $novo = self::texto($depois[$chave] ?? null);

            if ($velho !== $novo) {
                $mudou[] = (string) $chave;
            }
        }

        sort($mudou);

        return $mudou;
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     */
    public static function resumo(?array $antes, ?array $depois): string
    {
        $partes = [];

        foreach (self::campos($antes, $depois) as $chave) {
            $partes[] = $chave . ': ' . self::texto($antes[$chave] ?? null) . ' -> ' . self::texto($depois[$chave] ?? null);
        }

        return implode(' | ', $partes);
    }

    private static function texto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if (is_array($valor)) {
            $json = json_encode($valor, JSON_UNESCAPED_UNICODE);

            return $json === false ? '—' : $json;
        }

        return (string) $valor;
    }
}
