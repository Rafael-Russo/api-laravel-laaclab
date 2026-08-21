<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    /**
     * Sem unique() no randomElement: a tabela nao tem constraint de
     * unicidade, e a lista finita estouraria com poucos registros.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->randomElement([
                'Geral', 'Bugs e travamentos', 'Desempenho', 'Suporte',
                'Off-topic', 'Guias e dicas', 'Novidades',
            ]),
        ];
    }
}
