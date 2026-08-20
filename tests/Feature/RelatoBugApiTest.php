<?php

namespace Tests\Feature;

use App\Models\Jogo;
use App\Models\RelatoBug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatoBugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_relatos(): void
    {
        RelatoBug::factory()->count(3)->create();

        $this->getJson('/api/relatos_bug')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_relato_e_retorna_201(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/relatos_bug', [
            'jogo_id' => $jogo->id,
            'titulo' => 'Crash ao abrir o inventario',
            'descricao' => 'O jogo fecha sozinho ao abrir o inventario na fase 3.',
            'severidade' => 'critica',
            'origem' => 'relato de usuario',
        ])
            ->assertCreated()
            ->assertJsonPath('titulo', 'Crash ao abrir o inventario')
            ->assertJsonPath('origem', 'relato de usuario');

        $this->assertDatabaseHas('relatos_bug', ['jogo_id' => $jogo->id]);
    }

    public function test_o_mesmo_jogo_pode_ter_varios_relatos(): void
    {
        $relato = RelatoBug::factory()->create();

        $this->postJson('/api/relatos_bug', [
            'jogo_id' => $relato->jogo_id,
            'titulo' => 'Outro problema',
            'descricao' => 'Textura sumindo no mapa aberto.',
            'severidade' => 'baixa',
            'origem' => 'telemetria',
        ])->assertCreated();
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/relatos_bug', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jogo_id', 'titulo', 'descricao', 'severidade', 'origem']);
    }

    public function test_store_com_jogo_inexistente_retorna_422(): void
    {
        $this->postJson('/api/relatos_bug', [
            'jogo_id' => 999999,
            'titulo' => 'Titulo',
            'descricao' => 'Descricao',
            'severidade' => 'alta',
            'origem' => 'telemetria',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_store_rejeita_titulo_longo_demais(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/relatos_bug', [
            'jogo_id' => $jogo->id,
            'titulo' => str_repeat('a', 101),
            'descricao' => 'Descricao',
            'severidade' => 'alta',
            'origem' => 'telemetria',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('titulo');
    }

    public function test_apagar_jogo_apaga_os_relatos_em_cascata(): void
    {
        $relato = RelatoBug::factory()->create();

        Jogo::findOrFail($relato->jogo_id)->delete();

        $this->assertDatabaseMissing('relatos_bug', ['id' => $relato->id]);
    }

    public function test_o_jogo_expoe_seus_relatos(): void
    {
        $relato = RelatoBug::factory()->create();

        $jogo = Jogo::with('relatosBug')->findOrFail($relato->jogo_id);

        $this->assertCount(1, $jogo->relatosBug);
        $this->assertSame($relato->id, $jogo->relatosBug->first()->id);
    }

    public function test_show_retorna_o_relato(): void
    {
        $relato = RelatoBug::factory()->create();

        $this->getJson("/api/relatos_bug/{$relato->id}")
            ->assertOk()
            ->assertJsonPath('id', $relato->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/relatos_bug/999')->assertNotFound();
    }

    public function test_update_altera_a_severidade(): void
    {
        $relato = RelatoBug::factory()->create(['severidade' => 'baixa']);

        $this->putJson("/api/relatos_bug/{$relato->id}", ['severidade' => 'critica'])
            ->assertOk()
            ->assertJsonPath('severidade', 'critica');

        $this->assertDatabaseHas('relatos_bug', [
            'id' => $relato->id,
            'severidade' => 'critica',
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/relatos_bug/999', ['severidade' => 'alta'])
            ->assertNotFound();
    }

    public function test_destroy_remove_o_relato_e_retorna_204(): void
    {
        $relato = RelatoBug::factory()->create();

        $this->deleteJson("/api/relatos_bug/{$relato->id}")->assertNoContent();

        $this->assertDatabaseMissing('relatos_bug', ['id' => $relato->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/relatos_bug/999')->assertNotFound();
    }
}
