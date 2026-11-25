<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiTransporteEscolar\Support\PermissaoMatriz;
use iEducar\Packages\BuritiTransporteEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'parent_id' => null,
            'title' => 'Transporte',
            'description' => 'Transporte Escolar v2',
            'link' => '/transporte-escolar',
            'icon' => 'fa-bus',
            'order' => 8,
            'type' => 1,
            'active' => true,
        ]);

        $cadastros = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Cadastros'],
            ['order' => 1, 'type' => 2, 'process' => null, 'active' => true],
        );
        $movimentos = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Movimentações'],
            ['order' => 2, 'type' => 2, 'process' => null, 'active' => true],
        );
        $referencia = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Referência'],
            ['order' => 3, 'type' => 2, 'process' => null, 'active' => true],
        );

        $links = [
            [Processos::EMPRESAS, $cadastros->getKey(), 'Empresas', '/transporte-escolar/empresas', 'Rede. A escola só consulta.'],
            [Processos::MOTORISTAS, $cadastros->getKey(), 'Motoristas', '/transporte-escolar/motoristas', 'Rede. A escola só consulta. Salário é recurso próprio.'],
            [Processos::MONITORES, $cadastros->getKey(), 'Monitores', '/transporte-escolar/monitores', 'Rede. A escola só consulta. Salário é recurso próprio.'],
            [Processos::VEICULOS, $cadastros->getKey(), 'Veículos', '/transporte-escolar/veiculos', 'Rede. A escola só consulta.'],
            [Processos::FORNECEDORES, $cadastros->getKey(), 'Fornecedores', '/transporte-escolar/fornecedores', 'Rede. A escola só consulta.'],
            [Processos::PONTOS, $cadastros->getKey(), 'Pontos', '/transporte-escolar/pontos', 'Rede. A escola só consulta.'],
            [Processos::ROTAS, $cadastros->getKey(), 'Rotas', '/transporte-escolar/rotas', 'Institucional grava a rede. Escola vê as rotas das suas escolas.'],
            [Processos::ALUNOS, $movimentos->getKey(), 'Alunos na rota', '/transporte-escolar/alunos', 'Escola grava só aluno da própria matrícula. Institucional vê a rede.'],
            [Processos::MAPA, $movimentos->getKey(), 'Mapa da rota', '/transporte-escolar/rotas', 'Escola consulta. Institucional grava ponto, traçado e GPX.'],
            [Processos::COPIA, $movimentos->getKey(), 'Cópia do ano', '/transporte-escolar/copia-ano', 'Só perfil institucional.'],
            [Processos::SETE, $movimentos->getKey(), 'Importar SETE', '/transporte-escolar/sete', 'Só perfil institucional.'],
            [Processos::DIVERGENCIA, $movimentos->getKey(), 'Divergências do SETE', '/transporte-escolar/sete', 'Institucional vê a rede. Escola vê a própria escola.'],
            [Processos::NORMAS, $referencia->getKey(), 'Normas', '/transporte-escolar/normas', 'Consulta para institucional e escola.'],
        ];

        foreach ($links as [$processo, $pai, $titulo, $link, $descricao]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $pai,
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => 0,
                'type' => 3,
                'active' => true,
            ]);
        }

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);

        foreach ($tipos as $tipo) {
            foreach (PermissaoMatriz::paraNivel((int) $tipo->nivel) as $processo => $flags) {
                if (!$flags['visualiza'] && !$flags['cadastra'] && !$flags['exclui']) {
                    continue;
                }

                $menuId = Menu::query()->where('process', $processo)->value('id');

                if ($menuId === null) {
                    continue;
                }

                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    [
                        'visualiza' => $flags['visualiza'] ? 1 : 0,
                        'cadastra' => $flags['cadastra'] ? 1 : 0,
                        'exclui' => $flags['exclui'] ? 1 : 0,
                    ],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();

        if ($raiz === null) {
            return;
        }

        $grupos = Menu::query()->where('parent_id', $raiz->getKey())->pluck('id');
        $folhas = Menu::query()->whereIn('parent_id', $grupos)->pluck('id');
        $ids = $grupos->merge($folhas)->push($raiz->getKey());

        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $folhas)->delete();
        Menu::query()->whereIn('id', $grupos)->delete();
        $raiz->delete();
    }
};
