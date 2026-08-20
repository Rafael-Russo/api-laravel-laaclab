<?php

namespace App\Http\Controllers;

use App\Models\RelatoBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatoBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RelatoBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $relato = RelatoBug::create($dados);

        return response()->json($relato, 201);
    }

    public function show(RelatoBug $relatoBug): JsonResponse
    {
        return response()->json($relatoBug);
    }

    public function update(Request $request, RelatoBug $relatoBug): JsonResponse
    {
        $dados = $request->validate($this->regras($relatoBug));

        $relatoBug->update($dados);

        return response()->json($relatoBug);
    }

    public function destroy(RelatoBug $relatoBug): JsonResponse
    {
        $relatoBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?RelatoBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'titulo' => "$obrigatorio|string|max:100",
            'descricao' => "$obrigatorio|string|max:5000",
            'severidade' => "$obrigatorio|string|max:20",
            'origem' => "$obrigatorio|string|max:50",
        ];
    }
}
