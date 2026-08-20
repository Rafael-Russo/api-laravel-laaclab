<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Autentica por e-mail e senha.
     *
     * E-mail inexistente e senha errada devolvem a mesma recusa, palavra por
     * palavra: um 401 que distinguisse os dois casos viraria um oraculo de
     * quais e-mails estao cadastrados.
     *
     * A comparacao ainda tem uma diferenca de tempo entre os dois casos (o
     * e-mail inexistente sai antes de calcular o hash). Aceitamos isso: esta
     * API nao tem autenticacao nenhuma nos demais endpoints, entao um canal
     * lateral de tempo aqui nao seria o elo mais fraco. Se Sanctum entrar,
     * este e um dos pontos a revisitar.
     */
    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => 'required|email|max:100',
            'senha' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $dados['email'])->first();

        if ($usuario === null || ! Hash::check($dados['senha'], $usuario->senha_hash)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return response()->json($usuario);
    }
}
