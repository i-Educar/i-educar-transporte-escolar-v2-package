<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * QR Code versão 1 ou 2, correção M, modo byte. Cabe no texto TE- e o id do aluno.
 */
final class QrCodigo
{
    public static function svg(string $texto): string
    {
        $modulos = self::matriz($texto);
        $n = count($modulos);
        $celula = 4;
        $margem = 4;
        $lado = ($n + ($margem * 2)) * $celula;
        $rects = '';

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if (!$modulos[$y][$x]) {
                    continue;
                }

                $rects .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d"/>',
                    ($x + $margem) * $celula,
                    ($y + $margem) * $celula,
                    $celula,
                    $celula,
                );
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $lado . '" height="' . $lado . '" viewBox="0 0 ' . $lado . ' ' . $lado . '"><rect width="100%" height="100%" fill="#fff"/>' . $rects . '</svg>';
    }

    /**
     * @return list<list<bool>>
     */
    public static function matriz(string $texto): array
    {
        $bytes = array_values(unpack('C*', $texto));
        $versao = count($bytes) <= 14 ? 1 : 2;
        $capacidade = $versao === 1 ? 16 : 28;
        $ecc = $versao === 1 ? 10 : 16;

        if (count($bytes) > $capacidade - 2) {
            $bytes = array_slice($bytes, 0, $capacidade - 2);
        }

        $dados = self::codewords($bytes, $capacidade);
        $resto = array_merge($dados, self::reedSolomon($dados, $ecc));
        $n = $versao === 1 ? 21 : 25;
        $matriz = array_fill(0, $n, array_fill(0, $n, false));
        $reservado = array_fill(0, $n, array_fill(0, $n, false));
        self::funcoes($matriz, $reservado, $versao);
        self::dados($matriz, $reservado, $resto);
        self::mascara($matriz, $reservado, 0);
        self::formato($matriz, 0, $versao === 1 ? 0 : 0);

        return $matriz;
    }

    /**
     * @param list<int> $bytes
     * @return list<int>
     */
    private static function codewords(array $bytes, int $capacidade): array
    {
        $bits = '0100' . str_pad(decbin(count($bytes)), 8, '0', STR_PAD_LEFT);

        foreach ($bytes as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $bits .= str_repeat('0', min(4, ($capacidade * 8) - strlen($bits)));
        $sobra = strlen($bits) % 8;
        $bits .= str_repeat('0', $sobra === 0 ? 8 : 8 - $sobra);
        $pads = ['11101100', '00010001'];
        $i = 0;

        while (strlen($bits) < $capacidade * 8) {
            $bits .= $pads[$i % 2];
            $i++;
        }

        $saida = [];

        for ($p = 0; $p < $capacidade; $p++) {
            $saida[] = bindec(substr($bits, $p * 8, 8));
        }

        return $saida;
    }

    /**
     * @param list<int> $dados
     * @return list<int>
     */
    private static function reedSolomon(array $dados, int $ecc): array
    {
        $gerador = [1];

        for ($i = 0; $i < $ecc; $i++) {
            $proximo = array_fill(0, count($gerador) + 1, 0);

            foreach ($gerador as $grau => $coef) {
                $proximo[$grau] ^= $coef;
                $proximo[$grau + 1] ^= self::gfMul($coef, self::gfPow(2, $i));
            }

            $gerador = $proximo;
        }

        $resto = array_merge($dados, array_fill(0, $ecc, 0));

        foreach ($dados as $i => $byte) {
            $fator = $resto[$i];

            if ($fator === 0) {
                continue;
            }

            foreach ($gerador as $grau => $coef) {
                $resto[$i + $grau] ^= self::gfMul($coef, $fator);
            }
        }

        return array_slice($resto, -$ecc);
    }

    private static function gfMul(int $a, int $b): int
    {
        $r = 0;

        for ($i = 0; $i < 8; $i++) {
            if (($b & 1) !== 0) {
                $r ^= $a;
            }

            $alto = ($a & 0x80) !== 0;
            $a = ($a << 1) & 0xFF;

            if ($alto) {
                $a ^= 0x1D;
            }

            $b >>= 1;
        }

        return $r;
    }

    private static function gfPow(int $base, int $exp): int
    {
        $r = 1;

        for ($i = 0; $i < $exp; $i++) {
            $r = self::gfMul($r, $base);
        }

        return $r;
    }

    /**
     * @param list<list<bool>> $matriz
     * @param list<list<bool>> $reservado
     */
    private static function funcoes(array &$matriz, array &$reservado, int $versao): void
    {
        $n = count($matriz);
        $olhos = [[0, 0], [0, $n - 7], [$n - 7, 0]];

        foreach ($olhos as [$y, $x]) {
            for ($dy = -1; $dy <= 7; $dy++) {
                for ($dx = -1; $dx <= 7; $dx++) {
                    $yy = $y + $dy;
                    $xx = $x + $dx;

                    if ($yy < 0 || $xx < 0 || $yy >= $n || $xx >= $n) {
                        continue;
                    }

                    $borda = $dy === -1 || $dy === 7 || $dx === -1 || $dx === 7;
                    $anel = $dy === 0 || $dy === 6 || $dx === 0 || $dx === 6;
                    $matriz[$yy][$xx] = !$borda && ($anel || ($dy >= 2 && $dy <= 4 && $dx >= 2 && $dx <= 4));
                    $reservado[$yy][$xx] = true;
                }
            }
        }

        for ($i = 0; $i < $n; $i++) {
            if (!$reservado[6][$i]) {
                $matriz[6][$i] = $i % 2 === 0;
                $reservado[6][$i] = true;
            }

            if (!$reservado[$i][6]) {
                $matriz[$i][6] = $i % 2 === 0;
                $reservado[$i][6] = true;
            }
        }

        $matriz[$n - 8][8] = true;
        $reservado[$n - 8][8] = true;

        if ($versao === 2) {
            self::alinhamento($matriz, $reservado, 18, 18);
        }

        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $reservado[8][$i] = true;
                $reservado[$i][8] = true;
            }
        }

        for ($i = 0; $i < 8; $i++) {
            $reservado[8][$n - 1 - $i] = true;
            $reservado[$n - 1 - $i][8] = true;
        }
    }

    /**
     * @param list<list<bool>> $matriz
     * @param list<list<bool>> $reservado
     */
    private static function alinhamento(array &$matriz, array &$reservado, int $cy, int $cx): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $matriz[$cy + $dy][$cx + $dx] = max(abs($dy), abs($dx)) !== 1;
                $reservado[$cy + $dy][$cx + $dx] = true;
            }
        }
    }

    /**
     * @param list<list<bool>> $matriz
     * @param list<list<bool>> $reservado
     * @param list<int> $codewords
     */
    private static function dados(array &$matriz, array $reservado, array $codewords): void
    {
        $bits = '';

        foreach ($codewords as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $n = count($matriz);
        $iBit = 0;

        for ($right = $n - 1; $right > 0; $right -= 2) {
            $col = $right <= 6 ? $right - 1 : $right;

            for ($vertical = 0; $vertical < $n; $vertical++) {
                for ($z = 0; $z < 2; $z++) {
                    $j = $col - $z;
                    $sobe = ((($col & 2) === 0) !== ($j < 6));
                    $i = $sobe ? ($n - 1 - $vertical) : $vertical;

                    if ($reservado[$i][$j]) {
                        continue;
                    }

                    $matriz[$i][$j] = isset($bits[$iBit]) && $bits[$iBit] === '1';
                    $iBit++;
                }
            }
        }
    }

    /**
     * @param list<list<bool>> $matriz
     * @param list<list<bool>> $reservado
     */
    private static function mascara(array &$matriz, array $reservado, int $mascara): void
    {
        $n = count($matriz);

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($reservado[$y][$x]) {
                    continue;
                }

                $inverter = match ($mascara) {
                    0 => ($y + $x) % 2 === 0,
                    default => false,
                };

                if ($inverter) {
                    $matriz[$y][$x] = !$matriz[$y][$x];
                }
            }
        }
    }

    /**
     * @param list<list<bool>> $matriz
     */
    private static function formato(array &$matriz, int $mascara, int $ignorado): void
    {
        unset($ignorado);
        $bits = self::formatoBits(0, $mascara);
        $n = count($matriz);
        $posA = [0, 1, 2, 3, 4, 5, 7, 8, $n - 7, $n - 6, $n - 5, $n - 4, $n - 3, $n - 2, $n - 1];

        for ($i = 0; $i < 15; $i++) {
            $aceso = $bits[$i] === '1';
            $matriz[8][$posA[$i]] = $aceso;
            $matriz[$posA[14 - $i]][8] = $aceso;
        }
    }

    private static function formatoBits(int $ecc, int $mascara): string
    {
        $dado = ($ecc << 3) | $mascara;
        $resto = $dado << 10;

        for ($i = 14; $i >= 10; $i--) {
            if (($resto & (1 << $i)) !== 0) {
                $resto ^= 0x537 << ($i - 10);
            }
        }

        $formatado = (($dado << 10) | $resto) ^ 0x5412;

        return str_pad(decbin($formatado), 15, '0', STR_PAD_LEFT);
    }
}
