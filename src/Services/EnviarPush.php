<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Services;

use iEducar\Packages\BuritiTransporteEscolar\Support\ChavePush;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EnviarPush
{
    public static function disparar(int $alunoId, int $avisoId, string $titulo, string $corpo): void
    {
        if (!Schema::hasTable('transporte_escolar.notificacao_inscricao')) {
            return;
        }

        $publica = (string) DB::table('transporte_escolar.configuracao')->where('chave', 'vapid_publica')->value('valor');
        $privada = (string) DB::table('transporte_escolar.configuracao')->where('chave', 'vapid_privada')->value('valor');
        $inscricoes = DB::table('transporte_escolar.notificacao_inscricao')
            ->where('ref_cod_aluno', $alunoId)
            ->where('ativo', true)
            ->get();

        foreach ($inscricoes as $inscricao) {
            DB::table('transporte_escolar.notificacao_fila')->insert([
                'inscricao_id' => $inscricao->id,
                'aviso_id' => $avisoId,
                'titulo' => $titulo,
                'corpo' => $corpo,
                'created_at' => now(),
            ]);

            if ($publica === '' || $privada === '' || $inscricao->endpoint === '') {
                continue;
            }

            try {
                $codigo = self::postar(
                    (string) $inscricao->endpoint,
                    ChavePush::autorizacao($privada, (string) $inscricao->endpoint, $publica),
                );

                if (in_array($codigo, [404, 410], true)) {
                    DB::table('transporte_escolar.notificacao_inscricao')->where('id', $inscricao->id)->update([
                        'ativo' => false,
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable) {
            }
        }
    }

    private static function postar(string $endpoint, string $autorizacao): int
    {
        if (!function_exists('curl_init')) {
            return 0;
        }

        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => ['TTL: 86400', 'Content-Length: 0', 'Authorization: ' . $autorizacao],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $codigo;
    }
}
