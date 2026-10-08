<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Middleware;

use Closure;
use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigeProcesso
{
    public function handle(Request $request, Closure $next, string $processo, string $acao = 'ver'): Response
    {
        if (!\App\Support\Tenancy\PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE)) {
            abort(404);
        }

        if (!Acesso::permite((int) $processo, $acao)) {
            abort(403, 'Seu tipo de usuário não tem esta permissão no Transporte escolar.');
        }

        return $next($request);
    }
}
