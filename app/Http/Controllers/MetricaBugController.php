<?php

namespace App\Http\Controllers;

use App\Models\MetricaBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricaBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(MetricaBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $metrica = MetricaBug::create($dados);

        return response()->json($metrica, 201);
    }

    public function show(MetricaBug $metricaBug): JsonResponse
    {
        return response()->json($metricaBug);
    }

    public function update(Request $request, MetricaBug $metricaBug): JsonResponse
    {
        $dados = $request->validate($this->regras($metricaBug));

        $metricaBug->update($dados);

        return response()->json($metricaBug);
    }

    public function destroy(MetricaBug $metricaBug): JsonResponse
    {
        $metricaBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?MetricaBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'tipo' => "$obrigatorio|string|max:20",
            'severidade' => "$obrigatorio|string|max:20",
            // Porcentagem: o DDL so diz INT, mas fora de 0-100 nao significa nada.
            'porcentagem' => "$obrigatorio|integer|min:0|max:100",
        ];
    }
}
