<?php

namespace App\Http\Controllers;

use App\Models\BugometroStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BugometroStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BugometroStatus::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $status = BugometroStatus::create($dados);

        return response()->json($status, 201);
    }

    public function show(BugometroStatus $bugometroStatus): JsonResponse
    {
        return response()->json($bugometroStatus);
    }

    public function update(Request $request, BugometroStatus $bugometroStatus): JsonResponse
    {
        $dados = $request->validate($this->regras($bugometroStatus));

        $bugometroStatus->update($dados);

        return response()->json($bugometroStatus);
    }

    public function destroy(BugometroStatus $bugometroStatus): JsonResponse
    {
        $bugometroStatus->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update.
     *
     * A unicidade aqui e de coluna unica (relacao 1:1 com jogo), nao de par
     * composto: a regra mora no proprio campo que muda. Por isso este
     * controller nao recebe a Request nem tem completaOPar() — se o update
     * nao envia jogo_id, o "sometimes" pula a checagem, o que e correto,
     * porque ausente significa inalterado e nao pode colidir consigo mesmo.
     *
     * @return array<string, mixed>
     */
    private function regras(?BugometroStatus $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => [
                $obrigatorio, 'integer', 'exists:jogos,id',
                Rule::unique('bugometro_status', 'jogo_id')->ignore($existente),
            ],
            'pontuacao' => 'nullable|integer',
            'status' => 'nullable|string|max:20',
        ];
    }
}
