<?php

use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AlunoRotaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FrequenciaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AuditoriaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AvisoController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoModeloController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\CacsController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\GastoController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\OfflineController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\ConformidadeController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\EstoqueController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\RelatorioController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\ViagemController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\CopiaAnoController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FrotaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NormaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\PainelController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\RotaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\SeteController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\SolicitacaoController;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Support\Facades\Route;

Route::prefix('transporte-escolar')->group(function () {
    Route::get('/', [PainelController::class, 'index'])->middleware('transporte.processo:' . Processos::RAIZ . ',ver');
    Route::get('/notificacoes/pulso', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoController::class, 'pulso'])->middleware('transporte.processo:' . Processos::RAIZ . ',ver');
    Route::get('/notificacoes', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoController::class, 'index'])->middleware('transporte.processo:' . Processos::RAIZ . ',ver');
    Route::get('/notificacoes/{notificacao}', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoController::class, 'show'])->middleware('transporte.processo:' . Processos::RAIZ . ',ver');
    Route::post('/notificacoes/{notificacao}/lida', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoController::class, 'lida'])->middleware('transporte.processo:' . Processos::RAIZ . ',ver');

    Route::get('/rotas', [RotaController::class, 'index'])->middleware('transporte.processo:' . Processos::ROTAS . ',ver');
    Route::post('/rotas', [RotaController::class, 'store'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::get('/rotas/{rota}', [RotaController::class, 'show'])->middleware('transporte.processo:' . Processos::MAPA . ',ver');
    Route::post('/rotas/{rota}/gpx', [RotaController::class, 'gpx'])->middleware('transporte.processo:' . Processos::MAPA . ',gravar');
    Route::post('/rotas/{rota}/percurso', [RotaController::class, 'percurso'])->middleware('transporte.processo:' . Processos::MAPA . ',gravar');
    Route::post('/rotas/{rota}/ordem', [RotaController::class, 'ordem'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/rotas/{rota}/itinerario/{parada}/remover', [RotaController::class, 'removerParada'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/rotas/{rota}/itinerario', [RotaController::class, 'itinerario'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/rotas/{rota}/escolas', [RotaController::class, 'escolas'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/pontos', [RotaController::class, 'ponto'])->middleware('transporte.processo:' . Processos::PONTOS . ',gravar');
    Route::post('/pontos/{ponto}/excluir', [FrotaController::class, 'excluirPonto'])->middleware('transporte.processo:' . Processos::PONTOS . ',excluir');
    Route::post('/pontos/{ponto}', [FrotaController::class, 'atualizarPonto'])->middleware('transporte.processo:' . Processos::PONTOS . ',gravar');
    Route::post('/pontos/{ponto}/coordenada', [RotaController::class, 'coordenada'])->middleware('transporte.processo:' . Processos::PONTOS . ',gravar');
    Route::post('/escolas/{escola}/coordenada', [RotaController::class, 'escolaCoordenada'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');

    Route::get('/alunos', [AlunoRotaController::class, 'index'])->middleware('transporte.processo:' . Processos::ALUNOS . ',ver');
    Route::post('/alunos', [AlunoRotaController::class, 'store'])->middleware('transporte.processo:' . Processos::ALUNOS . ',gravar');
    Route::post('/alunos/atualizar', [AlunoRotaController::class, 'atualizar'])->middleware('transporte.processo:' . Processos::ALUNOS . ',gravar');
    Route::post('/alunos/cartao', [AlunoRotaController::class, 'cartao'])->middleware('transporte.processo:' . Processos::ALUNOS . ',gravar');
    Route::post('/alunos/remover', [AlunoRotaController::class, 'destroy'])->middleware('transporte.processo:' . Processos::ALUNOS . ',excluir');

    Route::get('/copia-ano', [CopiaAnoController::class, 'index'])->middleware('transporte.processo:' . Processos::COPIA . ',ver');
    Route::post('/copia-ano', [CopiaAnoController::class, 'store'])->middleware('transporte.processo:' . Processos::COPIA . ',gravar');

    Route::get('/sete', [SeteController::class, 'index'])->middleware('transporte.processo:' . Processos::DIVERGENCIA . ',ver');
    Route::post('/sete', [SeteController::class, 'store'])->middleware('transporte.processo:' . Processos::SETE . ',gravar');
    Route::get('/sete/exportar/{entidade}', [SeteController::class, 'exportar'])->middleware('transporte.processo:' . Processos::SETE . ',ver');

    Route::get('/normas', [NormaController::class, 'index'])->middleware('transporte.processo:' . Processos::NORMAS . ',ver');

    Route::get('/frequencia', [FrequenciaController::class, 'index'])->middleware('transporte.processo:' . Processos::VIAGENS . ',ver');
    Route::get('/frequencia/{viagem}/pdf', [FrequenciaController::class, 'pdf'])->middleware('transporte.processo:' . Processos::VIAGENS . ',ver');
    Route::get('/viagens', [ViagemController::class, 'index'])->middleware('transporte.processo:' . Processos::VIAGENS . ',ver');
    Route::post('/viagens', [ViagemController::class, 'store'])->middleware('transporte.processo:' . Processos::VIAGENS . ',gravar');
    Route::post('/viagens/lote', [ViagemController::class, 'lote'])->middleware('transporte.processo:' . Processos::VIAGENS . ',gravar');
    Route::get('/viagens/{viagem}', [ViagemController::class, 'show'])->middleware('transporte.processo:' . Processos::VIAGENS . ',ver');
    Route::post('/viagens/{viagem}/evento', [ViagemController::class, 'evento'])->middleware('transporte.processo:' . Processos::VIAGENS . ',gravar');

    Route::get('/offline', [OfflineController::class, 'index'])->middleware('transporte.processo:' . Processos::OFFLINE . ',ver');
    Route::get('/offline/pacote', [OfflineController::class, 'pacote'])->middleware('transporte.processo:' . Processos::OFFLINE . ',ver');
    Route::post('/offline/fila', [OfflineController::class, 'fila'])->middleware('transporte.processo:' . Processos::OFFLINE . ',gravar');
    Route::post('/offline/pendente', [OfflineController::class, 'pendente'])->middleware('transporte.processo:' . Processos::OFFLINE . ',gravar');

    Route::get('/gastos', [GastoController::class, 'index'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::post('/gastos', [GastoController::class, 'gasto'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/abastecimento', [GastoController::class, 'abastecimento'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/plano', [GastoController::class, 'plano'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/ordem', [GastoController::class, 'ordem'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/contrato', [GastoController::class, 'contrato'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/parcela', [GastoController::class, 'parcela'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/aquaviario', [GastoController::class, 'aquaviario'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/geocerca', [GastoController::class, 'geocerca'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/gastos/rateio', [GastoController::class, 'rateio'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::get('/gastos/comprovante/{comprovante}', [GastoController::class, 'comprovante'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');

    Route::get('/ordens', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'ordens'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::post('/ordens', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'gravarOrdem'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/ordens/{ordem}/situacao', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'situacaoOrdem'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::get('/ordens/{ordem}/pdf', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'pdfOrdem'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::get('/ordens.csv', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'csvOrdens'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::get('/bilhetes', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'bilhetes'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::post('/bilhetes', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'gravarBilhete'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/bilhetes/{bilhete}/situacao', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'situacaoBilhete'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::get('/bilhetes/{bilhete}/pdf', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'pdfBilhete'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::get('/bilhetes.csv', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'csvBilhetes'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');

    Route::get('/conformidade', [ConformidadeController::class, 'index'])->middleware('transporte.processo:' . Processos::VEICULOS . ',ver');
    Route::post('/conformidade/autorizacao', [ConformidadeController::class, 'autorizacao'])->middleware('transporte.processo:' . Processos::VEICULOS . ',gravar');
    Route::post('/conformidade/autorizacao/{autorizacao}', [ConformidadeController::class, 'editarAutorizacao'])->middleware('transporte.processo:' . Processos::VEICULOS . ',gravar');
    Route::post('/conformidade/checklist', [ConformidadeController::class, 'checklist'])->middleware('transporte.processo:' . Processos::VEICULOS . ',gravar');
    Route::get('/conformidade/anexo/{tabela}/{id}', [ConformidadeController::class, 'anexoArquivo'])->middleware('transporte.processo:' . Processos::VEICULOS . ',ver');

    Route::get('/estoque', [EstoqueController::class, 'index'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::post('/estoque/item', [EstoqueController::class, 'item'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/estoque/item/{item}', [EstoqueController::class, 'editarItem'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/estoque/entrada', [EstoqueController::class, 'entrada'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/estoque/baixa', [EstoqueController::class, 'baixa'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');
    Route::post('/estoque/contagem', [EstoqueController::class, 'contagem'])->middleware('transporte.processo:' . Processos::GASTOS . ',gravar');

    Route::get('/relatorios', [RelatorioController::class, 'index'])->middleware('transporte.processo:' . Processos::RELATORIOS . ',ver');
    Route::get('/relatorios/csv', [RelatorioController::class, 'csv'])->middleware('transporte.processo:' . Processos::RELATORIOS . ',ver');
    Route::get('/relatorios/dossie', [RelatorioController::class, 'dossie'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::get('/avisos', [AvisoController::class, 'index'])->middleware('transporte.processo:' . Processos::AVISOS . ',ver');
    Route::get('/avisos/modelos', [NotificacaoModeloController::class, 'index'])->middleware('transporte.processo:' . Processos::AVISOS . ',gravar');
    Route::post('/avisos/modelos', [NotificacaoModeloController::class, 'store'])->middleware('transporte.processo:' . Processos::AVISOS . ',gravar');
    Route::post('/avisos/modelos/{modelo}', [NotificacaoModeloController::class, 'atualizar'])->middleware('transporte.processo:' . Processos::AVISOS . ',gravar');
    Route::get('/solicitacoes', [SolicitacaoController::class, 'index'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',ver');
    Route::post('/solicitacoes/atendimento', [SolicitacaoController::class, 'atendimento'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/processo', [SolicitacaoController::class, 'processo'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/processo/{processo}', [SolicitacaoController::class, 'editarProcesso'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/processo/{processo}/situacao', [SolicitacaoController::class, 'situacaoProcesso'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/grupo', [SolicitacaoController::class, 'grupo'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/grupo/{grupo}', [SolicitacaoController::class, 'editarGrupo'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/grupo/{grupo}/situacao', [SolicitacaoController::class, 'situacaoGrupo'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/oferta', [SolicitacaoController::class, 'oferta'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/oferta/{oferta}/situacao', [SolicitacaoController::class, 'situacaoOferta'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/oferta/{oferta}', [SolicitacaoController::class, 'editarOferta'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/{solicitacao}', [SolicitacaoController::class, 'situacao'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::get('/solicitacoes/{solicitacao}/pdf', [SolicitacaoController::class, 'pdf'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',ver');
    Route::get('/solicitacoes/{solicitacao}/mapa', [SolicitacaoController::class, 'mapa'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',ver');
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('transporte.processo:' . Processos::AUDITORIA . ',ver');
    Route::get('/auditoria/csv', [AuditoriaController::class, 'csv'])->middleware('transporte.processo:' . Processos::AUDITORIA . ',ver');
    Route::get('/cacs', [CacsController::class, 'index'])->middleware('transporte.processo:' . Processos::CACS . ',ver');
    Route::get('/cacs/csv', [CacsController::class, 'csv'])->middleware('transporte.processo:' . Processos::CACS . ',ver');

    foreach (['empresas' => Processos::EMPRESAS, 'motoristas' => Processos::MOTORISTAS, 'monitores' => Processos::MONITORES, 'veiculos' => Processos::VEICULOS, 'fornecedores' => Processos::FORNECEDORES, 'pontos' => Processos::PONTOS] as $caminho => $processo) {
        Route::get('/' . $caminho, [FrotaController::class, 'index'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',ver');
        Route::get('/' . $caminho . '/csv', [FrotaController::class, 'csv'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',ver');
        Route::post('/' . $caminho, [FrotaController::class, 'store'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',gravar');
        if ($caminho !== 'pontos') {
            Route::post('/' . $caminho . '/{registro}', [FrotaController::class, 'atualizar'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',gravar');
        }
    }
});
