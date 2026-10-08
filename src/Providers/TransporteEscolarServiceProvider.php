<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Providers;

use iEducar\Packages\BuritiTransporteEscolar\Console\SimulaTransporte;
use iEducar\Packages\BuritiTransporteEscolar\Http\Middleware\ExigeProcesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use iEducar\Packages\BuritiTransporteEscolar\Support\IdentidadeMunicipio;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use iEducar\Packages\BuritiTransporteEscolar\Support\VisaoRede;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TransporteEscolarServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-transporte-escolar-v2';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'transporte-escolar');

        if ($this->app->runningInConsole()) {
            $this->commands([SimulaTransporte::class]);

            if (\App\Support\Tenancy\PackageTenant::allows(self::PACKAGE)) {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            }
        }

        Route::prefix('transporte-escolar/api')->group(function () {
            Route::get('/chamada', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\ChamadaApiController::class, 'pacote']);
            Route::post('/chamada', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\ChamadaApiController::class, 'marcar']);
        });

        Route::get('/transporte-escolar/avisos/cadastro', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoPublicaController::class, 'cadastro']);
        Route::post('/transporte-escolar/avisos/cadastro', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoPublicaController::class, 'identificar']);
        Route::post('/transporte-escolar/avisos/cadastro/inscricao', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoPublicaController::class, 'inscrever']);
        Route::get('/transporte-escolar/avisos/pendente', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoPublicaController::class, 'pendente']);
        Route::get('/transporte-escolar/avisos/sw.js', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\NotificacaoPublicaController::class, 'worker']);

        Route::middleware(['web'])->group(function () {
            Route::get('/transporte-escolar/marca', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\DocumentoController::class, 'marca']);
            Route::get('/transporte-escolar/aviso/{token}', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\AvisoController::class, 'publico']);
            Route::get('/transporte-escolar/familia', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'inicio']);
            Route::get('/transporte-escolar/familia/pedir', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'pedir']);
            Route::post('/transporte-escolar/familia/pedir', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'gravar']);
            Route::get('/transporte-escolar/familia/protocolo', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'protocolo']);
            Route::post('/transporte-escolar/familia/protocolo', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'consultar']);
            Route::post('/transporte-escolar/familia/cancelar', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'cancelar']);
            Route::get('/te/{token}', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'acompanhar']);
            Route::post('/te/{token}', [\iEducar\Packages\BuritiTransporteEscolar\Http\Controllers\FamiliaPortalController::class, 'confirmar']);
        });

        Route::middleware([
            'web',
            'ieducar.navigation',
            'ieducar.footer',
            'ieducar.xssbypass',
            'ieducar.suspended',
            'auth',
            'ieducar.checkresetpassword',
        ])->group(__DIR__ . '/../../routes/web.php');

        $this->app['router']->aliasMiddleware('transporte.processo', ExigeProcesso::class);

        View::composer(['transporte-escolar::layout', 'transporte-escolar::portal.layout'], function ($view): void {
            $view->with('municipio', IdentidadeMunicipio::rotulo());
            $view->with('logoMunicipio', IdentidadeMunicipio::arquivo() ? url('/transporte-escolar/marca') : null);
        });

        View::composer(['transporte-escolar::layout', 'transporte-escolar::painel'], function ($view): void {
            $view->with('visaoRotulo', VisaoRede::rotulo());
            $view->with('menuTransporte', [
                'painel' => Acesso::permite(Processos::RAIZ, 'ver'),
                'rotas' => Acesso::permite(Processos::ROTAS, 'ver'),
                'alunos' => Acesso::permite(Processos::ALUNOS, 'ver'),
                'copia' => Acesso::permite(Processos::COPIA, 'ver'),
                'sete' => Acesso::permite(Processos::SETE, 'ver') || Acesso::permite(Processos::DIVERGENCIA, 'ver'),
                'frota' => Acesso::permite(Processos::VEICULOS, 'ver'),
                'normas' => Acesso::permite(Processos::NORMAS, 'ver'),
                'viagens' => Acesso::permite(Processos::VIAGENS, 'ver'),
                'offline' => Acesso::permite(Processos::OFFLINE, 'ver'),
                'gastos' => Acesso::permite(Processos::GASTOS, 'ver'),
                'relatorios' => Acesso::permite(Processos::RELATORIOS, 'ver'),
                'cacs' => Acesso::permite(Processos::CACS, 'ver'),
                'auditoria' => Acesso::permite(Processos::AUDITORIA, 'ver'),
                'avisos' => Acesso::permite(Processos::AVISOS, 'ver'),
                'avisosModelos' => Acesso::permite(Processos::AVISOS, 'gravar'),
                'solicitacoes' => Acesso::permite(Processos::SOLICITACOES, 'ver'),
            ]);
        });
    }
}
