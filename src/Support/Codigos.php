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
     * Mesmos códigos e rótulos de App\Models\TransportationVehicleType,
     * gravados em pmieducar.aluno.veiculo_transporte_escolar.
     *
     * @var array<int, string>
     */
    public const VEICULO_CENSO = [
        1 => 'Rodoviário - Vans/Kombis',
        2 => 'Rodoviário - Microônibus',
        3 => 'Rodoviário - Ônibus',
        4 => 'Rodoviário - Bicicleta',
        5 => 'Rodoviário - Tração animal',
        6 => 'Rodoviário - Outro',
        7 => 'Aquaviário/Embarcação - Capacidade de até 5 alunos',
        8 => 'Aquaviário/Embarcação - Capacidade entre 5 a 15 alunos',
        9 => 'Aquaviário/Embarcação - Capacidade entre 15 a 35 alunos',
        10 => 'Aquaviário/Embarcação - Capacidade acima de 35 alunos',
        11 => 'Ferroviário - Trem/Metrô',
    ];

    /**
     * @return array<string, list<int>>
     */
    public static function modaisCenso(): array
    {
        return [
            'Rodoviário' => [1, 2, 3, 4, 5, 6],
            'Aquaviário' => [7, 8, 9, 10],
            'Ferroviário' => [11],
        ];
    }

    public static function opcaoCenso(int $codigo): string
    {
        $rotulo = self::VEICULO_CENSO[$codigo] ?? '';
        $partes = explode(' - ', $rotulo, 2);

        return $partes[1] ?? $rotulo;
    }

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
