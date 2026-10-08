<?php

namespace iEducar\Packages\BuritiTransporteEscolar\Http\Controllers;

use App\Support\Tenancy\PackageTenant;
use iEducar\Packages\BuritiTransporteEscolar\Providers\TransporteEscolarServiceProvider;
use iEducar\Packages\BuritiTransporteEscolar\Support\NomeExibicao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificacaoPublicaController
{
    public function cadastro(): View
    {
        $this->liberado();

        return view('transporte-escolar::avisos.cadastro', [
            'chave' => (string) DB::table('transporte_escolar.configuracao')->where('chave', 'vapid_publica')->value('valor'),
            'aluno' => null,
            'erro' => null,
        ]);
    }

    public function identificar(Request $request): View
    {
        $this->liberado();
        $dados = $request->validate([
            'matricula' => ['required', 'integer'],
            'nascimento' => ['required', 'date'],
        ]);
        $aluno = $this->aluno((int) $dados['matricula'], (string) $dados['nascimento']);

        return view('transporte-escolar::avisos.cadastro', [
            'chave' => (string) DB::table('transporte_escolar.configuracao')->where('chave', 'vapid_publica')->value('valor'),
            'aluno' => $aluno,
            'matricula' => (int) $dados['matricula'],
            'nascimento' => (string) $dados['nascimento'],
            'erro' => $aluno === null ? 'Matrícula e data de nascimento não conferem com um aluno em transporte.' : null,
        ]);
    }

    public function inscrever(Request $request): JsonResponse
    {
        $this->liberado();
        $dados = $request->validate([
            'matricula' => ['required', 'integer'],
            'nascimento' => ['required', 'date'],
            'endpoint' => ['required', 'string', 'max:500'],
            'p256dh' => ['nullable', 'string', 'max:200'],
            'auth' => ['nullable', 'string', 'max:80'],
        ]);
        $aluno = $this->aluno((int) $dados['matricula'], (string) $dados['nascimento']);

        if ($aluno === null) {
            return response()->json(['erro' => 'Cadastro não conferido.'], 422);
        }

        $token = Str::random(40);
        DB::table('transporte_escolar.notificacao_inscricao')->updateOrInsert(
            ['endpoint' => $dados['endpoint']],
            [
                'token' => $token,
                'ref_cod_aluno' => $aluno->cod_aluno,
                'chave_p256dh' => $dados['p256dh'] ?? null,
                'chave_auth' => $dados['auth'] ?? null,
                'ativo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return response()->json(['token' => $token]);
    }

    public function pendente(Request $request): JsonResponse
    {
        $this->liberado();
        $token = (string) $request->query('token', '');
        $inscricao = DB::table('transporte_escolar.notificacao_inscricao')
            ->where('token', $token)
            ->where('ativo', true)
            ->first();

        if ($inscricao === null) {
            return response()->json(['titulo' => null]);
        }

        $fila = DB::table('transporte_escolar.notificacao_fila as f')
            ->join('transporte_escolar.aviso_familia as a', 'a.id', '=', 'f.aviso_id')
            ->where('f.inscricao_id', $inscricao->id)
            ->whereNull('f.entregue_em')
            ->orderBy('f.id')
            ->first(['f.id', 'f.titulo', 'f.corpo', 'a.token']);

        if ($fila === null) {
            return response()->json(['titulo' => null]);
        }

        DB::table('transporte_escolar.notificacao_fila')->where('id', $fila->id)->update(['entregue_em' => now()]);

        return response()->json([
            'titulo' => $fila->titulo,
            'corpo' => $fila->corpo,
            'url' => url('/transporte-escolar/aviso/' . $fila->token),
        ]);
    }

    public function worker(): Response
    {
        $js = <<<'JS'
self.addEventListener('message', function (event) {
    if (!event.data || !event.data.token) return;
    caches.open('te-aviso').then(function (cache) {
        cache.put('/transporte-escolar/avisos/token-local', new Response(event.data.token));
    });
});
self.addEventListener('push', function (event) {
    event.waitUntil(entregar());
});
self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = event.notification.data && event.notification.data.url;
    if (url) event.waitUntil(clients.openWindow(url));
});
function entregar() {
    return caches.open('te-aviso').then(function (cache) {
        return cache.match('/transporte-escolar/avisos/token-local');
    }).then(function (salvo) {
        return salvo ? salvo.text() : '';
    }).then(function (token) {
        return fetch('/transporte-escolar/avisos/pendente?token=' + encodeURIComponent(token));
    }).then(function (resposta) {
        return resposta.json();
    }).then(function (dados) {
        if (!dados || !dados.titulo) return;
        return self.registration.showNotification(dados.titulo, { body: dados.corpo || '', data: { url: dados.url || '/' } });
    });
}
JS;

        return response($js, 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
    }

    private function liberado(): void
    {
        if (!PackageTenant::allows(TransporteEscolarServiceProvider::PACKAGE)) {
            abort(404);
        }
    }

    private function aluno(int $matricula, string $nascimento): ?object
    {
        $aluno = DB::table('pmieducar.matricula as m')
            ->join('pmieducar.aluno as a', 'a.cod_aluno', '=', 'm.ref_cod_aluno')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->join('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->join('transporte_escolar.aluno_rota as ar', function ($join) {
                $join->on('ar.ref_cod_aluno', '=', 'a.cod_aluno')->on('ar.ano', '=', 'm.ano');
            })
            ->where('m.cod_matricula', $matricula)
            ->where('m.ativo', 1)
            ->whereDate('f.data_nasc', $nascimento)
            ->first(['a.cod_aluno', 'p.nome']);

        if ($aluno === null) {
            return null;
        }

        $aluno->primeiro = NomeExibicao::exibir((string) $aluno->nome, false);

        return $aluno;
    }
}
