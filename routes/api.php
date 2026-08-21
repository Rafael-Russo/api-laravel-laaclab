<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\BibliotecaUsuarioController;
use App\Http\Controllers\BugometroStatusController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CurtidaAvaliacaoController;
use App\Http\Controllers\HistoricoBugController;
use App\Http\Controllers\JogoController;
use App\Http\Controllers\JogoPlataformaController;
use App\Http\Controllers\MetricaBugController;
use App\Http\Controllers\PlataformaController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RelatoBugController;
use App\Http\Controllers\TopicoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// As rotas deste arquivo ja recebem o prefixo /api automaticamente.

// O parametro de rota e sempre declarado explicitamente com ->parameters():
// o pluralizador do Eloquent usado por apiResource() para singularizar o
// nome do recurso e ingles e erra em nomes portugueses (ex.: "avaliacoes"
// viraria "avaliaco"), quebrando o route-model binding. Mesmo motivo pelo
// qual todo model declara "protected $table" explicitamente.
Route::apiResource('usuarios', UsuarioController::class)
    ->parameters(['usuarios' => 'usuario']);
Route::apiResource('jogos', JogoController::class)
    ->parameters(['jogos' => 'jogo']);
Route::apiResource('plataformas', PlataformaController::class)
    ->parameters(['plataformas' => 'plataforma']);
Route::apiResource('jogos_plataformas', JogoPlataformaController::class)
    ->parameters(['jogos_plataformas' => 'jogo_plataforma']);
Route::apiResource('biblioteca_usuario', BibliotecaUsuarioController::class)
    ->parameters(['biblioteca_usuario' => 'biblioteca_usuario']);
Route::apiResource('avaliacoes', AvaliacaoController::class)
    ->parameters(['avaliacoes' => 'avaliacao']);
Route::apiResource('curtidas_avaliacoes', CurtidaAvaliacaoController::class)
    ->parameters(['curtidas_avaliacoes' => 'curtida_avaliacao']);
Route::apiResource('bugometro_status', BugometroStatusController::class)
    ->parameters(['bugometro_status' => 'bugometro_status']);
Route::apiResource('metricas_bug', MetricaBugController::class)
    ->parameters(['metricas_bug' => 'metrica_bug']);
Route::apiResource('relatos_bug', RelatoBugController::class)
    ->parameters(['relatos_bug' => 'relato_bug']);
Route::apiResource('historico_bug', HistoricoBugController::class)
    ->parameters(['historico_bug' => 'historico_bug']);
Route::apiResource('categorias', CategoriaController::class)
    ->parameters(['categorias' => 'categoria']);
Route::apiResource('topicos', TopicoController::class)
    ->parameters(['topicos' => 'topico']);
Route::apiResource('posts', PostController::class)
    ->parameters(['posts' => 'post']);

// Autenticacao. Fica fora de apiResource porque nao e um recurso CRUD: e uma
// unica acao. O frontend Flask chama este endpoint no servidor, nunca no
// browser, para que a senha nao passe pelo JavaScript.
Route::post('login', [AuthController::class, 'login']);
