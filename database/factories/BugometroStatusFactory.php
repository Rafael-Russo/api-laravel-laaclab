<?php

namespace Database\Factories;

use App\Models\BugometroStatus;
use App\Models\Jogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugometroStatus>
 */
class BugometroStatusFactory extends Factory
{
    protected $model = BugometroStatus::class;

    /**
     * jogo_id e uma factory, nao um id fixo: como a coluna e unica, cada
     * chamada precisa do seu proprio jogo para nao colidir.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'pontuacao' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['estavel', 'instavel', 'critico', 'injogavel']),
        ];
    }
}
