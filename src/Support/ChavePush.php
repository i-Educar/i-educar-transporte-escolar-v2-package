<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class ChavePush
{
    /**
     * @return array{publica: string, privada: string}
     */
    public static function par(): array
    {
        $chave = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        openssl_pkey_export($chave, $pem);
        $detalhe = openssl_pkey_get_details($chave);
        $x = str_pad($detalhe['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($detalhe['ec']['y'], 32, "\0", STR_PAD_LEFT);

        return [
            'publica' => self::base64(chr(4) . $x . $y),
            'privada' => $pem,
        ];
    }

    public static function autorizacao(string $pem, string $endpoint, string $publica): string
    {
        $origem = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $cabeca = self::base64(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_UNESCAPED_SLASHES));
        $corpo = self::base64(json_encode([
            'aud' => $origem,
            'exp' => time() + 12 * 3600,
            'sub' => 'mailto:transporte@localhost',
        ], JSON_UNESCAPED_SLASHES));
        $dados = $cabeca . '.' . $corpo;
        openssl_sign($dados, $assinatura, $pem, OPENSSL_ALGO_SHA256);

        return 'vapid t=' . $dados . '.' . self::base64(self::bruta($assinatura)) . ', k=' . $publica;
    }

    public static function base64(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }

    private static function bruta(string $der): string
    {
        $posicao = 0;
        $posicao++;
        $tamanho = ord($der[$posicao++]);

        if ($tamanho & 0x80) {
            $posicao += $tamanho & 0x7f;
        }

        $r = ltrim(self::ler($der, $posicao), "\0");
        $s = ltrim(self::ler($der, $posicao), "\0");

        return str_pad($r, 32, "\0", STR_PAD_LEFT) . str_pad($s, 32, "\0", STR_PAD_LEFT);
    }

    private static function ler(string $der, int &$posicao): string
    {
        $posicao++;
        $tamanho = ord($der[$posicao++]);

        if ($tamanho & 0x80) {
            $bytes = $tamanho & 0x7f;
            $tamanho = 0;

            for ($i = 0; $i < $bytes; $i++) {
                $tamanho = ($tamanho << 8) | ord($der[$posicao++]);
            }
        }

        $valor = substr($der, $posicao, $tamanho);
        $posicao += $tamanho;

        return $valor;
    }
}
