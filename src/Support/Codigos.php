<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

final class Codigos
{
    public const TIPO_ROTA = ['U' => 'Urbana', 'R' => 'Rural'];

    public const SENTIDO = ['I' => 'Ida', 'V' => 'Volta', 'C' => 'Circular'];

    public const PERIODO = [
        'manha' => 'Manhã',
        'tarde' => 'Tarde',
        'noite' => 'Noite',
        'integral' => 'Integral',
    ];

    public const MALHA = ['P' => 'Pavimentada', 'N' => 'Não pavimentada'];

    public const VINCULO = [
        'efetivo' => 'Efetivo',
        'contratado' => 'Contratado',
        'terceirizado' => 'Terceirizado',
    ];

    /**
     * Mesmos códigos do Educacenso já gravados em pmieducar.aluno.veiculo_transporte_escolar.
     * Não há tabela de tipo no núcleo; o domínio fica no código.
     *
     * @var array<int, string>
     */
    public const VEICULO_CENSO = [
        1 => 'Vans/Kombis',
        2 => 'Micro-ônibus',
        3 => 'Ônibus',
        4 => 'Bicicleta',
        5 => 'Tração animal',
        6 => 'Outro',
        7 => 'Capacidade de até 5 alunos',
        8 => 'Capacidade de 5 a 15 alunos',
        9 => 'Capacidade de 15 a 35 alunos',
        10 => 'Capacidade acima de 35 alunos',
        11 => 'Trem/Metrô',
    ];

    public static function tipoRota(string $valor): bool
    {
        return isset(self::TIPO_ROTA[$valor]);
    }

    public static function sentido(string $valor): bool
    {
        return isset(self::SENTIDO[$valor]);
    }

    public static function periodo(string $valor): bool
    {
        return isset(self::PERIODO[$valor]);
    }
}
