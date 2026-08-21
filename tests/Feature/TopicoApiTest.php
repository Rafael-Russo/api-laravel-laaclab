<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_topicos(): void
    {
        Topico::factory()->count(3)->create();

        $this->getJson('/api/topicos')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_topico_e_retorna_201(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = Categoria::factory()->create();

        $this->postJson('/api/topicos', [
            'usuario_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'titulo' => 'O jogo trava ao carregar a fase 3',
        ])
            ->assertCreated()
            ->assertJsonPath('usuario_id', $usuario->id)
            ->assertJsonPath('categoria_id', $categoria->id)
            ->assertJsonPath('titulo', 'O jogo trava ao carregar a fase 3');

        $this->assertDatabaseHas('topicos', ['usuario_id' => $usuario->id]);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/topicos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['usuario_id', 'categoria_id', 'titulo']);
    }

    public function test_store_com_usuario_inexistente_retorna_422(): void
    {
        $categoria = Categoria::factory()->create();

        $this->postJson('/api/topicos', [
            'usuario_id' => 999999,
            'categoria_id' => $categoria->id,
            'titulo' => 'Titulo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario_id');
    }

    public function test_store_com_categoria_inexistente_retorna_422(): void
    {
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/topicos', [
            'usuario_id' => $usuario->id,
            'categoria_id' => 999999,
            'titulo' => 'Titulo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('categoria_id');
    }

    public function test_store_com_titulo_longo_demais_retorna_422(): void
    {
        $usuario = Usuario::factory()->create();
        $categoria = Categoria::factory()->create();

        $this->postJson('/api/topicos', [
            'usuario_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'titulo' => str_repeat('a', 101),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('titulo');
    }

    public function test_apagar_usuario_apaga_seus_topicos_em_cascata(): void
    {
        $topico = Topico::factory()->create();

        Usuario::findOrFail($topico->usuario_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
    }

    public function test_apagar_categoria_apaga_seus_topicos_em_cascata(): void
    {
        $topico = Topico::factory()->create();

        Categoria::findOrFail($topico->categoria_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
    }

    public function test_o_usuario_expoe_seus_topicos(): void
    {
        $topico = Topico::factory()->create();

        $usuario = Usuario::with('topicos')->findOrFail($topico->usuario_id);

        $this->assertCount(1, $usuario->topicos);
        $this->assertSame($topico->id, $usuario->topicos->first()->id);
    }

    public function test_show_retorna_o_topico(): void
    {
        $topico = Topico::factory()->create();

        $this->getJson("/api/topicos/{$topico->id}")
            ->assertOk()
            ->assertJsonPath('id', $topico->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/topicos/999')->assertNotFound();
    }

    public function test_update_altera_o_titulo(): void
    {
        $topico = Topico::factory()->create(['titulo' => 'Titulo antigo']);

        $this->putJson("/api/topicos/{$topico->id}", ['titulo' => 'Titulo novo'])
            ->assertOk()
            ->assertJsonPath('titulo', 'Titulo novo');

        $this->assertDatabaseHas('topicos', [
            'id' => $topico->id,
            'titulo' => 'Titulo novo',
        ]);
    }

    public function test_update_troca_a_categoria(): void
    {
        $topico = Topico::factory()->create();
        $outra = Categoria::factory()->create();

        $this->putJson("/api/topicos/{$topico->id}", ['categoria_id' => $outra->id])
            ->assertOk()
            ->assertJsonPath('categoria_id', $outra->id);

        $this->assertDatabaseHas('topicos', [
            'id' => $topico->id,
            'categoria_id' => $outra->id,
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/topicos/999', ['titulo' => 'x'])->assertNotFound();
    }

    public function test_destroy_remove_o_topico_e_retorna_204(): void
    {
        $topico = Topico::factory()->create();

        $this->deleteJson("/api/topicos/{$topico->id}")->assertNoContent();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/topicos/999')->assertNotFound();
    }
}
