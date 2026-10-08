<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Colunas do i-Educar 2.12 que este pacote lê ou atualiza.
 * Se faltarem, a migration para. Não cria essas colunas de novo.
 */
final class CoreEsquema
{
    /**
     * @return array<string, list<string>>
     */
    public static function colunas(): array
    {
        return [
            'pmieducar.aluno' => [
                'cod_aluno',
                'ref_idpes',
                'ativo',
                'tipo_transporte',
                'veiculo_transporte_escolar',
                'utiliza_transporte_rural',
                'rota_transporte',
            ],
            'pmieducar.matricula' => [
                'cod_matricula',
                'ref_cod_aluno',
                'ref_ref_cod_escola',
                'ref_cod_curso',
                'ref_ref_cod_serie',
                'ano',
                'ativo',
                'aprovado',
                'saida_escola',
            ],
            'pmieducar.matricula_turma' => [
                'ref_cod_matricula',
                'ref_cod_turma',
                'ativo',
                'etapa_educacenso',
            ],
            'pmieducar.escola' => [
                'cod_escola',
                'ref_idpes',
                'latitude',
                'longitude',
                'zona_localizacao',
                'ativo',
            ],
            'pmieducar.turma' => ['cod_turma', 'nm_turma', 'ref_ref_cod_serie'],
            'pmieducar.serie' => ['cod_serie', 'nm_serie'],
            'pmieducar.escola_usuario' => ['ref_cod_usuario', 'ref_cod_escola'],
            'pmieducar.tipo_usuario' => ['cod_tipo_usuario', 'nivel', 'ativo'],
            'pmieducar.usuario' => ['cod_usuario', 'ref_cod_tipo_usuario'],
            'cadastro.pessoa' => ['idpes', 'nome'],
            'cadastro.fisica' => ['idpes', 'cpf', 'data_nasc', 'sexo', 'zona_localizacao_censo'],
            'cadastro.juridica' => ['idpes', 'cnpj', 'fantasia'],
        ];
    }

    public static function exigir(): void
    {
        foreach (self::colunas() as $tabela => $colunas) {
            if (!Schema::hasTable($tabela)) {
                throw new RuntimeException("Transporte Escolar v2 precisa da tabela {$tabela} do i-Educar 2.12.");
            }

            foreach ($colunas as $coluna) {
                if (!Schema::hasColumn($tabela, $coluna)) {
                    throw new RuntimeException("Transporte Escolar v2 precisa da coluna {$tabela}.{$coluna} do i-Educar 2.12. Rode as migrations do núcleo antes deste pacote.");
                }
            }
        }
    }
}
