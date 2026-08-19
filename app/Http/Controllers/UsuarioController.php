<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Usuario::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome_usuario' => 'required|string|max:50|unique:usuarios,nome_usuario',
            'email'        => 'required|email|max:100|unique:usuarios,email',
            'senha'        => 'required|string|min:8',
            'idade'        => 'nullable|integer|min:0|max:150',
            'avatar_url'   => 'nullable|string',
            'bio'          => 'nullable|string',
            'nivel'        => 'sometimes|integer|min:1',
        ]);

        $usuario = Usuario::create($this->trocaSenhaPelaColuna($dados));

        return response()->json($usuario, 201);
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json($usuario);
    }

    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $dados = $request->validate([
            'nome_usuario' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('usuarios', 'nome_usuario')->ignore($usuario),
            ],
            'email' => [
                'sometimes', 'email', 'max:100',
                Rule::unique('usuarios', 'email')->ignore($usuario),
            ],
            'senha'      => 'sometimes|string|min:8',
            'idade'      => 'nullable|integer|min:0|max:150',
            'avatar_url' => 'nullable|string',
            'bio'        => 'nullable|string',
            'nivel'      => 'sometimes|integer|min:1',
        ]);

        $usuario->update($this->trocaSenhaPelaColuna($dados));

        return response()->json($usuario);
    }

    public function destroy(Usuario $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json(null, 204);
    }

    /**
     * A API recebe "senha" em texto puro; a coluna e "senha_hash".
     * O hash em si e aplicado pelo cast "hashed" do model.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function trocaSenhaPelaColuna(array $dados): array
    {
        if (isset($dados['senha'])) {
            $dados['senha_hash'] = $dados['senha'];
            unset($dados['senha']);
        }

        return $dados;
    }
}
