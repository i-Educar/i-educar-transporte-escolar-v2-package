<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A tela mostra um pedaço. A planilha continua com a lista inteira do filtro.
 */
final class Pagina
{
    public const TAMANHO = 25;

    public static function numero(mixed $pagina): int
    {
        $numero = (int) $pagina;

        return $numero < 1 ? 1 : $numero;
    }

    /**
     * @return array{pagina: int, paginas: int, total: int, inicio: int, tamanho: int}
     */
    public static function fatia(int $total, mixed $pagina, int $tamanho = self::TAMANHO): array
    {
        $tamanho = $tamanho < 1 ? self::TAMANHO : $tamanho;
        $paginas = max(1, (int) ceil($total / $tamanho));
        $atual = min(self::numero($pagina), $paginas);

        return [
            'pagina' => $atual,
            'paginas' => $paginas,
            'total' => max(0, $total),
            'inicio' => ($atual - 1) * $tamanho,
            'tamanho' => $tamanho,
        ];
    }
}
