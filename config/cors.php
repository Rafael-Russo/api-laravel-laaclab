<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| O frontend Flask roda numa origem diferente da API e busca os dados do
| dominio direto do browser, entao toda tela depende destes cabecalhos.
|
| O padrao do framework libera "*". Trocamos por uma lista explicita vinda do
| ambiente por dois motivos: um curinga nao registra em lugar nenhum quem
| confiamos, e e incompativel com requisicao com credencial — se Sanctum
| entrar um dia, "*" passa a quebrar em vez de apenas ser permissivo.
|
| O default cobre o servidor de desenvolvimento do Flask, para clonar o repo e
| rodar sem configurar nada. Em producao, aponte CORS_ALLOWED_ORIGINS para o
| dominio real; aceita varias origens separadas por virgula.
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:5000,http://127.0.0.1:5000',
        )),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // O Flask guarda a sessao do lado dele e nao manda cookie para a API; a
    // identidade viaja como usuario_id nos parametros. Manter false permite
    // que allowed_origins continue sendo uma lista de valores exatos.
    'supports_credentials' => false,

];
