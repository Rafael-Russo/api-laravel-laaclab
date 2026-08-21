<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_as_categorias(): void
    {
        Categoria::factory()->count(3)->create();

        $this->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_categoria_e_retorna_201(): void
    {
        $this->postJson('/api/categorias', ['nome' => 'Bugs e travamentos'])
            ->assertCreated()
            ->assertJsonPath('nome', 'Bugs e travamentos');

        $this->assertDatabaseHas('categorias', ['nome' => 'Bugs e travamentos']);
    }

    public function test_store_sem_nome_retorna_422(): void
    {
        $this->postJson('/api/categorias', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nome');
    }

    public function test_store_com_nome_longo_demais_retorna_422(): void
    {
        $this->postJson('/api/categorias', ['nome' => str_repeat('a', 51)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nome');
    }

    public function test_duas_categorias_podem_ter_o_mesmo_nome(): void
    {
        Categoria::factory()->create(['nome' => 'Geral']);

        // O DDL nao tem unique aqui, e a spec nao acrescenta um.
        $this->postJson('/api/categorias', ['nome' => 'Geral'])
            ->assertCreated();

        $this->assertDatabaseCount('categorias', 2);
    }

    public function test_show_retorna_a_categoria(): void
    {
        $categoria = Categoria::factory()->create();

        $this->getJson("/api/categorias/{$categoria->id}")
            ->assertOk()
            ->assertJsonPath('id', $categoria->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/categorias/999')->assertNotFound();
    }

    public function test_update_altera_a_categoria(): void
    {
        $categoria = Categoria::factory()->create(['nome' => 'Geral']);

        $this->putJson("/api/categorias/{$categoria->id}", ['nome' => 'Off-topic'])
            ->assertOk()
            ->assertJsonPath('nome', 'Off-topic');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Off-topic',
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/categorias/999', ['nome' => 'x'])->assertNotFound();
    }

    public function test_destroy_remove_a_categoria_e_retorna_204(): void
    {
        $categoria = Categoria::factory()->create();

        $this->deleteJson("/api/categorias/{$categoria->id}")->assertNoContent();

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/categorias/999')->assertNotFound();
    }
}
