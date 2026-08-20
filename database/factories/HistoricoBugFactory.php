<?php

namespace Database\Factories;

use App\Models\HistoricoBug;
use App\Models\Jogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricoBug>
 */
class HistoricoBugFactory extends Factory
{
    protected $model = HistoricoBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'quantidade_crash' => fake()->numberBetween(0, 500),
            'quantidade_bug' => fake()->numberBetween(0, 500),
            'quantidade_fps_drop' => fake()->numberBetween(0, 500),
            'quantidade_stutter' => fake()->numberBetween(0, 500),
        ];
    }
}
