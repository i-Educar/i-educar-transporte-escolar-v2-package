<?php

use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AlunoRotaController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AvisoController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\CacsController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\GastoController;
use iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\OfflineController;
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

    Route::get('/rotas', [RotaController::class, 'index'])->middleware('transporte.processo:' . Processos::ROTAS . ',ver');
    Route::post('/rotas', [RotaController::class, 'store'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::get('/rotas/{rota}', [RotaController::class, 'show'])->middleware('transporte.processo:' . Processos::MAPA . ',ver');
    Route::post('/rotas/{rota}/gpx', [RotaController::class, 'gpx'])->middleware('transporte.processo:' . Processos::MAPA . ',gravar');
    Route::post('/rotas/{rota}/ordem', [RotaController::class, 'ordem'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/rotas/{rota}/itinerario', [RotaController::class, 'itinerario'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/rotas/{rota}/escolas', [RotaController::class, 'escolas'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');
    Route::post('/pontos', [RotaController::class, 'ponto'])->middleware('transporte.processo:' . Processos::PONTOS . ',gravar');
    Route::post('/pontos/{ponto}/coordenada', [RotaController::class, 'coordenada'])->middleware('transporte.processo:' . Processos::PONTOS . ',gravar');
    Route::post('/escolas/{escola}/coordenada', [RotaController::class, 'escolaCoordenada'])->middleware('transporte.processo:' . Processos::ROTAS . ',gravar');

    Route::get('/alunos', [AlunoRotaController::class, 'index'])->middleware('transporte.processo:' . Processos::ALUNOS . ',ver');
    Route::post('/alunos', [AlunoRotaController::class, 'store'])->middleware('transporte.processo:' . Processos::ALUNOS . ',gravar');
    Route::post('/alunos/remover', [AlunoRotaController::class, 'destroy'])->middleware('transporte.processo:' . Processos::ALUNOS . ',excluir');

    Route::get('/copia-ano', [CopiaAnoController::class, 'index'])->middleware('transporte.processo:' . Processos::COPIA . ',ver');
    Route::post('/copia-ano', [CopiaAnoController::class, 'store'])->middleware('transporte.processo:' . Processos::COPIA . ',gravar');

    Route::get('/sete', [SeteController::class, 'index'])->middleware('transporte.processo:' . Processos::DIVERGENCIA . ',ver');
    Route::post('/sete', [SeteController::class, 'store'])->middleware('transporte.processo:' . Processos::SETE . ',gravar');
    Route::get('/sete/exportar/{entidade}', [SeteController::class, 'exportar'])->middleware('transporte.processo:' . Processos::SETE . ',ver');

    Route::get('/normas', [NormaController::class, 'index'])->middleware('transporte.processo:' . Processos::NORMAS . ',ver');

    Route::get('/viagens', [ViagemController::class, 'index'])->middleware('transporte.processo:' . Processos::VIAGENS . ',ver');
    Route::post('/viagens', [ViagemController::class, 'store'])->middleware('transporte.processo:' . Processos::VIAGENS . ',gravar');
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

    Route::get('/relatorios', [RelatorioController::class, 'index'])->middleware('transporte.processo:' . Processos::RELATORIOS . ',ver');
    Route::get('/relatorios/csv', [RelatorioController::class, 'csv'])->middleware('transporte.processo:' . Processos::RELATORIOS . ',ver');
    Route::get('/relatorios/dossie', [RelatorioController::class, 'dossie'])->middleware('transporte.processo:' . Processos::GASTOS . ',ver');
    Route::get('/avisos', [AvisoController::class, 'index'])->middleware('transporte.processo:' . Processos::AVISOS . ',ver');
    Route::get('/solicitacoes', [SolicitacaoController::class, 'index'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',ver');
    Route::post('/solicitacoes/atendimento', [SolicitacaoController::class, 'atendimento'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/processo', [SolicitacaoController::class, 'processo'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/oferta', [SolicitacaoController::class, 'oferta'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::post('/solicitacoes/{solicitacao}', [SolicitacaoController::class, 'situacao'])->middleware('transporte.processo:' . Processos::SOLICITACOES . ',gravar');
    Route::get('/cacs', [CacsController::class, 'index'])->middleware('transporte.processo:' . Processos::CACS . ',ver');

    foreach (['empresas' => Processos::EMPRESAS, 'motoristas' => Processos::MOTORISTAS, 'monitores' => Processos::MONITORES, 'veiculos' => Processos::VEICULOS, 'fornecedores' => Processos::FORNECEDORES, 'pontos' => Processos::PONTOS] as $caminho => $processo) {
        Route::get('/' . $caminho, [FrotaController::class, 'index'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',ver');
        Route::post('/' . $caminho, [FrotaController::class, 'store'])->defaults('recurso', $caminho)->middleware('transporte.processo:' . $processo . ',gravar');
    }
});
