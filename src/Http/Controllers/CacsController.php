<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CacsController
{
    public function index(Request $request): View
    {
        return app(RelatorioController::class)->index($request);
    }

    public function csv(Request $request): Response
    {
        return app(RelatorioController::class)->csv($request);
    }
}
