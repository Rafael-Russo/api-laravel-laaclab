<?php

namespace App\Http\Controllers;

use App\Models\Jogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JogoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Jogo::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome'            => 'required|string|max:100',
            'descricao'       => 'nullable|string',
            'genero'          => 'nullable|string|max:50',
            'classificacao'   => 'nullable|string|max:10',
            'desenvolvedora'  => 'nullable|string|max:100',
            'data_lancamento' => 'nullable|date',
            'capa_url'        => 'nullable|string',
        ]);

        $jogo = Jogo::create($dados);

        return response()->json($jogo, 201);
    }

    public function show(Jogo $jogo): JsonResponse
    {
        return response()->json($jogo);
    }

    public function update(Request $request, Jogo $jogo): JsonResponse
    {
        $dados = $request->validate([
            'nome'            => 'sometimes|string|max:100',
            'descricao'       => 'nullable|string',
            'genero'          => 'nullable|string|max:50',
            'classificacao'   => 'nullable|string|max:10',
            'desenvolvedora'  => 'nullable|string|max:100',
            'data_lancamento' => 'nullable|date',
            'capa_url'        => 'nullable|string',
        ]);

        $jogo->update($dados);

        return response()->json($jogo);
    }

    public function destroy(Jogo $jogo): JsonResponse
    {
        $jogo->delete();

        return response()->json(null, 204);
    }
}
