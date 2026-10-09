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

    private const MARGEM = 14;

    private const FOLGA = 10;

    private const CARTAO_L = 402;

    private const CARTAO_A = 278;

    private const CABECA = 46;

    private const RODAPE = 16;

    /**
     * @param list<array{papel: string, nome: string, municipio: string, linhas: list<array{0: string, 1: string}>, verso: ?list<array{0: string, 1: string}>, foto: ?array{w: int, h: int, data: string}}> $pessoas
     */
    public static function montar(array $pessoas): string
    {
        $brasao = self::brasao();
        $paginas = $pessoas === [] ? [[]] : array_chunk($pessoas, 2);
        $folhas = [];

        foreach ($paginas as $pagina) {
            $folhas[] = self::pagina($pagina, $brasao !== null);
        }

        return self::arquivo($folhas, $brasao);
    }

    /**
     * @param list<array{papel: string, nome: string, municipio: string, linhas: list<array{0: string, 1: string}>, verso: ?list<array{0: string, 1: string}>, foto: ?array{w: int, h: int, data: string}}> $pessoas
     * @return array{fluxo: string, fotos: list<array{w: int, h: int, data: string}>}
     */
    private static function pagina(array $pessoas, bool $brasao): array
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
            array_push($comandos, ...self::frente($pessoa, $xFrente, $y, $foto, $brasao));
            array_push($comandos, ...self::verso($pessoa, $xVerso, $y, $brasao));
        }

        if (count($pessoas) === 2) {
            $yCorte = self::MARGEM + self::CARTAO_A + (int) (self::FOLGA / 2);
            $comandos[] = '0.55 0.66 0.72 RG';
            $comandos[] = '[2 2] 0 d';
            $comandos[] = self::MARGEM . ' ' . $yCorte . ' m ' . (self::LARGURA - self::MARGEM) . ' ' . $yCorte . ' l S';
            $comandos[] = '[] 0 d';
        }

        $xDobra = self::MARGEM + self::CARTAO_L + (int) (self::FOLGA / 2);

        foreach ($pessoas as $indice => $pessoa) {
            $y = $bases[$indice];
            $comandos[] = '0.55 0.66 0.72 RG';
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
    private static function frente(array $pessoa, int $x, int $y, ?int $foto, bool $brasao): array
    {
        $topo = $y + self::CARTAO_A;
        $comandos = self::fundo($x, $y);
        array_push($comandos, ...self::cabecalho($x, $topo, $pessoa['municipio'], 'Transporte escolar', $pessoa['papel'], $brasao));

        $faixa = $topo - self::CABECA - 28;
        $comandos[] = '0.93 0.96 0.97 rg';
        $comandos[] = ($x + 1) . ' ' . $faixa . ' ' . (self::CARTAO_L - 2) . ' 28 re f';
        $nome = self::quebrar($pessoa['nome'], 42);
        $comandos[] = '0.08 0.18 0.24 rg';
        $comandos[] = self::texto(2, $x + 12, $faixa + (isset($nome[1]) ? 14 : 9), 12, $nome[0]);

        if (isset($nome[1])) {
            $comandos[] = self::texto(2, $x + 12, $faixa + 2, 12, $nome[1]);
        }

        $corpoBase = $y + self::RODAPE + 8;
        $corpoTopo = $faixa - 8;
        $corpoAltura = $corpoTopo - $corpoBase;
        $fotoL = 108;
        $fotoX = $x + 10;
        $comandos[] = '0.86 0.91 0.93 rg';
        $comandos[] = $fotoX . ' ' . $corpoBase . ' ' . $fotoL . ' ' . $corpoAltura . ' re f';

        if ($foto !== null) {
            $comandos[] = 'q ' . $fotoL . ' 0 0 ' . $corpoAltura . ' ' . $fotoX . ' ' . $corpoBase . ' cm /Im' . $foto . ' Do Q';
        } else {
            $comandos[] = '0.35 0.48 0.55 rg';
            $comandos[] = self::texto(2, $fotoX + 24, $corpoBase + (int) ($corpoAltura / 2) - 3, 8, 'Sem foto');
        }

        array_push($comandos, ...self::grade(
            $fotoX + $fotoL + 8,
            $corpoBase,
            self::CARTAO_L - 20 - $fotoL - 8,
            $corpoAltura,
            $pessoa['linhas'],
            1,
        ));
        array_push($comandos, ...self::rodape($x, $y, 'Carteira de identificação'));
        array_push($comandos, ...self::borda($x, $y));

        return $comandos;
    }

    /**
     * @param array{papel: string, municipio: string, verso: ?list<array{0: string, 1: string}>, qr?: ?list<list<bool>>} $pessoa
     * @return list<string>
     */
    private static function verso(array $pessoa, int $x, int $y, bool $brasao): array
    {
        $campos = $pessoa['verso'] ?? null;

        if ($campos === null) {
            return [
                '0.09 0.32 0.40 RG',
                '1.1 w',
                $x . ' ' . $y . ' ' . self::CARTAO_L . ' ' . self::CARTAO_A . ' re S',
                '0.7 w',
                ($x + 12) . ' ' . ($y + 12) . ' ' . (self::CARTAO_L - 24) . ' ' . (self::CARTAO_A - 24) . ' re S',
            ];
        }

        $qr = $pessoa['qr'] ?? null;
        $faixa = is_array($qr) && $qr !== [] ? 96 : 0;
        $topo = $y + self::CARTAO_A;
        $base = $y + self::RODAPE + 8;
        $altura = $topo - self::CABECA - 8 - $base;
        $comandos = self::fundo($x, $y);
        array_push($comandos, ...self::cabecalho($x, $topo, $pessoa['municipio'], 'Dados da rota', '', $brasao));
        array_push($comandos, ...self::grade(
            $x + 10,
            $base + $faixa,
            self::CARTAO_L - 20,
            $altura - $faixa,
            $campos,
            2,
        ));

        if ($faixa > 0) {
            array_push($comandos, ...self::qr($qr, $x + self::CARTAO_L - 10, $base));
        }

        array_push($comandos, ...self::rodape($x, $y, 'Dobre na linha tracejada'));
        array_push($comandos, ...self::borda($x, $y));

        return $comandos;
    }

    /**
     * @param list<list<bool>> $matriz
     * @return list<string>
     */
    private static function qr(array $matriz, int $direita, int $base): array
    {
        $n = count($matriz);
        $modulo = 2;
        $quiet = 4 * $modulo;
        $caixa = ($n * $modulo) + ($quiet * 2);
        $ox = $direita - $caixa;
        $oy = $base + 12;
        $comandos = [
            '1 1 1 rg',
            $ox . ' ' . $oy . ' ' . $caixa . ' ' . $caixa . ' re f',
            '0 0 0 rg',
        ];

        for ($linha = 0; $linha < $n; $linha++) {
            for ($coluna = 0; $coluna < $n; $coluna++) {
                if (!$matriz[$linha][$coluna]) {
                    continue;
                }

                $comandos[] = ($ox + $quiet + ($coluna * $modulo)) . ' ' . ($oy + $quiet + (($n - 1 - $linha) * $modulo)) . ' ' . $modulo . ' ' . $modulo . ' re f';
            }
        }

        $legenda = 'Mapa do setor';
        $tx = (int) ($ox + (($caixa - (mb_strlen($legenda) * 3.6)) / 2));
        $comandos[] = self::texto(1, $tx, $base + 2, 7, $legenda);

        return $comandos;
    }

    /**
     * @return list<string>
     */
    private static function cabecalho(int $x, int $topo, string $municipio, string $linha, string $papel, bool $brasao): array
    {
        $base = $topo - self::CABECA;
        $comandos = [
            '0.09 0.32 0.40 rg',
            ($x + 1) . ' ' . $base . ' ' . (self::CARTAO_L - 2) . ' ' . (self::CABECA - 1) . ' re f',
            '0.75 0.58 0.22 rg',
            ($x + 1) . ' ' . ($base - 2) . ' ' . (self::CARTAO_L - 2) . ' 2.5 re f',
        ];
        $textoX = $x + 12;

        if ($brasao) {
            $comandos[] = '1 1 1 rg';
            $comandos[] = ($x + 8) . ' ' . ($base + 7) . ' 32 32 re f';
            $comandos[] = 'q 28 0 0 28 ' . ($x + 10) . ' ' . ($base + 9) . ' cm /Br Do Q';
            $textoX = $x + 46;
        }
        $nome = self::quebrar($municipio, 34);
        $comandos[] = '1 1 1 rg';
        $comandos[] = self::texto(2, $textoX, $base + (isset($nome[1]) ? 24 : 20), 9, $nome[0]);

        if (isset($nome[1])) {
            $comandos[] = self::texto(2, $textoX, $base + 13, 9, $nome[1]);
            $comandos[] = '0.78 0.88 0.90 rg';
            $comandos[] = self::texto(1, $textoX, $base + 3, 7, self::cortar($linha, 180, 7));
        } else {
            $comandos[] = '0.78 0.88 0.90 rg';
            $comandos[] = self::texto(1, $textoX, $base + 8, 7, self::cortar($linha, 180, 7));
        }

        if ($papel !== '') {
            $comandos[] = '0.75 0.58 0.22 rg';
            $comandos[] = ($x + self::CARTAO_L - 92) . ' ' . ($base + 14) . ' 80 18 re f';
            $comandos[] = '0.09 0.32 0.40 rg';
            $comandos[] = self::texto(2, $x + self::CARTAO_L - 84, $base + 19, 8, self::cortar($papel, 68, 8, true));
        }

        return $comandos;
    }

    /**
     * @param list<array{0: string, 1: string}> $campos
     * @return list<string>
     */
    private static function grade(int $x, int $y, int $largura, int $altura, array $campos, int $colunas): array
    {
        $total = count($campos);

        if ($total === 0 || $altura < 18 || $largura < 40) {
            return [];
        }

        $colunas = max(1, min($colunas, $total));
        $fileiras = (int) ceil($total / $colunas);
        $folga = 4;
        $celulaL = (int) floor(($largura - (($colunas - 1) * $folga)) / $colunas);
        $pesos = [];

        foreach ($campos as $campo) {
            $pesos[] = max(1, substr_count((string) $campo[1], "\n") + 1);
        }

        $soma = array_sum($pesos);
        $livre = $altura - (($fileiras - 1) * $folga);
        $comandos = [];
        $topo = $y + $altura;

        foreach ($campos as $indice => $campo) {
            $coluna = $indice % $colunas;
            $celulaA = max(16, (int) floor($livre * $pesos[$indice] / max(1, $soma)));
            $cx = $x + ($coluna * ($celulaL + $folga));
            $cy = $topo - $celulaA;
            array_push($comandos, ...self::celula($cx, $cy, $celulaL, $celulaA, (string) $campo[0], (string) $campo[1]));

            if ($coluna === $colunas - 1 || $indice === $total - 1) {
                $topo -= $celulaA + $folga;
            }
        }

        return $comandos;
    }

    /**
     * @return list<string>
     */
    private static function celula(int $x, int $y, int $largura, int $altura, string $rotulo, string $valor): array
    {
        $valor = trim($valor) === '' ? '-' : $valor;
        $larguraTexto = max(8, (int) floor(($largura - 12) / 4.2));
        $linhas = [];

        $blocos = preg_split("/\n/", $valor) ?: ['-'];

        if (count($blocos) > 1) {
            foreach ($blocos as $bloco) {
                $bloco = trim((string) $bloco);

                if ($bloco !== '') {
                    $linhas[] = self::cortar($bloco, $largura - 12, 7);
                }
            }
        } else {
            array_push($linhas, ...self::quebrar(trim((string) ($blocos[0] ?? '')), $larguraTexto));
        }

        $comandos = [
            '0.945 0.965 0.972 rg',
            $x . ' ' . $y . ' ' . $largura . ' ' . $altura . ' re f',
            '0.82 0.88 0.91 RG',
            '0.6 w',
            $x . ' ' . $y . ' ' . $largura . ' ' . $altura . ' re S',
        ];

        if (count($linhas) > 1 || $altura >= 26) {
            $comandos[] = '0.22 0.40 0.48 rg';
            $comandos[] = self::texto(1, $x + 6, $y + $altura - 9, 6, self::cortar(mb_strtoupper($rotulo), $largura - 12, 6));
            $comandos[] = '0.08 0.14 0.18 rg';
            $topo = $y + $altura - 18;
            $visiveis = array_slice($linhas, 0, max(1, (int) floor(($topo - $y) / 8)));

            foreach ($visiveis as $indice => $linha) {
                $comandos[] = self::texto(count($linhas) > 1 ? 1 : 2, $x + 6, $topo - ($indice * 8), 7, $linha);
            }

            return $comandos;
        }

        $linhas = self::quebrar($valor, max(8, (int) floor(($largura - 86) / 4.6)));

        if ($altura < 26) {
            $comandos[] = '0.22 0.40 0.48 rg';
            $comandos[] = self::texto(1, $x + 6, $y + 5, 6, self::cortar(mb_strtoupper($rotulo), 74, 6));
            $comandos[] = '0.08 0.14 0.18 rg';
            $comandos[] = self::texto(2, $x + 82, $y + 4, 8, $linhas[0]);

            return $comandos;
        }

        return $comandos;
    }

    /**
     * @return list<string>
     */
    private static function fundo(int $x, int $y): array
    {
        return [
            '0.98 0.99 0.99 rg',
            $x . ' ' . $y . ' ' . self::CARTAO_L . ' ' . self::CARTAO_A . ' re f',
        ];
    }

    /**
     * @return list<string>
     */
    private static function rodape(int $x, int $y, string $texto): array
    {
        return [
            '0.09 0.32 0.40 rg',
            ($x + 1) . ' ' . ($y + 1) . ' ' . (self::CARTAO_L - 2) . ' ' . (self::RODAPE - 1) . ' re f',
            '1 1 1 rg',
            self::texto(1, $x + 10, $y + 5, 7, self::cortar($texto, self::CARTAO_L - 24, 7)),
        ];
    }

    /**
     * @return list<string>
     */
    private static function borda(int $x, int $y): array
    {
        return [
            '0.09 0.32 0.40 RG',
            '1.1 w',
            $x . ' ' . $y . ' ' . self::CARTAO_L . ' ' . self::CARTAO_A . ' re S',
        ];
    }

    /**
     * @param list<array{fluxo: string, fotos: list<array{w: int, h: int, data: string}>}> $folhas
     * @param ?array{w: int, h: int, data: string} $brasao
     */
    private static function arquivo(array $folhas, ?array $brasao): string
    {
        $objetos = [];
        $objetos[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[] = '';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $brasaoId = 0;

        if ($brasao !== null) {
            $objetos[] = self::imagem($brasao);
            $brasaoId = count($objetos);
        }

        $kids = [];

        foreach ($folhas as $folha) {
            $imagens = [];

            foreach ($folha['fotos'] as $foto) {
                $objetos[] = self::imagem($foto);
                $imagens[] = count($objetos) . ' 0 R';
            }

            $objetos[] = '<< /Length ' . strlen($folha['fluxo']) . " >>\nstream\n" . $folha['fluxo'] . "\nendstream";
            $conteudo = count($objetos);
            $xobjetos = [];

            if ($brasaoId !== 0) {
                $xobjetos[] = '/Br ' . $brasaoId . ' 0 R';
            }

            foreach ($imagens as $indice => $referencia) {
                $xobjetos[] = '/Im' . ($indice + 1) . ' ' . $referencia;
            }

            $recursos = '/Font << /F1 3 0 R /F2 4 0 R >>';

            if ($xobjetos !== []) {
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

    /**
     * @param array{w: int, h: int, data: string} $imagem
     */
    private static function imagem(array $imagem): string
    {
        return '<< /Type /XObject /Subtype /Image /Width ' . $imagem['w'] . ' /Height ' . $imagem['h']
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($imagem['data']) . " >>\nstream\n"
            . $imagem['data'] . "\nendstream";
    }

    /**
     * @return ?array{w: int, h: int, data: string}
     */
    private static function brasao(): ?array
    {
        if (!class_exists(IdentidadeMunicipio::class) || !function_exists('imagecreatefromstring')) {
            return null;
        }

        $arquivo = IdentidadeMunicipio::arquivo();

        if ($arquivo === null || !is_file($arquivo)) {
            return null;
        }

        $origem = @imagecreatefromstring((string) file_get_contents($arquivo));

        if ($origem === false) {
            return null;
        }

        $lado = 128;
        $largura = imagesx($origem);
        $altura = imagesy($origem);
        $destino = imagecreatetruecolor($lado, $lado);
        $branco = imagecolorallocate($destino, 255, 255, 255);
        imagefill($destino, 0, 0, $branco);
        $escala = min($lado / max(1, $largura), $lado / max(1, $altura));
        $novaLargura = (int) round($largura * $escala);
        $novaAltura = (int) round($altura * $escala);
        imagecopyresampled($destino, $origem, (int) (($lado - $novaLargura) / 2), (int) (($lado - $novaAltura) / 2), 0, 0, $novaLargura, $novaAltura, $largura, $altura);
        imagedestroy($origem);

        if (!function_exists('imagejpeg')) {
            imagedestroy($destino);

            return null;
        }

        ob_start();
        imagejpeg($destino, null, 85);
        $dados = ob_get_clean();
        imagedestroy($destino);

        return is_string($dados) && $dados !== '' ? ['w' => $lado, 'h' => $lado, 'data' => $dados] : null;
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
            return [$texto === '' ? '-' : $texto];
        }

        $corte = mb_strrpos(mb_substr($texto, 0, $largura), ' ');
        $corte = $corte === false || $corte < 8 ? $largura : $corte;
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
