<?php

namespace Tests\Feature;

use App\Models\Jogo;
use App\Models\MetricaBug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricaBugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_as_metricas(): void
    {
        MetricaBug::factory()->count(3)->create();

        $this->getJson('/api/metricas_bug')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_metrica_e_retorna_201(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/metricas_bug', [
            'jogo_id' => $jogo->id,
            'tipo' => 'crash',
            'severidade' => 'alta',
            'porcentagem' => 37,
        ])
            ->assertCreated()
            ->assertJsonPath('tipo', 'crash')
            ->assertJsonPath('porcentagem', 37);

        $this->assertDatabaseHas('metricas_bug', ['jogo_id' => $jogo->id]);
    }

    public function test_o_mesmo_jogo_pode_ter_varias_metricas(): void
    {
        $metrica = MetricaBug::factory()->create();

        $this->postJson('/api/metricas_bug', [
            'jogo_id' => $metrica->jogo_id,
            'tipo' => 'fps_drop',
            'severidade' => 'baixa',
            'porcentagem' => 5,
        ])->assertCreated();

        $this->assertDatabaseCount('metricas_bug', 2);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/metricas_bug', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jogo_id', 'tipo', 'severidade', 'porcentagem']);
    }

    public function test_store_com_jogo_inexistente_retorna_422(): void
    {
        $this->postJson('/api/metricas_bug', [
            'jogo_id' => 999999,
            'tipo' => 'crash',
            'severidade' => 'alta',
            'porcentagem' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_store_rejeita_porcentagem_acima_de_cem(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/metricas_bug', [
            'jogo_id' => $jogo->id,
            'tipo' => 'crash',
            'severidade' => 'alta',
            'porcentagem' => 101,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('porcentagem');
    }

    public function test_store_rejeita_porcentagem_negativa(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/metricas_bug', [
            'jogo_id' => $jogo->id,
            'tipo' => 'crash',
            'severidade' => 'alta',
            'porcentagem' => -1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('porcentagem');
    }

    public function test_apagar_jogo_apaga_as_metricas_em_cascata(): void
    {
        $metrica = MetricaBug::factory()->create();

        Jogo::findOrFail($metrica->jogo_id)->delete();

        $this->assertDatabaseMissing('metricas_bug', ['id' => $metrica->id]);
    }

    public function test_o_jogo_expoe_suas_metricas(): void
    {
        $metrica = MetricaBug::factory()->create();

        $jogo = Jogo::with('metricasBug')->findOrFail($metrica->jogo_id);

        $this->assertCount(1, $jogo->metricasBug);
        $this->assertSame($metrica->id, $jogo->metricasBug->first()->id);
    }

    public function test_show_retorna_a_metrica(): void
    {
        $metrica = MetricaBug::factory()->create();

        $this->getJson("/api/metricas_bug/{$metrica->id}")
            ->assertOk()
            ->assertJsonPath('id', $metrica->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/metricas_bug/999')->assertNotFound();
    }

    public function test_update_altera_a_porcentagem(): void
    {
        $metrica = MetricaBug::factory()->create(['porcentagem' => 10]);

        $this->putJson("/api/metricas_bug/{$metrica->id}", ['porcentagem' => 80])
            ->assertOk()
            ->assertJsonPath('porcentagem', 80);

        $this->assertDatabaseHas('metricas_bug', [
            'id' => $metrica->id,
            'porcentagem' => 80,
        ]);
    }

    public function test_update_troca_o_jogo(): void
    {
        $registro = MetricaBug::factory()->create();
        $outroJogo = Jogo::factory()->create();

        $this->putJson("/api/metricas_bug/{$registro->id}", ['jogo_id' => $outroJogo->id])
            ->assertOk()
            ->assertJsonPath('jogo_id', $outroJogo->id);

        $this->assertDatabaseHas('metricas_bug', [
            'id' => $registro->id,
            'jogo_id' => $outroJogo->id,
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/metricas_bug/999', ['porcentagem' => 1])
            ->assertNotFound();
    }

    public function test_destroy_remove_a_metrica_e_retorna_204(): void
    {
        $metrica = MetricaBug::factory()->create();

        $this->deleteJson("/api/metricas_bug/{$metrica->id}")->assertNoContent();

        $this->assertDatabaseMissing('metricas_bug', ['id' => $metrica->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/metricas_bug/999')->assertNotFound();
    }
}
