<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * A trilha vem do gatilho do i-Educar em public.ieducar_audit.
 * Antes vazio é inclusão. Depois vazio é exclusão. Os dois preenchidos são alteração.
 */
final class AuditoriaTransporte
{
    public static function operacao(bool $tinhaAntes, bool $temDepois): string
    {
        if (!$tinhaAntes && $temDepois) {
            return 'Inclusão';
        }

        if ($tinhaAntes && !$temDepois) {
            return 'Exclusão';
        }

        return 'Alteração';
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     * @return list<string>
     */
    public static function campos(?array $antes, ?array $depois): array
    {
        $antes ??= [];
        $depois ??= [];
        $chaves = array_unique(array_merge(array_keys($antes), array_keys($depois)));
        $mudou = [];

        foreach ($chaves as $chave) {
            if (in_array($chave, ['updated_at', 'created_at'], true)) {
                continue;
            }

            $velho = self::texto($antes[$chave] ?? null);
            $novo = self::texto($depois[$chave] ?? null);

            if ($velho !== $novo) {
                $mudou[] = (string) $chave;
            }
        }

        sort($mudou);

        return $mudou;
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     */
    public static function resumo(?array $antes, ?array $depois): string
    {
        $partes = [];

        foreach (self::campos($antes, $depois) as $chave) {
            $partes[] = $chave . ': ' . self::texto($antes[$chave] ?? null) . ' -> ' . self::texto($depois[$chave] ?? null);
        }

        return implode(' | ', $partes);
    }

    public static function assunto(string $tabela): string
    {
        return self::ASSUNTOS[$tabela] ?? self::rotulo($tabela);
    }

    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $depois
     * @return array{modo: string, frase: string, linhas: list<array{rotulo: string, antes: string, depois: string}>}
     */
    public static function leitura(?array $antes, ?array $depois): array
    {
        $modo = match (self::operacao($antes !== null, $depois !== null)) {
            'Inclusão' => 'inclusao',
            'Exclusão' => 'exclusao',
            default => 'alteracao',
        };
        $chaves = $modo === 'alteracao'
            ? self::campos($antes, $depois)
            : array_values(array_filter(
                array_keys($modo === 'exclusao' ? ($antes ?? []) : ($depois ?? [])),
                static fn (string $chave): bool => !in_array($chave, self::OCULTOS, true)
            ));
        $linhas = [];

        foreach ($chaves as $chave) {
            $velho = self::apresentar($chave, $antes[$chave] ?? null);
            $novo = self::apresentar($chave, $depois[$chave] ?? null);

            if ($modo !== 'alteracao' && ($modo === 'exclusao' ? $velho : $novo) === 'Não informado') {
                continue;
            }

            $linhas[] = [
                'rotulo' => self::rotulo($chave),
                'antes' => $velho,
                'depois' => $novo,
            ];
        }

        usort($linhas, static fn (array $a, array $b): int => strcasecmp($a['rotulo'], $b['rotulo']));

        return [
            'modo' => $modo,
            'frase' => match ($modo) {
                'inclusao' => 'Estes dados foram registrados.',
                'exclusao' => 'Estes dados foram apagados.',
                default => 'O que mudou nesta ação.',
            },
            'linhas' => $linhas,
        ];
    }

    public static function rotulo(string $chave): string
    {
        if (isset(self::ROTULOS[$chave])) {
            return self::ROTULOS[$chave];
        }

        $texto = str_replace('_', ' ', $chave);

        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }

    public static function apresentar(string $chave, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Não informado';
        }

        if (is_array($valor)) {
            $partes = array_map(static fn ($item) => self::apresentar($chave, $item), $valor);

            return $partes === [] ? 'Não informado' : implode(', ', $partes);
        }

        if (is_bool($valor) || in_array($valor, ['true', 'false', 't', 'f'], true)) {
            return in_array($valor, [true, 'true', 't', 1, '1'], true) ? 'Sim' : 'Não';
        }

        $texto = (string) $valor;

        if ($chave === 'ativo' && in_array($texto, ['0', '1'], true)) {
            return $texto === '1' ? 'Ativo' : 'Inativo';
        }

        if (in_array($chave, self::SIM_NAO, true) && in_array($texto, ['0', '1'], true)) {
            return $texto === '1' ? 'Sim' : 'Não';
        }

        $mapa = match ($chave) {
            'situacao' => self::SITUACAO,
            'tipo_rota' => Codigos::TIPO_ROTA,
            'sentido' => Codigos::SENTIDO,
            'tipo' => self::TIPO_EVENTO,
            'periodo', 'turno' => Codigos::PERIODO,
            'malha' => Codigos::MALHA,
            'vinculo' => Codigos::VINCULO,
            'fonte' => self::FONTE,
            default => [],
        };

        if ($chave === 'tipo_censo' && isset(Codigos::VEICULO_CENSO[(int) $texto])) {
            return Codigos::VEICULO_CENSO[(int) $texto];
        }

        if (isset($mapa[$texto])) {
            return $mapa[$texto];
        }

        if (in_array($chave, self::DINHEIRO, true) && is_numeric($texto)) {
            return 'R$ ' . number_format((float) $texto, 2, ',', '.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto) === 1) {
            $data = \DateTimeImmutable::createFromFormat('!Y-m-d', $texto);

            return $data === false ? $texto : $data->format('d/m/Y');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $texto) === 1) {
            $data = date_create($texto);

            return $data === false ? $texto : $data->format('d/m/Y H:i');
        }

        if (preg_match('/^\d{2}:\d{2}/', $texto) === 1) {
            return substr($texto, 0, 5);
        }

        if (mb_strlen($texto) > 280) {
            return mb_substr($texto, 0, 277) . '…';
        }

        return $texto;
    }

    private const OCULTOS = ['id', 'created_at', 'updated_at', 'origem', 'origem_chave'];

    private const SIM_NAO = [
        'exclusivo_escolar', 'adaptado_pcd', 'terceirizada', 'preventiva', 'fora_da_cerca', 'caminho_da_escola',
    ];

    private const DINHEIRO = ['valor', 'valor_mensal', 'salario_mensal', 'mao_obra', 'saldo_informado'];

    private const SITUACAO = [
        'aberto' => 'Aberto',
        'pausado' => 'Pausado',
        'bloqueado' => 'Pausado',
        'recebida' => 'Recebida',
        'em_analise' => 'Em análise',
        'deferida' => 'Aceita',
        'indeferida' => 'Recusada',
        'cancelada' => 'Cancelada',
        'solicitada' => 'Solicitada',
        'autorizada' => 'Autorizada',
        'em_execucao' => 'Em execução',
        'concluida' => 'Concluída',
        'solicitado' => 'Solicitado',
        'autorizado' => 'Autorizado',
        'abastecido' => 'Abastecido',
    ];

    private const FONTE = [
        'pnate' => 'PNATE',
        'proprio' => 'Recurso próprio',
    ];

    private const TIPO_EVENTO = [
        'embarque' => 'Embarque',
        'desembarque' => 'Desembarque',
        'falta' => 'Falta',
    ];

    private const ASSUNTOS = [
        'empresa' => 'Empresa',
        'motorista' => 'Motorista',
        'monitor' => 'Monitor',
        'fornecedor' => 'Fornecedor',
        'veiculo' => 'Veículo',
        'ponto' => 'Ponto de parada',
        'rota' => 'Rota',
        'rota_escola' => 'Escola da rota',
        'itinerario' => 'Itinerário',
        'tracado' => 'Traçado da rota',
        'aluno_rota' => 'Aluno na rota',
        'viagem' => 'Viagem',
        'embarque' => 'Embarque',
        'abastecimento' => 'Abastecimento',
        'manutencao_plano' => 'Plano de manutenção',
        'ordem_servico' => 'Ordem de serviço',
        'gasto' => 'Gasto',
        'contrato' => 'Contrato',
        'parcela_recurso' => 'Parcela de recurso',
        'ocorrencia' => 'Ocorrência',
        'solicitacao' => 'Pedido da família',
        'aviso_familia' => 'Aviso à família',
        'notificacao_modelo' => 'Modelo de aviso',
        'notificacao_inscricao' => 'Inscrição de aviso',
        'notificacao_fila' => 'Fila de aviso',
        'processo_vaga' => 'Processo de vagas',
        'processo_vaga_oferta' => 'Oferta de vaga',
        'processo_grupo' => 'Agrupamento do processo',
        'configuracao' => 'Configuração',
        'dispositivo' => 'Aparelho da linha',
        'fila_evento' => 'Evento da fila',
        'fila_pendente' => 'Fila pendente',
        'comprovante' => 'Comprovante',
    ];

    private const ROTULOS = [
        'id' => 'Número do registro',
        'descricao' => 'Descrição',
        'placa' => 'Placa',
        'renavam' => 'Renavam',
        'marca' => 'Marca',
        'ano_fabricacao' => 'Ano de fabricação',
        'ano_modelo' => 'Ano do modelo',
        'passageiros' => 'Lugares',
        'tipo_censo' => 'Tipo de veículo',
        'exclusivo_escolar' => 'Uso exclusivo escolar',
        'adaptado_pcd' => 'Adaptado para acessibilidade',
        'ativo' => 'Cadastro',
        'consumo_medio_km_l' => 'Consumo médio',
        'empresa_id' => 'Empresa',
        'motorista_id' => 'Motorista',
        'monitor_id' => 'Monitor',
        'veiculo_id' => 'Veículo',
        'rota_id' => 'Rota',
        'ponto_id' => 'Ponto',
        'fornecedor_id' => 'Fornecedor',
        'viagem_id' => 'Viagem',
        'ref_cod_aluno' => 'Aluno',
        'ref_cod_escola' => 'Escola',
        'ref_idpes' => 'Pessoa',
        'ano' => 'Ano',
        'tipo_rota' => 'Tipo da rota',
        'periodo' => 'Período',
        'sentido' => 'Sentido',
        'km_pav' => 'Quilômetros pavimentados',
        'km_npav' => 'Quilômetros não pavimentados',
        'km_calculado' => 'Quilômetros calculados',
        'km_trilha' => 'Quilômetros percorridos no GPS',
        'km_inicial' => 'Quilometragem inicial',
        'km_final' => 'Quilometragem final',
        'velocidade_media_kmh' => 'Velocidade média',
        'terceirizada' => 'Terceirizada',
        'nome' => 'Nome',
        'cnh' => 'CNH',
        'tipo_cnh' => 'Categoria da CNH',
        'vencimento_cnh' => 'Vencimento da CNH',
        'dt_habilitacao' => 'Data da habilitação',
        'vinculo' => 'Vínculo',
        'turno' => 'Turno',
        'salario_mensal' => 'Salário mensal',
        'situacao' => 'Situação',
        'valor' => 'Valor',
        'fonte' => 'Recurso',
        'data' => 'Data',
        'texto' => 'Texto',
        'protocolo' => 'Protocolo',
        'nome_aluno' => 'Aluno',
        'nome_responsavel' => 'Responsável',
        'telefone' => 'Telefone',
        'endereco' => 'Endereço',
        'escola_informada' => 'Escola informada',
        'motivo_situacao' => 'Motivo',
        'observacao' => 'Observação',
        'item' => 'Item',
        'litros' => 'Litros',
        'litros_autorizados' => 'Litros autorizados',
        'km' => 'Quilometragem',
        'posto' => 'Posto',
        'solicitante' => 'Quem pediu',
        'preventiva' => 'Manutenção preventiva',
        'peca' => 'Peça',
        'nota' => 'Nota',
        'categoria' => 'Categoria',
        'inicio' => 'Início',
        'fim' => 'Fim',
        'valor_mensal' => 'Valor mensal',
        'mes' => 'Mês',
        'saldo_informado' => 'Saldo informado',
        'vagas' => 'Vagas',
        'idade_min' => 'Idade mínima',
        'idade_max' => 'Idade máxima',
        'titulo' => 'Título',
        'corpo' => 'Texto do aviso',
        'token' => 'Código',
        'lido_em' => 'Lido em',
        'fora_da_cerca' => 'Fora do ponto',
        'tipo' => 'Tipo',
        'hora' => 'Horário',
        'seq' => 'Ordem da parada',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'raio_metros' => 'Raio do ponto',
        'residencia_latitude' => 'Latitude da casa',
        'residencia_longitude' => 'Longitude da casa',
        'cartao' => 'Cartão',
        'nascimento' => 'Nascimento',
        'pedido_em' => 'Data do pedido',
        'atualizado_em' => 'Atualizado em',
        'acompanhamento' => 'Código de acompanhamento',
        'malha' => 'Malha',
        'chave' => 'Item',
    ];

    private static function texto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if (is_array($valor)) {
            $json = json_encode($valor, JSON_UNESCAPED_UNICODE);

            return $json === false ? '—' : $json;
        }

        return (string) $valor;
    }
}
