<?php

use iEducar\Packages\BuritiTransporteEscolar\Support\AtendimentoSetor;
use iEducar\Packages\BuritiTransporteEscolar\Support\AvisoTexto;
use iEducar\Packages\BuritiTransporteEscolar\Support\ConsumoReal;
use iEducar\Packages\BuritiTransporteEscolar\Support\CopiaAnoPlanejador;
use iEducar\Packages\BuritiTransporteEscolar\Support\DescargaJanela;
use iEducar\Packages\BuritiTransporteEscolar\Support\DiaLetivo;
use iEducar\Packages\BuritiTransporteEscolar\Support\DiasUteis;
use iEducar\Packages\BuritiTransporteEscolar\Support\Encargo;
use iEducar\Packages\BuritiTransporteEscolar\Support\Geocerca;
use iEducar\Packages\BuritiTransporteEscolar\Support\MedicaoContrato;
use iEducar\Packages\BuritiTransporteEscolar\Support\QrCodigo;
use iEducar\Packages\BuritiTransporteEscolar\Support\FilaIdempotente;
use iEducar\Packages\BuritiTransporteEscolar\Support\GastoRegra;
use iEducar\Packages\BuritiTransporteEscolar\Support\Gpx;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmCalculado;
use iEducar\Packages\BuritiTransporteEscolar\Support\KmRealizado;
use iEducar\Packages\BuritiTransporteEscolar\Support\OrdemPontos;
use iEducar\Packages\BuritiTransporteEscolar\Support\PermissaoMatriz;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\RateioSalario;
use iEducar\Packages\BuritiTransporteEscolar\Support\ResumoNumerico;
use iEducar\Packages\BuritiTransporteEscolar\Support\SincroniaCenso;
use iEducar\Packages\BuritiTransporteEscolar\Support\PedidoFamilia;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProcessoVaga;
use iEducar\Packages\BuritiTransporteEscolar\Support\ProtocoloSolicitacao;
use iEducar\Packages\BuritiTransporteEscolar\Support\SituacaoPedido;

require __DIR__ . '/../src/Support/Processos.php';
require __DIR__ . '/../src/Support/PermissaoMatriz.php';
require __DIR__ . '/../src/Support/Codigos.php';
require __DIR__ . '/../src/Support/KmCalculado.php';
require __DIR__ . '/../src/Support/SincroniaCenso.php';
require __DIR__ . '/../src/Support/CopiaAnoPlanejador.php';
require __DIR__ . '/../src/Support/OrdemPontos.php';
require __DIR__ . '/../src/Support/Gpx.php';
require __DIR__ . '/../src/Support/GastoRegra.php';
require __DIR__ . '/../src/Support/KmRealizado.php';
require __DIR__ . '/../src/Support/ResumoNumerico.php';
require __DIR__ . '/../src/Support/RateioSalario.php';
require __DIR__ . '/../src/Support/DescargaJanela.php';
require __DIR__ . '/../src/Support/FilaIdempotente.php';
require __DIR__ . '/../src/Support/DiasUteis.php';
require __DIR__ . '/../src/Support/MedicaoContrato.php';
require __DIR__ . '/../src/Support/Encargo.php';
require __DIR__ . '/../src/Support/ConsumoReal.php';
require __DIR__ . '/../src/Support/Geocerca.php';
require __DIR__ . '/../src/Support/AvisoTexto.php';
require __DIR__ . '/../src/Support/DiaLetivo.php';
require __DIR__ . '/../src/Support/QrCodigo.php';
require __DIR__ . '/../src/Support/ProtocoloSolicitacao.php';
require __DIR__ . '/../src/Support/PedidoFamilia.php';
require __DIR__ . '/../src/Support/SituacaoPedido.php';
require __DIR__ . '/../src/Support/ProcessoVaga.php';
require __DIR__ . '/../src/Support/AtendimentoSetor.php';

$falhas = 0;

function ok(bool $condicao, string $mensagem): void
{
    global $falhas;

    if ($condicao) {
        echo "ok {$mensagem}\n";

        return;
    }

    $falhas++;
    echo "falhou {$mensagem}\n";
}

$institucional = PermissaoMatriz::paraNivel(2);
$escola = PermissaoMatriz::paraNivel(4);
$biblioteca = PermissaoMatriz::paraNivel(8);

ok($institucional[Processos::SETE]['cadastra'] === true, 'institucional grava importação do SETE');
ok($escola[Processos::SETE]['visualiza'] === false, 'escola não vê importação do SETE');
ok($escola[Processos::ALUNOS]['cadastra'] === true, 'escola grava aluno da própria rota');
ok($escola[Processos::ROTAS]['cadastra'] === false, 'escola não altera rota da rede');
ok($escola[Processos::COPIA]['visualiza'] === false, 'escola não copia o ano');
ok($biblioteca[Processos::RAIZ]['visualiza'] === false, 'biblioteca não entra no transporte');

$plano = CopiaAnoPlanejador::planejar(
    [
        ['id' => 10, 'descricao' => 'Rural A'],
        ['id' => 20, 'descricao' => 'Rural B'],
    ],
    [
        ['rota_id' => 20, 'seq' => 1, 'ponto_id' => 8, 'veiculo_id' => null, 'hora' => null, 'tipo' => 'I'],
        ['rota_id' => 10, 'seq' => 1, 'ponto_id' => 3, 'veiculo_id' => null, 'hora' => null, 'tipo' => 'I'],
    ],
    [
        ['aluno_id' => 1, 'rota_id' => 10, 'ponto_id' => 3, 'turno' => 'manha', 'matricula_ativa' => true],
        ['aluno_id' => 2, 'rota_id' => 20, 'ponto_id' => 8, 'turno' => 'manha', 'matricula_ativa' => false],
    ],
    ['Rural A'],
);

ok($plano['ignoradas'] === ['Rural A'], 'rota que já existe no destino não é copiada');
ok($plano['rotas'][0]['origem_id'] === 20, 'a segunda rota continua com o próprio id');
ok($plano['rotas'][0]['itinerarios'][0]['ponto_id'] === 8, 'itinerário não segue a posição da lista');
ok($plano['alunos_recusados'] === [2], 'aluno sem matrícula ativa fica de fora');

$censo = SincroniaCenso::aoVincular(SincroniaCenso::ESTADUAL, true, 3, [2], 'Linha Norte');
ok($censo['tipo_transporte'] === SincroniaCenso::ESTADUAL, 'transporte estadual da ficha não vira municipal');
ok($censo['utiliza_transporte_rural'] === true, 'rota rural marca o campo que o núcleo já tem');
ok($censo['veiculo_transporte_escolar'] === [2, 3], 'o código de veículo do Censo é acrescentado');
ok(SincroniaCenso::aoVincular(0, false, null, [], 'Centro')['tipo_transporte'] === SincroniaCenso::MUNICIPAL, 'sem transporte passa a municipal');

$km = KmCalculado::quilometros([
    ['latitude' => 0.0, 'longitude' => 0.0],
    ['latitude' => 0.0, 'longitude' => 1.0],
]);
ok($km > 110 && $km < 112, 'um grau de longitude no equador fica perto de 111 km');

$gpx = '<gpx><trk><trkseg><trkpt lat="-12.1" lon="-38.5"></trkpt><trkpt lat="-12.2" lon="-38.4"></trkpt></trkseg></trk></gpx>';
ok(count(Gpx::coordenadas($gpx)) === 2, 'GPX lê latitude e longitude');

$ordem = OrdemPontos::sugerir(
    [
        ['latitude' => 0.0, 'longitude' => 0.0],
        ['latitude' => 0.0, 'longitude' => 2.0],
    ],
    [
        ['id' => 7, 'latitude' => 0.0, 'longitude' => 2.1],
        ['id' => 3, 'latitude' => 0.0, 'longitude' => 0.1],
    ],
);
ok($ordem === [3, 7], 'pontos seguem a ordem da trilha');

ok($escola[Processos::VIAGENS]['cadastra'] === true, 'escola grava o embarque das suas rotas');
ok($escola[Processos::GASTOS]['visualiza'] === false, 'escola não vê o dinheiro da rede');
ok($escola[Processos::CACS]['visualiza'] === false, 'painel do conselho não abre para a escola');
ok($escola[Processos::OFFLINE]['cadastra'] === true, 'escola sobe a fila offline das suas rotas');
ok($institucional[Processos::CACS]['cadastra'] === false, 'painel do CACS é só leitura');

ok(GastoRegra::motivo('salario', 'pnate', false) !== null, 'salário não entra como despesa do PNATE');
ok(GastoRegra::motivo('manutencao_aquaviaria', 'pnate', false) !== null, 'embarcação fica oculta sem transporte aquaviário');
ok(GastoRegra::motivo('manutencao_rodoviaria', 'pnate', false) === null, 'manutenção rodoviária pode usar PNATE');
ok(KmRealizado::deOdometro(10.0, 12.5) === 2.5, 'km realizado é a diferença do odômetro');
ok(KmRealizado::deOdometro(12.0, 10.0) === null, 'odômetro regressivo não vira km');
ok(ResumoNumerico::de([1.0, 3.0, 5.0])['media'] === 3.0, 'média de quilometragem');
ok(RateioSalario::porKm(1000, 25, 100) === 250.0, 'salário rateado pelo km');
ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-08 05:10')) === true, 'descarga das 05:00 em dia de semana');
ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-10 05:10')) === false, 'descarga não abre no sábado');
ok(FilaIdempotente::novos(['a', 'b'], ['a']) === ['b'], 'evento já recebido não entra de novo');
ok(FilaIdempotente::alteraCadastroMestre('embarque') === false, 'embarque não altera o cadastro mestre');
ok(MedicaoContrato::valor(1000, 0, 20) === 0.0, 'contrato sem viagem no mês não é medido');
ok(MedicaoContrato::valor(1000, 10, 20) === 500.0, 'medição acompanha os dias com viagem');
ok(Encargo::compor(1000, 20) === 1200.0, 'encargo compõe o salário do custo');
ok(ConsumoReal::piorQueOMedio(6.0, 10.0) === true, 'consumo real pior que o médio cadastrado');
ok(Geocerca::dentro(0.0, 0.0, 0.0, 0.0, 150) === true, 'ponto coincidente está dentro da cerca');
ok(AvisoTexto::frase('Ana', 'desembarque', 'Rural') === 'Ana desembarcou na rota Rural.', 'aviso de desembarque');
ok($escola[Processos::AVISOS]['visualiza'] === true, 'escola vê o aviso das suas rotas');
ok(DiasUteis::contar(2026, 10) === 22, 'outubro de 2026 tem 22 dias de semana');
ok(DiaLetivo::eLetivo(false, []) === null, 'sem calendário a descarga usa dia de semana');
ok(DiaLetivo::eLetivo(true, ['n']) === false, 'motivo não letivo fecha a janela');
ok(DiaLetivo::eLetivo(true, [null]) === true, 'dia do calendário sem motivo é letivo');
ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-08 05:10'), ['05:00'], 20, false) === false, 'dia não letivo não dispara a descarga');
ok(RateioSalario::porAlunos(1000, 1, 4) === 250.0, 'salário rateado pelo número de alunos');
ok(count(QrCodigo::matriz('TE-1')) === 21, 'QR Code do aluno cabe na versão 1');

$poli = PermissaoMatriz::paraNivel(1);
ok($poli[Processos::CACS]['cadastra'] === false, 'poli-institucional também só lê o CACS');
ok($escola[Processos::RELATORIOS]['visualiza'] === true && $escola[Processos::RELATORIOS]['cadastra'] === false, 'escola consulta relatório e não grava');
ok($escola[Processos::AVISOS]['cadastra'] === false, 'escola não cria aviso à mão');
ok($institucional[Processos::GASTOS]['cadastra'] === true, 'institucional lança gasto');
ok($biblioteca[Processos::VIAGENS]['visualiza'] === false, 'biblioteca não abre viagem');

ok(GastoRegra::motivo('salario', 'proprio', false) !== null, 'salário não vira gasto nem com recurso próprio');
ok(GastoRegra::motivo('multa', 'pnate', false) !== null, 'multa não entra no PNATE');
ok(GastoRegra::motivo('tarifa_bancaria', 'pnate', true) !== null, 'tarifa bancária não entra no PNATE');
ok(GastoRegra::motivo(null, 'pnate', false) !== null, 'categoria vazia é recusada');
ok(GastoRegra::motivo('passe', null, false) !== null, 'fonte vazia é recusada');
ok(GastoRegra::motivo('manutencao_aquaviaria', 'pnate', true) === null, 'embarcação entra quando o município marca aquaviário');
ok(GastoRegra::motivo('fantasia', 'pnate', false) !== null, 'categoria inventada é recusada');

ok(KmRealizado::deOdometro(null, 10.0) === null, 'odômetro sem km inicial não calcula');
ok(KmRealizado::deOdometro(10.0, 10.0) === 0.0, 'odômetro parado resulta em zero');
ok(ResumoNumerico::de([])['total'] === 0.0, 'resumo sem número fica em zero');
ok(RateioSalario::porKm(0, 10, 10) === 0.0, 'salário zero não rateia');
ok(RateioSalario::porAlunos(1000, 0, 4) === 0.0, 'sem aluno embarcado o rateio é zero');

ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-08 12:30')) === true, 'descarga das 12:30');
ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-08 08:00')) === false, 'fora da janela a descarga periódica não abre');
ok(DescargaJanela::noHorario(new DateTimeImmutable('2026-10-10 05:10'), ['05:00'], 20, true) === true, 'sábado letivo entra na janela');
ok(DescargaJanela::aoAbrirComRede(false) === false, 'sem rede a abertura não descarrega');

ok(FilaIdempotente::alteraCadastroMestre('aluno') === true, 'evento de cadastro é recusado na fila');
ok(FilaIdempotente::novos([], ['a']) === [], 'fila vazia não grava nada');
ok(MedicaoContrato::valor(800, 40, 20) === 800.0, 'medição não passa do valor mensal');
ok(MedicaoContrato::valor(-10, 5, 20) === 0.0, 'contrato negativo não gera custo');
ok(Encargo::compor(0, 20) === 0.0, 'sem salário o encargo não inventa valor');
ok(Encargo::compor(1000, -5) === 1000.0, 'encargo negativo é ignorado');
ok(ConsumoReal::kmPorLitro(100, 10) === 10.0, 'consumo real é km por litro');
ok(ConsumoReal::kmPorLitro(0, 10) === null, 'sem km não há consumo');
ok(ConsumoReal::piorQueOMedio(9.0, 10.0) === false, 'consumo dentro da folga não alerta');
ok(Geocerca::dentro(0.0, 1.0, 0.0, 0.0, 150) === false, 'ponto a um grau fica fora da cerca');
ok(Geocerca::dentro(0.0, 1.0, 0.0, 0.0, 0) === true, 'raio zero não marca fora');
ok(AvisoTexto::frase('Ana', 'embarque', 'Rural') === 'Ana embarcou na rota Rural.', 'aviso de embarque');
ok(DiaLetivo::eLetivo(true, ['e']) === true, 'dia extra do calendário é letivo');
ok(DiaLetivo::eLetivo(true, []) === false, 'calendário do ano sem este dia não é letivo');
ok(DiasUteis::contar(2026, 2) === 20, 'fevereiro de 2026 tem 20 dias de semana');
ok(count(QrCodigo::matriz('TE-123456789012345')) === 25, 'código longo sobe para a versão 2 do QR');
ok(str_contains(QrCodigo::svg('TE-1'), '<svg'), 'o desenho do QR é SVG');

$vazio = CopiaAnoPlanejador::planejar(
    [['id' => 1, 'descricao' => 'Centro']],
    [['rota_id' => 1, 'seq' => 2, 'ponto_id' => 9, 'veiculo_id' => 4, 'hora' => '07:00', 'tipo' => 'V']],
    [['aluno_id' => 8, 'rota_id' => 1, 'ponto_id' => 9, 'turno' => 'manha', 'matricula_ativa' => true]],
    [],
);
ok($vazio['rotas'][0]['itinerarios'][0]['ponto_id'] === 9, 'cópia leva o ponto da rota de origem');
ok($vazio['rotas'][0]['alunos'][0]['aluno_id'] === 8, 'aluno com matrícula ativa no destino entra');
ok($vazio['ignoradas'] === [], 'ano destino vazio copia a rota');

$semCodigo = SincroniaCenso::aoVincular(0, false, 99, [3], 'Centro');
ok($semCodigo['veiculo_transporte_escolar'] === [3], 'código de veículo fora do Censo não entra na ficha');
$repetido = SincroniaCenso::aoVincular(2, false, 3, [3], 'Centro');
ok($repetido['veiculo_transporte_escolar'] === [3], 'o mesmo código de veículo não duplica');
ok(SincroniaCenso::aoDesvincularUltima()['tipo_transporte'] === 0, 'última saída zera o transporte da ficha');
ok(Gpx::coordenadas('<gpx></gpx>') === [], 'GPX sem ponto não inventa coordenada');
ok(KmCalculado::minutos(40, 40) === 60, 'tempo estimado usa a velocidade média');
ok(count(Processos::titulos()) === 21, 'cada tela do menu tem um processo');

$protocolo = ProtocoloSolicitacao::montar(2026, 1);
ok(preg_match('/^TE-2026-000001-\d$/', $protocolo) === 1, 'protocolo traz ano, sequência e dígito');
ok(ProtocoloSolicitacao::conferir($protocolo) === true, 'dígito do protocolo confere');
ok(ProtocoloSolicitacao::conferir(substr($protocolo, 0, -1) . ((substr($protocolo, -1) === '0') ? '1' : '0')) === false, 'dígito trocado invalida o protocolo');
ok(ProtocoloSolicitacao::respostaConsulta(false, false, false) === 'invalido', 'protocolo incompleto não consulta');
ok(ProtocoloSolicitacao::respostaConsulta(true, false, false) === 'nao_encontrado', 'protocolo certo sem pedido não abre ficha');
ok(ProtocoloSolicitacao::respostaConsulta(true, true, false) === 'nao_encontrado', 'data de nascimento errada não revela o pedido');
ok(ProtocoloSolicitacao::respostaConsulta(true, true, true) === 'ok', 'protocolo e nascimento abrem o acompanhamento');
ok(ProtocoloSolicitacao::mesmaData('2016-04-02', '2016-04-02') === true, 'a mesma data de nascimento confere');
ok(ProtocoloSolicitacao::mesmaData('2016-02-31', '2016-02-31') === false, 'dia inexistente não conta como data');

$pedidoOk = [
    'nome_aluno' => 'Ana Silva',
    'nascimento' => '2016-04-02',
    'nome_responsavel' => 'Maria Silva',
    'telefone' => '(11) 99999-8888',
    'endereco' => 'Rua das Flores, 10',
    'turno' => 'manha',
    'escola' => 'Escola Centro',
];
ok(PedidoFamilia::erros($pedidoOk, new DateTimeImmutable('2026-10-08')) === [], 'pedido completo da família passa');
ok(PedidoFamilia::erros(['nome_aluno' => 'A'], new DateTimeImmutable('2026-10-08')) !== [], 'pedido vazio é recusado');
$futuro = $pedidoOk;
$futuro['nascimento'] = '2027-01-01';
ok(PedidoFamilia::erros($futuro, new DateTimeImmutable('2026-10-08')) !== [], 'nascimento futuro é recusado');

ok(SituacaoPedido::mover('recebida', 'cancelar', 'familia') === 'cancelada', 'família cancela pedido ainda não analisado');
ok(SituacaoPedido::mover('deferida', 'cancelar', 'familia') === null, 'família não cancela pedido já aceito');
ok(SituacaoPedido::mover('recebida', 'deferir', 'familia') === null, 'família não aceita o próprio pedido');
ok(SituacaoPedido::mover('recebida', 'encaminhar', 'escola') === 'em_analise', 'escola encaminha o pedido');
ok(SituacaoPedido::mover('em_analise', 'deferir', 'escola') === null, 'escola não aceita o pedido');
ok(SituacaoPedido::mover('em_analise', 'deferir', 'rede') === 'deferida', 'rede aceita o pedido em análise');
ok(SituacaoPedido::mover('deferida', 'indeferir', 'rede') === null, 'pedido aceito não volta');
ok(str_contains(SituacaoPedido::frase('deferida'), 'aceito'), 'a família lê que o pedido foi aceito');
ok($escola[Processos::SOLICITACOES]['visualiza'] === true && $escola[Processos::SOLICITACOES]['cadastra'] === true, 'escola vê e encaminha o pedido da própria escola');
ok($escola[Processos::SOLICITACOES]['exclui'] === false, 'escola não apaga pedido');
ok($biblioteca[Processos::SOLICITACOES]['visualiza'] === false, 'biblioteca não vê pedido de transporte');
ok(AtendimentoSetor::publicar('deferida') === true, 'pedido aceito mostra o setor');
ok(AtendimentoSetor::publicar('em_analise') === false, 'pedido em análise ainda não mostra o endereço');
$mapa = AtendimentoSetor::mapa('-30.03', '-51.22');
ok($mapa !== null && str_contains($mapa['bbox'], '-51.22'), 'coordenadas válidas montam o mapa');
ok(AtendimentoSetor::mapa('91', '10') === null, 'latitude impossível não vira mapa');
ok(AtendimentoSetor::mapa('', '-51') === null, 'coordenada vazia não vira mapa');
ok(ProcessoVaga::noPeriodo(true, '2026-10-01', '2026-10-31', new DateTimeImmutable('2026-10-08')) === true, 'pedido dentro do período do processo');
ok(ProcessoVaga::noPeriodo(true, '2026-10-01', '2026-10-31', new DateTimeImmutable('2026-11-01')) === false, 'pedido fora do período não entra');
ok(ProcessoVaga::noPeriodo(false, '2026-10-01', '2026-12-31', new DateTimeImmutable('2026-10-08')) === false, 'processo inativo não recebe pedido');
ok(ProcessoVaga::cabe(2, 1) === true, 'ainda há vaga na rota');
ok(ProcessoVaga::cabe(2, 2) === false, 'rota lotada não recebe outro pedido');
ok(ProcessoVaga::ocupa('deferida') === true && ProcessoVaga::ocupa('cancelada') === false, 'pedido aceito ocupa vaga e o cancelado devolve');

exit($falhas === 0 ? 0 : 1);
