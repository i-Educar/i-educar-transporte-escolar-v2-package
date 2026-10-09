<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A4 paisagem com duas pessoas por folha.
 * Cada pessoa ocupa uma faixa: frente à esquerda e verso à direita, para dobrar.
 */
final class CarteirinhaPdf
{
    private const LARGURA = 842;

    private const ALTURA = 595;

    private const MARGEM = 18;

    private const FOLGA = 16;

    private const CARTAO_L = 395;

    private const CARTAO_A = 271;

    /**
     * @param list<array{papel: string, nome: string, municipio: string, linhas: list<array{0: string, 1: string}>, verso: ?list<array{0: string, 1: string}>, foto: ?array{w: int, h: int, data: string}}> $pessoas
     */
    public static function montar(array $pessoas): string
    {
        $paginas = $pessoas === [] ? [[]] : array_chunk($pessoas, 2);
        $folhas = [];

        foreach ($paginas as $pagina) {
            $folhas[] = self::pagina($pagina);
        }

        return self::arquivo($folhas);
    }

    /**
     * @param list<array{papel: string, nome: string, municipio: string, linhas: list<array{0: string, 1: string}>, verso: ?list<array{0: string, 1: string}>, foto: ?array{w: int, h: int, data: string}}> $pessoas
     * @return array{fluxo: string, fotos: list<array{w: int, h: int, data: string}>}
     */
    private static function pagina(array $pessoas): array
    {
        $fotos = [];
        $comandos = [];
        $bases = [self::MARGEM + self::CARTAO_A + self::FOLGA, self::MARGEM];

        foreach ($pessoas as $indice => $pessoa) {
            $foto = null;

            if (is_array($pessoa['foto'] ?? null)) {
                $fotos[] = $pessoa['foto'];
                $foto = count($fotos);
            }

            $xFrente = self::MARGEM;
            $xVerso = self::MARGEM + self::CARTAO_L + self::FOLGA;
            $y = $bases[$indice];
            array_push($comandos, ...self::frente($pessoa, $xFrente, $y, $foto));
            array_push($comandos, ...self::verso($pessoa['verso'] ?? null, $xVerso, $y));
        }

        if (count($pessoas) === 2) {
            $yCorte = self::MARGEM + self::CARTAO_A + (self::FOLGA / 2);
            $comandos[] = '0.62 0.68 0.74 RG';
            $comandos[] = '[2 2] 0 d';
            $comandos[] = self::MARGEM . ' ' . $yCorte . ' m ' . (self::LARGURA - self::MARGEM) . ' ' . $yCorte . ' l S';
            $comandos[] = '[] 0 d';
        }

        $xDobra = self::MARGEM + self::CARTAO_L + (self::FOLGA / 2);

        foreach ($pessoas as $indice => $pessoa) {
            $y = $bases[$indice];
            $comandos[] = '0.62 0.68 0.74 RG';
            $comandos[] = '[2 2] 0 d';
            $comandos[] = $xDobra . ' ' . $y . ' m ' . $xDobra . ' ' . ($y + self::CARTAO_A) . ' l S';
            $comandos[] = '[] 0 d';
        }

        return ['fluxo' => implode("\n", $comandos), 'fotos' => $fotos];
    }

    /**
     * @param array{papel: string, nome: string, municipio: string, linhas: list<array{0: string, 1: string}>} $pessoa
     * @return list<string>
     */
    private static function frente(array $pessoa, int $x, int $y, ?int $foto): array
    {
        $topo = $y + self::CARTAO_A;
        $comandos = [
            '0.278 0.447 0.561 rg',
            ($x + 1) . ' ' . ($topo - 29) . ' ' . (self::CARTAO_L - 2) . ' 28 re f',
            '1 1 1 rg',
            self::texto(2, $x + 10, $topo - 18, 8, self::cortar($pessoa['municipio'], 230, 8, true)),
            self::texto(2, $x + self::CARTAO_L - 118, $topo - 18, 8, self::cortar($pessoa['papel'], 104, 8, true)),
        ];
        $fotoX = $x + 12;
        $fotoY = $topo - 40 - 96;
        $comandos[] = '0.93 0.95 0.97 rg';
        $comandos[] = $fotoX . ' ' . $fotoY . ' 72 96 re f';
        $comandos[] = '0.75 0.80 0.86 RG';
        $comandos[] = '0.6 w';
        $comandos[] = $fotoX . ' ' . $fotoY . ' 72 96 re S';

        if ($foto !== null) {
            $comandos[] = 'q 72 0 0 96 ' . $fotoX . ' ' . $fotoY . ' cm /Im' . $foto . ' Do Q';
        } else {
            $comandos[] = '0.45 0.52 0.58 rg';
            $comandos[] = self::texto(1, $fotoX + 14, $fotoY + 44, 7, 'Sem foto');
        }

        $textoX = $x + 96;
        $textoL = self::CARTAO_L - 108;
        $nome = self::quebrar($pessoa['nome'], (int) floor($textoL / 6.4));
        $comandos[] = '0.10 0.14 0.18 rg';
        $comandos[] = self::texto(2, $textoX, $topo - 48, 11, $nome[0] ?? '');

        if (isset($nome[1])) {
            $comandos[] = self::texto(2, $textoX, $topo - 62, 11, $nome[1]);
        }

        $cursor = $topo - (isset($nome[1]) ? 80 : 68);

        foreach ($pessoa['linhas'] as $campo) {
            if ($cursor - 14 < $y + 12) {
                break;
            }

            array_push($comandos, ...self::linha($textoX, $cursor - 14, $textoL, $campo[0], $campo[1]));
            $cursor -= 18;
        }

        $comandos[] = '0.75 0.80 0.86 RG';
        $comandos[] = '0.8 w';
        $comandos[] = $x . ' ' . $y . ' ' . self::CARTAO_L . ' ' . self::CARTAO_A . ' re S';

        return $comandos;
    }

    /**
     * @param ?list<array{0: string, 1: string}> $campos
     * @return list<string>
     */
    private static function verso(?array $campos, int $x, int $y): array
    {
        $comandos = [
            '0.75 0.80 0.86 RG',
            '0.8 w',
            $x . ' ' . $y . ' ' . self::CARTAO_L . ' ' . self::CARTAO_A . ' re S',
            ($x + 10) . ' ' . ($y + 10) . ' ' . (self::CARTAO_L - 20) . ' ' . (self::CARTAO_A - 20) . ' re S',
        ];

        if ($campos === null) {
            return $comandos;
        }

        $cursor = $y + self::CARTAO_A - 28;
        $coluna = 0;
        $meia = (int) floor((self::CARTAO_L - 48) / 2);

        foreach ($campos as $campo) {
            if ($cursor < $y + 22) {
                break;
            }

            $campoX = $x + 18 + ($coluna * ($meia + 8));
            array_push($comandos, ...self::linha($campoX, $cursor, $meia, $campo[0], $campo[1]));
            $coluna = $coluna === 0 ? 1 : 0;
            $cursor -= $coluna === 0 ? 22 : 0;
        }

        return $comandos;
    }

    /**
     * @return list<string>
     */
    private static function linha(int $x, int $y, int $largura, string $rotulo, string $valor): array
    {
        $valor = trim($valor) === '' ? '-' : $valor;

        return [
            '0.35 0.42 0.48 rg',
            self::texto(1, $x, $y + 9, 6, self::cortar(mb_strtoupper($rotulo), $largura, 6)),
            '0.10 0.14 0.18 rg',
            self::texto(2, $x, $y, 8, self::cortar($valor, $largura, 8, true)),
        ];
    }

    /**
     * @param list<array{fluxo: string, fotos: list<array{w: int, h: int, data: string}>}> $folhas
     */
    private static function arquivo(array $folhas): string
    {
        $objetos = [];
        $objetos[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[] = '';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];

        foreach ($folhas as $folha) {
            $imagens = [];

            foreach ($folha['fotos'] as $foto) {
                $objetos[] = '<< /Type /XObject /Subtype /Image /Width ' . $foto['w'] . ' /Height ' . $foto['h']
                    . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($foto['data']) . " >>\nstream\n"
                    . $foto['data'] . "\nendstream";
                $imagens[] = count($objetos) . ' 0 R';
            }

            $objetos[] = '<< /Length ' . strlen($folha['fluxo']) . " >>\nstream\n" . $folha['fluxo'] . "\nendstream";
            $conteudo = count($objetos);
            $recursos = '/Font << /F1 3 0 R /F2 4 0 R >>';

            if ($imagens !== []) {
                $xobjetos = [];

                foreach ($imagens as $indice => $referencia) {
                    $xobjetos[] = '/Im' . ($indice + 1) . ' ' . $referencia;
                }

                $recursos .= ' /XObject << ' . implode(' ', $xobjetos) . ' >>';
            }

            $objetos[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::LARGURA . ' ' . self::ALTURA . '] /Contents ' . $conteudo . ' 0 R /Resources << ' . $recursos . ' >> >>';
            $kids[] = count($objetos) . ' 0 R';
        }

        $objetos[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objetos as $indice => $objeto) {
            $offsets[] = strlen($pdf);
            $pdf .= ($indice + 1) . " 0 obj\n" . $objeto . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private static function texto(int $fonte, int $x, int $y, int $tamanho, string $linha): string
    {
        return 'BT /F' . $fonte . ' ' . $tamanho . ' Tf ' . $x . ' ' . $y . ' Td (' . self::escapar(self::win($linha)) . ') Tj ET';
    }

    private static function cortar(string $texto, int $largura, int $tamanho, bool $negrito = false): string
    {
        $maximo = max(1, (int) floor($largura / ($tamanho * ($negrito ? 0.56 : 0.5))));

        if (mb_strlen($texto) <= $maximo) {
            return $texto;
        }

        return rtrim(mb_substr($texto, 0, $maximo - 1)) . '.';
    }

    /**
     * @return list<string>
     */
    private static function quebrar(string $texto, int $largura): array
    {
        $texto = trim($texto);

        if (mb_strlen($texto) <= $largura) {
            return [$texto];
        }

        $corte = mb_strrpos(mb_substr($texto, 0, $largura), ' ');
        $corte = $corte === false ? $largura : $corte;
        $resto = trim(mb_substr($texto, $corte));

        if (mb_strlen($resto) > $largura) {
            $resto = rtrim(mb_substr($resto, 0, $largura - 1)) . '.';
        }

        return [trim(mb_substr($texto, 0, $corte)), $resto];
    }

    private static function win(string $texto): string
    {
        $convertido = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);

        return $convertido === false ? $texto : $convertido;
    }

    private static function escapar(string $texto): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
    }
}
