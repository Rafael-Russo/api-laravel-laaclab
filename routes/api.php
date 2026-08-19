<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// As rotas deste arquivo ja recebem o prefixo /api automaticamente.
Route::apiResource('usuarios', UsuarioController::class);
