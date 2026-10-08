<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Support;

/**
 * O que a família precisa informar para abrir o pedido.
 * Não cria pessoa nem matrícula no núcleo.
 */
final class PedidoFamilia
{
    /**
     * @param array<string, mixed> $dados
     * @return list<string>
     */
    public static function erros(array $dados, ?\DateTimeImmutable $hoje = null): array
    {
        $hoje ??= new \DateTimeImmutable('today');
        $erros = [];
        $aluno = trim((string) ($dados['nome_aluno'] ?? ''));
        $responsavel = trim((string) ($dados['nome_responsavel'] ?? ''));
        $endereco = trim((string) ($dados['endereco'] ?? ''));
        $escola = trim((string) ($dados['escola'] ?? ''));
        $turno = (string) ($dados['turno'] ?? '');
        $telefone = preg_replace('/\D+/', '', (string) ($dados['telefone'] ?? '')) ?? '';
        $nascimento = ProtocoloSolicitacao::mesmaData((string) ($dados['nascimento'] ?? ''), (string) ($dados['nascimento'] ?? ''))
            ? (string) $dados['nascimento']
            : null;

        if (mb_strlen($aluno) < 3) {
            $erros[] = 'Informe o nome do aluno.';
        }

        if ($nascimento === null) {
            $erros[] = 'Informe a data de nascimento do aluno.';
        } else {
            $data = new \DateTimeImmutable($nascimento);

            if ($data > $hoje) {
                $erros[] = 'A data de nascimento não pode ser futura.';
            } elseif ($data < $hoje->modify('-25 years')) {
                $erros[] = 'Confira a data de nascimento do aluno.';
            }
        }

        if (mb_strlen($responsavel) < 3) {
            $erros[] = 'Informe o nome de quem pede o transporte.';
        }

        if (strlen($telefone) < 10 || strlen($telefone) > 11) {
            $erros[] = 'Informe um telefone com DDD.';
        }

        if (mb_strlen($endereco) < 5) {
            $erros[] = 'Informe o endereço de onde o aluno sai.';
        }

        if (!isset(Codigos::PERIODO[$turno])) {
            $erros[] = 'Escolha o turno.';
        }

        if (mb_strlen($escola) < 3) {
            $erros[] = 'Informe a escola.';
        }

        return $erros;
    }
}
