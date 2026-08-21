<?php

namespace Tests\Feature;

use App\Models\HistoricoBug;
use App\Models\Jogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricoBugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_registros(): void
    {
        HistoricoBug::factory()->count(3)->create();

        $this->getJson('/api/historico_bug')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_registro_e_retorna_201(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/historico_bug', [
            'jogo_id' => $jogo->id,
            'quantidade_crash' => 12,
            'quantidade_bug' => 47,
            'quantidade_fps_drop' => 8,
            'quantidade_stutter' => 3,
        ])
            ->assertCreated()
            ->assertJsonPath('quantidade_crash', 12)
            ->assertJsonPath('quantidade_stutter', 3);

        $this->assertDatabaseHas('historico_bug', ['jogo_id' => $jogo->id]);
    }

    public function test_o_timestamp_de_criacao_se_chama_registrado_em(): void
    {
        $registro = HistoricoBug::factory()->create();

        $this->assertNotNull($registro->registrado_em);
        $this->assertNotNull($registro->atualizado_em);

        $this->getJson("/api/historico_bug/{$registro->id}")
            ->assertOk()
            ->assertJsonMissingPath('criado_em')
            ->assertJsonPath('registrado_em', $registro->registrado_em->toJSON());
    }

    public function test_o_mesmo_jogo_pode_ter_varios_registros(): void
    {
        $registro = HistoricoBug::factory()->create();

        $this->postJson('/api/historico_bug', [
            'jogo_id' => $registro->jogo_id,
            'quantidade_crash' => 0,
            'quantidade_bug' => 1,
            'quantidade_fps_drop' => 0,
            'quantidade_stutter' => 0,
        ])->assertCreated();

        $this->assertDatabaseCount('historico_bug', 2);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/historico_bug', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'jogo_id',
                'quantidade_crash',
                'quantidade_bug',
                'quantidade_fps_drop',
                'quantidade_stutter',
            ]);
    }

    public function test_store_com_jogo_inexistente_retorna_422(): void
    {
        $this->postJson('/api/historico_bug', [
            'jogo_id' => 999999,
            'quantidade_crash' => 1,
            'quantidade_bug' => 1,
            'quantidade_fps_drop' => 1,
            'quantidade_stutter' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_store_rejeita_quantidade_negativa(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/historico_bug', [
            'jogo_id' => $jogo->id,
            'quantidade_crash' => -1,
            'quantidade_bug' => 0,
            'quantidade_fps_drop' => 0,
            'quantidade_stutter' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantidade_crash');
    }

    public function test_apagar_jogo_apaga_o_historico_em_cascata(): void
    {
        $registro = HistoricoBug::factory()->create();

        Jogo::findOrFail($registro->jogo_id)->delete();

        $this->assertDatabaseMissing('historico_bug', ['id' => $registro->id]);
    }

    public function test_o_jogo_expoe_o_seu_historico(): void
    {
        $registro = HistoricoBug::factory()->create();

        $jogo = Jogo::with('historicoBug')->findOrFail($registro->jogo_id);

        $this->assertCount(1, $jogo->historicoBug);
        $this->assertSame($registro->id, $jogo->historicoBug->first()->id);
    }

    public function test_show_retorna_o_registro(): void
    {
        $registro = HistoricoBug::factory()->create();

        $this->getJson("/api/historico_bug/{$registro->id}")
            ->assertOk()
            ->assertJsonPath('id', $registro->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/historico_bug/999')->assertNotFound();
    }

    public function test_update_altera_as_contagens(): void
    {
        $registro = HistoricoBug::factory()->create(['quantidade_crash' => 5]);

        $this->putJson("/api/historico_bug/{$registro->id}", ['quantidade_crash' => 99])
            ->assertOk()
            ->assertJsonPath('quantidade_crash', 99);

        $this->assertDatabaseHas('historico_bug', [
            'id' => $registro->id,
            'quantidade_crash' => 99,
        ]);
    }

    public function test_update_troca_o_jogo(): void
    {
        $registro = HistoricoBug::factory()->create();
        $outroJogo = Jogo::factory()->create();

        $this->putJson("/api/historico_bug/{$registro->id}", ['jogo_id' => $outroJogo->id])
            ->assertOk()
            ->assertJsonPath('jogo_id', $outroJogo->id);

        $this->assertDatabaseHas('historico_bug', [
            'id' => $registro->id,
            'jogo_id' => $outroJogo->id,
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/historico_bug/999', ['quantidade_crash' => 1])
            ->assertNotFound();
    }

    public function test_destroy_remove_o_registro_e_retorna_204(): void
    {
        $registro = HistoricoBug::factory()->create();

        $this->deleteJson("/api/historico_bug/{$registro->id}")->assertNoContent();

        $this->assertDatabaseMissing('historico_bug', ['id' => $registro->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/historico_bug/999')->assertNotFound();
    }
}
