<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topico>
 */
class TopicoFactory extends Factory
{
    protected $model = Topico::class;

    /**
     * As FKs sao factories, nao ids fixos: assim Topico::factory()->create()
     * funciona sem preparacao previa.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'categoria_id' => Categoria::factory(),
            'titulo' => fake()->sentence(6),
        ];
    }
}
