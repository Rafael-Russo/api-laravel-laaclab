<?php

namespace Database\Factories;

use App\Models\Jogo;
use App\Models\MetricaBug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetricaBug>
 */
class MetricaBugFactory extends Factory
{
    protected $model = MetricaBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'tipo' => fake()->randomElement(['crash', 'bug', 'fps_drop', 'stutter']),
            'severidade' => fake()->randomElement(['baixa', 'media', 'alta', 'critica']),
            'porcentagem' => fake()->numberBetween(0, 100),
        ];
    }
}
