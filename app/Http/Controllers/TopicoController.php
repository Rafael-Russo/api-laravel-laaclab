<?php

namespace App\Http\Controllers;

use App\Models\Topico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Topico::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $topico = Topico::create($dados);

        return response()->json($topico, 201);
    }

    public function show(Topico $topico): JsonResponse
    {
        return response()->json($topico);
    }

    public function update(Request $request, Topico $topico): JsonResponse
    {
        $dados = $request->validate($this->regras($topico));

        $topico->update($dados);

        return response()->json($topico);
    }

    public function destroy(Topico $topico): JsonResponse
    {
        $topico->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?Topico $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'usuario_id' => "$obrigatorio|integer|exists:usuarios,id",
            'categoria_id' => "$obrigatorio|integer|exists:categorias,id",
            'titulo' => "$obrigatorio|string|max:100",
        ];
    }
}
