<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal público (família) na home/login
    |--------------------------------------------------------------------------
    |
    | aparecer_na_home: exibe botão na tela de login para usuários externos.
    | exigir_processo_aberto: só mostra se houver processo de vaga aberto.
    |
    */
    'public' => [
        'aparecer_na_home' => (bool) env('TRANSPORTE_APARECER_NA_HOME', true),
        'exigir_processo_aberto' => (bool) env('TRANSPORTE_EXIGIR_PROCESSO_ABERTO', true),
        'titulo' => env('TRANSPORTE_HOME_TITULO', 'Transporte'),
        'ordem' => (int) env('TRANSPORTE_HOME_ORDEM', 20),
    ],

];
