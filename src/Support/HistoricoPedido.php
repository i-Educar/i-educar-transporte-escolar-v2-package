<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HistoricoPedido
{
    /**
     * @return list<string>
     */
    public static function exigidos(mixed $gravado): array
    {
        if (!is_string($gravado) || trim($gravado) === '') {
            return [];
        }

        $lista = json_decode($gravado, true);

        if (!is_array($lista)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $lista), static fn (string $item): bool => $item !== ''));
    }

    /**
     * @param list<string> $linhas
     */
    public static function gravarExigidos(array $linhas): ?string
    {
        $linhas = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $linhas), static fn (string $item): bool => $item !== ''));

        return $linhas === [] ? null : json_encode($linhas, JSON_UNESCAPED_UNICODE);
    }

    public static function ator(?string $forcado = null): string
    {
        if ($forcado !== null && trim($forcado) !== '') {
            return trim($forcado);
        }

        $usuario = Auth::user();

        if ($usuario === null) {
            return 'Família';
        }

        $nome = trim((string) ($usuario->name ?? ''));

        if ($nome === '' && !empty($usuario->ref_idpes)) {
            $nome = trim((string) DB::table('cadastro.pessoa')->where('idpes', $usuario->ref_idpes)->value('nome'));
        }

        return $nome !== '' ? $nome : 'Usuário do setor';
    }

    public static function lugar(object $pedido, ?string $rota = null): string
    {
        $rota = trim((string) ($rota ?? $pedido->rota_vinculada ?? $pedido->rota ?? ''));
        $rota = $rota !== '' ? $rota : 'rota do pedido';

        return match ((string) $pedido->situacao) {
            SituacaoPedido::RECEBIDA => 'Na secretaria, ainda sem análise',
            SituacaoPedido::ANALISE => 'Em análise',
            SituacaoPedido::DEFERIDA => 'Aceito, aguardando a documentação no setor',
            SituacaoPedido::CONFERIDA => 'Na rota ' . $rota,
            SituacaoPedido::ESPERA => 'Na lista de espera da ' . $rota,
            SituacaoPedido::INDEFERIDA => 'Encerrado, pedido recusado',
            SituacaoPedido::CANCELADA => 'Encerrado pela família',
            default => 'Situação não reconhecida',
        };
    }

    public static function registrar(int $solicitacao, string $situacao, string $lugar, ?string $detalhe = null, ?string $ator = null): void
    {
        if (!Schema::hasTable('transporte_escolar.solicitacao_historico')) {
            return;
        }

        DB::table('transporte_escolar.solicitacao_historico')->insert([
            'solicitacao_id' => $solicitacao,
            'situacao' => $situacao,
            'em' => now(),
            'ator' => self::ator($ator),
            'lugar' => $lugar,
            'detalhe' => $detalhe !== null && trim($detalhe) !== '' ? trim($detalhe) : null,
        ]);
    }

    /**
     * @param list<object> $gravados
     * @return list<object>
     */
    public static function linhas(object $pedido, array $gravados): array
    {
        if ($gravados !== []) {
            return $gravados;
        }

        $linhas = [(object) [
            'situacao' => SituacaoPedido::RECEBIDA,
            'em' => $pedido->pedido_em,
            'ator' => 'Família',
            'lugar' => 'Portal da família',
            'detalhe' => null,
        ]];

        if ((string) $pedido->situacao !== SituacaoPedido::RECEBIDA) {
            $linhas[] = (object) [
                'situacao' => (string) $pedido->situacao,
                'em' => $pedido->atualizado_em ?: $pedido->pedido_em,
                'ator' => 'Não registrado neste passo',
                'lugar' => self::lugar($pedido),
                'detalhe' => $pedido->motivo_situacao ?? null,
            ];
        }

        return $linhas;
    }
}
