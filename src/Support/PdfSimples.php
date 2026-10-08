<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * Folha A4 com faixa de identificação, logomarca e uma ou duas vias.
 * O texto fica em WinAnsi para o leitor e a impressora.
 */
final class PdfSimples
{
    /**
     * @param list<string> $linhas
     */
    public static function montar(array $linhas): string
    {
        return self::arquivo([self::fluxoTexto($linhas, 40, 800, 52)], null);
    }

    /**
     * @param array{
     *   municipio: string,
     *   titulo: string,
     *   protocolo: string,
     *   subtitulo: string,
     *   linhas: list<array{cpf: string, presente: bool}>,
     *   logo: ?string
     * } $doc
     */
    public static function lista(array $doc): string
    {
        $logo = self::logo($doc['logo'] ?? null);
        $linhas = array_values($doc['linhas']);
        $paginas = $linhas === [] ? [[]] : array_chunk($linhas, 32);
        $fluxos = [];

        foreach ($paginas as $indice => $pagina) {
            $fluxos[] = implode("\n", self::paginaLista($doc, $pagina, $indice + 1, count($paginas), $logo !== null));
        }

        return self::arquivo($fluxos, $logo);
    }

    /**
     * @param array{
     *   municipio: string,
     *   titulo: string,
     *   protocolo: string,
     *   campos: list<array{0: string, 1: string}>,
     *   assinaturas: list<string>,
     *   observacao: string,
     *   logo: ?string
     * } $doc
     */
    public static function impresso(array $doc, int $vias = 2, bool $meia = true): string
    {
        $logo = self::logo($doc['logo'] ?? null);

        return self::arquivo([self::duasVias($doc, $logo !== null)], $logo);
    }

    /**
     * @param array{municipio: string, titulo: string, protocolo: string, campos: list<array{0: string, 1: string}>, assinaturas: list<string>, observacao: string} $doc
     */
    /**
     * @param array{municipio: string, titulo: string, protocolo: string, campos: list<array{0: string, 1: string}>, assinaturas: list<string>, observacao: string} $doc
     */
    private static function duasVias(array $doc, bool $logo): string
    {
        $comandos = array_merge(
            self::metade($doc, $logo, 804, 'Via 1', 'Arquivo', 452),
            self::tesoura(421),
            self::metade($doc, $logo, 392, 'Via 2', 'Canhoto', 28),
        );

        return implode("\n", $comandos);
    }

    /**
     * @param array{municipio: string, titulo: string, protocolo: string, campos: list<array{0: string, 1: string}>, assinaturas: list<string>, observacao: string} $doc
     * @return list<string>
     */
    private static function metade(array $doc, bool $logo, int $faixa, string $via, string $papel, int $rodape): array
    {
        $comandos = self::cabecalho($doc, $logo, $faixa, $via);
        $comandos[] = '0.75 0.78 0.82 RG';
        $comandos[] = '0.6 w';
        $comandos[] = '28 ' . ($rodape - 8) . ' 539 ' . ($faixa - $rodape + 34) . ' re S';
        $y = $faixa - 22;
        $qr = $doc['qr'] ?? null;
        $limite = $rodape + (is_array($qr) ? 86 : 36);
        $coluna = 0;

        foreach ($doc['campos'] as $campo) {
            $largo = mb_strlen((string) $campo[1]) > 42;
            $largura = $largo || $coluna === 1 ? 519 : 254;
            $x = $coluna === 0 ? 36 : 301;

            if ($largo && $coluna === 1) {
                $y -= 32;
                $coluna = 0;
                $x = 36;
                $largura = 519;
            }

            if ($y - 28 < $limite) {
                break;
            }

            array_push($comandos, ...self::caixa($x, $y - 28, $largura, 28, (string) $campo[0], (string) $campo[1]));
            $coluna = $largo || $coluna === 1 ? 0 : 1;
            $y -= $coluna === 0 ? 32 : 0;
        }

        if ($coluna === 1) {
            $y -= 32;
        }

        if ($doc['observacao'] !== '' && $y - 22 >= $limite) {
            array_push($comandos, ...self::caixa(36, $y - 22, 519, 22, 'Observacao', $doc['observacao']));
            $y -= 28;
        }

        $xAssinatura = 36;

        foreach ($doc['assinaturas'] as $assinatura) {
            if ($y < $limite) {
                break;
            }

            $comandos[] = '0.45 0.50 0.55 RG';
            $comandos[] = $xAssinatura . ' ' . ($y - 16) . ' m ' . ($xAssinatura + 150) . ' ' . ($y - 16) . ' l S';
            $comandos[] = '0.28 0.34 0.40 rg';
            $comandos[] = self::texto($xAssinatura, $y - 26, 7, $assinatura);
            $xAssinatura += 175;

            if ($xAssinatura > 430) {
                $xAssinatura = 36;
                $y -= 34;
            }
        }

        $comandos[] = '0.28 0.34 0.40 rg';
        $comandos[] = self::texto(36, $rodape, 7, 'Transporte escolar  |  i-Educar  |  ' . $via . '  |  ' . $papel . '  |  Documento de controle, sem valor fiscal');

        if (is_array($qr) && $qr !== []) {
            array_push($comandos, ...self::qr($qr, 470, $rodape + 12, 2));
        }

        return $comandos;
    }

    /**
     * @param list<list<bool>> $matriz
     * @return list<string>
     */
    private static function qr(array $matriz, int $x, int $y, int $modulo): array
    {
        $comandos = ['0 0 0 rg'];
        $n = count($matriz);

        for ($linha = 0; $linha < $n; $linha++) {
            for ($coluna = 0; $coluna < $n; $coluna++) {
                if (!$matriz[$linha][$coluna]) {
                    continue;
                }

                $comandos[] = ($x + ($coluna * $modulo)) . ' ' . ($y + (($n - 1 - $linha) * $modulo)) . ' ' . $modulo . ' ' . $modulo . ' re f';
            }
        }

        return $comandos;
    }

    /**
     * @param array{municipio: string, titulo: string, protocolo: string, subtitulo: string} $doc
     * @param list<array{cpf: string, presente: bool}> $linhas
     * @return list<string>
     */
    private static function paginaLista(array $doc, array $linhas, int $pagina, int $total, bool $logo): array
    {
        $comandos = self::cabecalho($doc, $logo, 800, $pagina . '/' . $total);
        $comandos[] = '0.28 0.34 0.40 rg';
        $comandos[] = self::texto(36, 772, 9, $doc['subtitulo']);
        $comandos[] = '0.91 0.94 0.96 rg';
        $comandos[] = '28 748 539 16 re f';
        $comandos[] = '0.22 0.33 0.42 rg';
        $comandos[] = self::textoFonte(2, 36, 752, 8, 'No');
        $comandos[] = self::textoFonte(2, 70, 752, 8, 'CPF');
        $comandos[] = self::textoFonte(2, 230, 752, 8, 'Status');
        $y = 728;

        if ($linhas === []) {
            $comandos[] = '0.36 0.42 0.48 rg';
            $comandos[] = self::texto(36, $y, 9, 'Nenhum aluno vinculado a esta rota.');
        }

        foreach ($linhas as $indice => $linha) {
            if ($indice % 2 === 0) {
                $comandos[] = '0.97 0.98 0.99 rg';
                $comandos[] = '28 ' . ($y - 4) . ' 539 16 re f';
            }

            $comandos[] = '0.14 0.20 0.24 rg';
            $comandos[] = self::texto(36, $y, 9, (string) (($pagina - 1) * 32 + $indice + 1));
            $comandos[] = self::textoFonte(2, 70, $y, 9, (string) $linha['cpf']);
            array_push($comandos, ...self::status($linha['presente'], 230, $y - 2));
            $y -= 20;
        }

        $comandos[] = '0.75 0.78 0.82 RG';
        $comandos[] = '0.6 w';
        $comandos[] = '28 36 m 567 36 l S';
        $comandos[] = '0.36 0.42 0.48 rg';
        $comandos[] = self::texto(36, 22, 7, 'Transporte escolar  |  i-Educar  |  Frequencia da viagem  |  CPF abreviado  |  Documento de controle, sem valor fiscal');

        return $comandos;
    }

    /**
     * @return list<string>
     */
    private static function status(bool $presente, int $x, int $y): array
    {
        if ($presente) {
            return [
                '0.09 0.42 0.30 rg',
                $x . ' ' . $y . ' 9 9 re f',
                '1 1 1 RG',
                '1.3 w',
                ($x + 2) . ' ' . ($y + 4) . ' m ' . ($x + 4) . ' ' . ($y + 2) . ' l ' . ($x + 7) . ' ' . ($y + 7) . ' l S',
                '0.09 0.42 0.30 rg',
                self::textoFonte(2, $x + 14, $y + 1, 9, 'Frequencia'),
            ];
        }

        return [
            '0.61 0.17 0.17 rg',
            $x . ' ' . $y . ' 9 9 re f',
            '1 1 1 RG',
            '1.3 w',
            ($x + 2) . ' ' . ($y + 2) . ' m ' . ($x + 7) . ' ' . ($y + 7) . ' l S',
            ($x + 7) . ' ' . ($y + 2) . ' m ' . ($x + 2) . ' ' . ($y + 7) . ' l S',
            '0.61 0.17 0.17 rg',
            self::textoFonte(2, $x + 14, $y + 1, 9, 'Ausencia'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function tesoura(int $y): array
    {
        return [
            '0.28 0.45 0.56 RG',
            '1.1 w',
            '36 ' . ($y - 7) . ' m 54 ' . ($y + 7) . ' l S',
            '36 ' . ($y + 7) . ' m 54 ' . ($y - 7) . ' l S',
            '44 ' . ($y - 11) . ' 3.2 0 360 arc S',
            '44 ' . ($y + 11) . ' 3.2 0 360 arc S',
            '[3 2] 0 d',
            '78 ' . $y . ' m 559 ' . $y . ' l S',
            '[] 0 d',
            '0.6 w',
            '0.14 0.20 0.24 rg',
            self::texto(64, $y + 6, 7, 'Destaque'),
        ];
    }

    /**
     * @param array{municipio: string, titulo: string, protocolo: string} $doc
     * @return list<string>
     */
    private static function cabecalho(array $doc, bool $logo, int $base, string $via): array
    {
        $x = $logo ? 64 : 36;
        $comandos = [
            '0.278 0.447 0.561 rg',
            '28 ' . $base . ' 539 26 re f',
        ];

        if ($logo) {
            $comandos[] = 'q 20 0 0 20 34 ' . ($base + 3) . ' cm /Im1 Do Q';
        }

        $comandos[] = '1 1 1 rg';
        $comandos[] = self::textoFonte(2, $x, $base + 14, 8, $doc['municipio']);
        $comandos[] = self::texto($x, $base + 4, 7, $doc['titulo']);
        $comandos[] = self::textoFonte(2, 500, $base + 8, 8, $via);
        $comandos[] = '0.96 0.97 0.98 rg';
        $comandos[] = '430 ' . ($base - 20) . ' 137 16 re f';
        $comandos[] = '0.75 0.78 0.82 RG';
        $comandos[] = '430 ' . ($base - 20) . ' 137 16 re S';
        $comandos[] = '0.14 0.20 0.24 rg';
        $comandos[] = self::texto(434, $base - 16, 6, 'No');
        $comandos[] = self::textoFonte(2, 448, $base - 16, 8, $doc['protocolo']);

        return $comandos;
    }

    /**
     * @param list<string> $fluxos
     * @param ?array{w: int, h: int, data: string, filtro: string} $logo
     */
    private static function arquivo(array $fluxos, ?array $logo): string
    {
        $objetos = [];
        $objetos[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[] = '';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $imagem = 0;

        if ($logo !== null) {
            $objetos[] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['w'] . ' /Height ' . $logo['h']
                . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 ' . ($logo['filtro'] !== '' ? '/Filter ' . $logo['filtro'] . ' ' : '')
                . '/Length ' . strlen($logo['data']) . " >>\nstream\n"
                . $logo['data'] . "\nendstream";
            $imagem = count($objetos);
        }

        $kids = [];

        foreach ($fluxos as $fluxo) {
            $objetos[] = '<< /Length ' . strlen($fluxo) . " >>\nstream\n" . $fluxo . "\nendstream";
            $conteudo = count($objetos);
            $recursos = '/Font << /F1 3 0 R /F2 4 0 R >>';

            if ($imagem !== 0) {
                $recursos .= ' /XObject << /Im1 ' . $imagem . ' 0 R >>';
            }

            $objetos[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents ' . $conteudo . ' 0 R /Resources << ' . $recursos . ' >> >>';
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
        $pdf .= "xref\n0 " . (count($objetos) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /**
     * @param list<string> $linhas
     */
    private static function fluxoTexto(array $linhas, int $x, int $y, int $limite): string
    {
        $comandos = ['BT', '/F1 11 Tf', $x . ' ' . $y . ' Td', '14 TL'];

        foreach (array_slice($linhas, 0, $limite) as $linha) {
            $comandos[] = '(' . self::escapar(self::win($linha)) . ') Tj';
            $comandos[] = 'T*';
        }

        $comandos[] = 'ET';

        return implode("\n", $comandos);
    }

    private static function texto(int $x, int $y, int $tamanho, string $linha): string
    {
        return self::textoFonte(1, $x, $y, $tamanho, $linha);
    }

    private static function textoFonte(int $fonte, int $x, int $y, int $tamanho, string $linha): string
    {
        return 'BT /F' . $fonte . ' ' . $tamanho . ' Tf ' . $x . ' ' . $y . ' Td (' . self::escapar(self::win($linha)) . ') Tj ET';
    }

    /**
     * @return list<string>
     */
    private static function caixa(int $x, int $y, int $largura, int $altura, string $rotulo, string $valor): array
    {
        $valor = trim($valor) === '' ? '-' : $valor;

        return [
            '0.93 0.95 0.96 rg',
            $x . ' ' . ($y + $altura - 10) . ' ' . $largura . ' 10 re f',
            '0.75 0.78 0.82 RG',
            '0.4 w',
            $x . ' ' . $y . ' ' . $largura . ' ' . $altura . ' re S',
            '0.35 0.42 0.48 rg',
            self::texto($x + 4, $y + $altura - 8, 6, mb_strtoupper($rotulo)),
            '0.10 0.14 0.18 rg',
            self::textoFonte(2, $x + 4, $y + 4, 8, mb_substr($valor, 0, $largura > 300 ? 90 : 42)),
        ];
    }

    /**
     * @return list<string>
     */
    private static function quebrar(string $texto, int $largura): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return ['-'];
        }

        $linhas = [];

        while (mb_strlen($texto) > $largura) {
            $corte = mb_strrpos(mb_substr($texto, 0, $largura), ' ');
            $corte = $corte === false ? $largura : $corte;
            $linhas[] = trim(mb_substr($texto, 0, $corte));
            $texto = trim(mb_substr($texto, $corte));
        }

        $linhas[] = $texto;

        return $linhas;
    }

    /**
     * @return ?array{w: int, h: int, data: string}
     */
    private static function logo(?string $arquivo): ?array
    {
        if ($arquivo === null || !is_file($arquivo) || !function_exists('imagecreatefromstring')) {
            return null;
        }

        $origem = @imagecreatefromstring((string) file_get_contents($arquivo));

        if ($origem === false) {
            return null;
        }

        $largura = imagesx($origem);
        $altura = imagesy($origem);
        $destino = imagecreatetruecolor(72, 72);
        $branco = imagecolorallocate($destino, 255, 255, 255);
        imagefill($destino, 0, 0, $branco);
        imagecopyresampled($destino, $origem, 0, 0, 0, 0, 72, 72, $largura, $altura);

        if (function_exists('imagejpeg')) {
            ob_start();
            imagejpeg($destino, null, 80);
            $dados = ob_get_clean();
            imagedestroy($origem);
            imagedestroy($destino);

            return is_string($dados) && $dados !== '' ? ['w' => 72, 'h' => 72, 'data' => $dados, 'filtro' => '/DCTDecode'] : null;
        }

        $dados = '';

        for ($linha = 0; $linha < 72; $linha++) {
            for ($coluna = 0; $coluna < 72; $coluna++) {
                $cor = imagecolorat($destino, $coluna, $linha);
                $dados .= chr(($cor >> 16) & 255) . chr(($cor >> 8) & 255) . chr($cor & 255);
            }
        }

        imagedestroy($origem);
        imagedestroy($destino);

        return ['w' => 72, 'h' => 72, 'data' => $dados, 'filtro' => ''];
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
