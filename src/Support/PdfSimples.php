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
        $y = $faixa - 34;
        $limite = $rodape + 16;

        foreach ($doc['campos'] as $campo) {
            foreach (self::quebrar($campo[0] . ': ' . $campo[1], 96) as $linha) {
                if ($y < $limite) {
                    break 2;
                }

                $comandos[] = self::texto(40, $y, 8, $linha);
                $y -= 11;
            }
        }

        if ($doc['observacao'] !== '' && $y >= $limite + 11) {
            $comandos[] = self::texto(40, $y, 7, self::quebrar($doc['observacao'], 110)[0]);
            $y -= 12;
        }

        foreach ($doc['assinaturas'] as $assinatura) {
            if ($y < $limite) {
                break;
            }

            $comandos[] = self::texto(40, $y, 8, $assinatura . ': ____________________');
            $y -= 14;
        }

        $comandos[] = self::texto(40, $rodape, 7, 'Transporte escolar  |  i-Educar  |  ' . $via . '  |  ' . $papel);

        return $comandos;
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
        $comandos[] = self::texto($x, $base + 14, 8, $doc['municipio']);
        $comandos[] = self::texto($x, $base + 4, 7, $doc['titulo']);
        $comandos[] = self::texto(520, $base + 8, 8, $via);
        $comandos[] = '0.14 0.20 0.24 rg';
        $comandos[] = self::texto(36, $base - 12, 8, 'Protocolo ' . $doc['protocolo']);

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
        $imagem = 0;

        if ($logo !== null) {
            $objetos[] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['w'] . ' /Height ' . $logo['h']
                . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 ' . ($logo['filtro'] !== '' ? '/Filter ' . $logo['filtro'] . ' ' : '')
                . '/Length ' . strlen($logo['data']) . " >>\nstream\n"
                . $logo['data'] . "\nendstream";
            $imagem = 4;
        }

        $kids = [];

        foreach ($fluxos as $fluxo) {
            $objetos[] = '<< /Length ' . strlen($fluxo) . " >>\nstream\n" . $fluxo . "\nendstream";
            $conteudo = count($objetos);
            $recursos = '/Font << /F1 3 0 R >>';

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
        return 'BT /F1 ' . $tamanho . ' Tf ' . $x . ' ' . $y . ' Td (' . self::escapar(self::win($linha)) . ') Tj ET';
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
