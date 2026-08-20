<?php

namespace Database\Factories;

use App\Models\Jogo;
use App\Models\RelatoBug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelatoBug>
 */
class RelatoBugFactory extends Factory
{
    protected $model = RelatoBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'titulo' => fake()->sentence(6),
            'descricao' => fake()->paragraph(),
            'severidade' => fake()->randomElement(['baixa', 'media', 'alta', 'critica']),
            'origem' => fake()->randomElement(['relato de usuario', 'telemetria', 'QA interno']),
        ];
    }
}
