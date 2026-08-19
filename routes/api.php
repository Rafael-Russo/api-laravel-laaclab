<?php

use App\Http\Controllers\JogoController;
use App\Http\Controllers\PlataformaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// As rotas deste arquivo ja recebem o prefixo /api automaticamente.
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('jogos', JogoController::class);
Route::apiResource('plataformas', PlataformaController::class);
