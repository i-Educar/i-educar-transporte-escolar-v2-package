<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use iEducar\Packages\BuritiTransporteEscolar\Support\Normas;
use Illuminate\View\View;

class NormaController
{
    public function index(): View
    {
        return view('transporte-escolar::normas', ['normas' => Normas::lista()]);
    }
}
