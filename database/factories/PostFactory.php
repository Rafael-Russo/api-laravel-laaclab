<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * topico_id e usuario_id sao factories independentes, entao o autor do
     * topico e o autor do post sao usuarios distintos — o que e o que permite
     * distinguir a cascata de um salto da de dois.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topico_id' => Topico::factory(),
            'usuario_id' => Usuario::factory(),
            'conteudo' => fake()->paragraph(),
        ];
    }
}
