<?php

namespace App\Http\Controllers;

use App\Models\HistoricoBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoricoBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(HistoricoBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $registro = HistoricoBug::create($dados);

        return response()->json($registro, 201);
    }

    public function show(HistoricoBug $historicoBug): JsonResponse
    {
        return response()->json($historicoBug);
    }

    public function update(Request $request, HistoricoBug $historicoBug): JsonResponse
    {
        $dados = $request->validate($this->regras($historicoBug));

        $historicoBug->update($dados);

        return response()->json($historicoBug);
    }

    public function destroy(HistoricoBug $historicoBug): JsonResponse
    {
        $historicoBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?HistoricoBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        // Contagens: o DDL so diz INT, mas contagem negativa nao significa nada.
        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'quantidade_crash' => "$obrigatorio|integer|min:0",
            'quantidade_bug' => "$obrigatorio|integer|min:0",
            'quantidade_fps_drop' => "$obrigatorio|integer|min:0",
            'quantidade_stutter' => "$obrigatorio|integer|min:0",
        ];
    }
}
