<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Post;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_posts(): void
    {
        Post::factory()->count(3)->create();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_post_e_retorna_201(): void
    {
        $topico = Topico::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Acontece comigo tambem, sempre no mesmo ponto.',
        ])
            ->assertCreated()
            ->assertJsonPath('topico_id', $topico->id)
            ->assertJsonPath('conteudo', 'Acontece comigo tambem, sempre no mesmo ponto.');

        $this->assertDatabaseHas('posts', ['topico_id' => $topico->id]);
    }

    public function test_o_mesmo_topico_pode_ter_varios_posts(): void
    {
        $post = Post::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $post->topico_id,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Segunda resposta no mesmo topico.',
        ])->assertCreated();

        $this->assertDatabaseCount('posts', 2);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['topico_id', 'usuario_id', 'conteudo']);
    }

    public function test_store_com_topico_inexistente_retorna_422(): void
    {
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => 999999,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Conteudo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('topico_id');
    }

    public function test_store_com_usuario_inexistente_retorna_422(): void
    {
        $topico = Topico::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => 999999,
            'conteudo' => 'Conteudo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario_id');
    }

    public function test_store_com_conteudo_longo_demais_retorna_422(): void
    {
        $topico = Topico::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => $usuario->id,
            'conteudo' => str_repeat('a', 5001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('conteudo');
    }

    public function test_apagar_topico_apaga_seus_posts_em_cascata(): void
    {
        $post = Post::factory()->create();

        Topico::findOrFail($post->topico_id)->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_apagar_o_autor_do_post_apaga_o_post_mas_nao_o_topico(): void
    {
        $post = Post::factory()->create();

        // A factory da autores distintos ao topico e ao post, entao apagar o
        // autor do post exercita a FK do proprio post, nao a herdada.
        Usuario::findOrFail($post->usuario_id)->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('topicos', ['id' => $post->topico_id]);
    }

    public function test_apagar_o_autor_do_topico_apaga_topico_e_posts(): void
    {
        $post = Post::factory()->create();
        $topico = Topico::findOrFail($post->topico_id);

        // Apaga o autor do TOPICO, nao o do post: a cascata tem que percorrer
        // usuario -> topico -> post.
        Usuario::findOrFail($topico->usuario_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_apagar_a_categoria_apaga_topico_e_posts(): void
    {
        $post = Post::factory()->create();
        $topico = Topico::findOrFail($post->topico_id);

        // O outro caminho de dois saltos: categoria -> topico -> post.
        Categoria::findOrFail($topico->categoria_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_o_topico_expoe_seus_posts(): void
    {
        $post = Post::factory()->create();

        $topico = Topico::with('posts')->findOrFail($post->topico_id);

        $this->assertCount(1, $topico->posts);
        $this->assertSame($post->id, $topico->posts->first()->id);
    }

    public function test_o_usuario_expoe_seus_posts(): void
    {
        $post = Post::factory()->create();

        $usuario = Usuario::with('posts')->findOrFail($post->usuario_id);

        $this->assertCount(1, $usuario->posts);
        $this->assertSame($post->id, $usuario->posts->first()->id);
    }

    public function test_show_retorna_o_post(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('id', $post->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/posts/999')->assertNotFound();
    }

    public function test_update_altera_o_conteudo(): void
    {
        $post = Post::factory()->create(['conteudo' => 'Conteudo antigo']);

        $this->putJson("/api/posts/{$post->id}", ['conteudo' => 'Conteudo novo'])
            ->assertOk()
            ->assertJsonPath('conteudo', 'Conteudo novo');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'conteudo' => 'Conteudo novo',
        ]);
    }

    public function test_update_troca_o_topico(): void
    {
        $post = Post::factory()->create();
        $outro = Topico::factory()->create();

        $this->putJson("/api/posts/{$post->id}", ['topico_id' => $outro->id])
            ->assertOk()
            ->assertJsonPath('topico_id', $outro->id);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'topico_id' => $outro->id,
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/posts/999', ['conteudo' => 'x'])->assertNotFound();
    }

    public function test_destroy_remove_o_post_e_retorna_204(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/posts/999')->assertNotFound();
    }
}
