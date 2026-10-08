<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Middleware;

use App\Menu;
use App\Services\MenuCacheService;
use Closure;
use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        $this->compartilhaMenu((int) $processo);

        return $next($request);
    }

    private function compartilhaMenu(int $processo): void
    {
        $cache = app(MenuCacheService::class);
        $menu = $cache->getMenuByUser(Auth::user());
        $processMenu = $cache->getProcessMenuData($processo);

        if ($processMenu !== false) {
            View::share([
                'mainmenu' => $processMenu['root_id'],
                'currentMenu' => (new Menu)->newFromBuilder($processMenu['attributes']),
                'menuPaths' => $processMenu['ancestor_ids'],
            ]);
        }

        View::share([
            'menu' => $menu,
            'root' => $processMenu === false ? null : $processMenu['root_id'],
            'title' => 'Transporte escolar',
        ]);
    }
}
