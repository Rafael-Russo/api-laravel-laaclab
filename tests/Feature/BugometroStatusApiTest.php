<?php

namespace Tests\Feature;

use App\Models\BugometroStatus;
use App\Models\Jogo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugometroStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_status(): void
    {
        BugometroStatus::factory()->count(3)->create();

        $this->getJson('/api/bugometro_status')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_status_e_retorna_201(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/bugometro_status', [
            'jogo_id' => $jogo->id,
            'pontuacao' => 72,
            'status' => 'instavel',
        ])
            ->assertCreated()
            ->assertJsonPath('jogo_id', $jogo->id)
            ->assertJsonPath('pontuacao', 72)
            ->assertJsonPath('status', 'instavel');

        $this->assertDatabaseHas('bugometro_status', ['jogo_id' => $jogo->id]);
    }

    public function test_store_aceita_apenas_o_jogo(): void
    {
        $jogo = Jogo::factory()->create();

        $this->postJson('/api/bugometro_status', ['jogo_id' => $jogo->id])
            ->assertCreated()
            ->assertJsonPath('pontuacao', null)
            ->assertJsonPath('status', null);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/bugometro_status', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_store_com_jogo_inexistente_retorna_422(): void
    {
        $this->postJson('/api/bugometro_status', ['jogo_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_store_rejeita_segundo_status_para_o_mesmo_jogo(): void
    {
        $status = BugometroStatus::factory()->create();

        $this->postJson('/api/bugometro_status', ['jogo_id' => $status->jogo_id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_o_banco_rejeita_segundo_status_como_rede_de_seguranca(): void
    {
        $status = BugometroStatus::factory()->create();

        $this->expectException(QueryException::class);

        BugometroStatus::create(['jogo_id' => $status->jogo_id]);
    }

    public function test_apagar_jogo_apaga_o_status_em_cascata(): void
    {
        $status = BugometroStatus::factory()->create();

        Jogo::findOrFail($status->jogo_id)->delete();

        $this->assertDatabaseMissing('bugometro_status', ['id' => $status->id]);
    }

    public function test_o_jogo_expoe_o_seu_bugometro(): void
    {
        $status = BugometroStatus::factory()->create();

        $jogo = Jogo::with('bugometroStatus')->findOrFail($status->jogo_id);

        $this->assertNotNull($jogo->bugometroStatus);
        $this->assertSame($status->id, $jogo->bugometroStatus->id);
    }

    public function test_show_retorna_o_status(): void
    {
        $status = BugometroStatus::factory()->create();

        $this->getJson("/api/bugometro_status/{$status->id}")
            ->assertOk()
            ->assertJsonPath('id', $status->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/bugometro_status/999')->assertNotFound();
    }

    public function test_update_altera_a_pontuacao_sem_enviar_o_jogo(): void
    {
        $status = BugometroStatus::factory()->create(['pontuacao' => 10]);

        // Nao manda jogo_id de proposito: ausente significa inalterado, e a
        // checagem de unicidade nao precisa rodar.
        $this->putJson("/api/bugometro_status/{$status->id}", ['pontuacao' => 95])
            ->assertOk()
            ->assertJsonPath('pontuacao', 95);

        $this->assertDatabaseHas('bugometro_status', [
            'id' => $status->id,
            'pontuacao' => 95,
        ]);
    }

    public function test_update_para_jogo_que_ja_tem_status_retorna_422(): void
    {
        $status = BugometroStatus::factory()->create();
        $outro = BugometroStatus::factory()->create();

        $this->putJson("/api/bugometro_status/{$status->id}", [
            'jogo_id' => $outro->jogo_id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jogo_id');
    }

    public function test_update_aceita_o_proprio_jogo_sem_conflito(): void
    {
        $status = BugometroStatus::factory()->create();

        $this->putJson("/api/bugometro_status/{$status->id}", [
            'jogo_id' => $status->jogo_id,
            'status' => 'estavel',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'estavel');
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/bugometro_status/999', ['pontuacao' => 1])
            ->assertNotFound();
    }

    public function test_destroy_remove_o_status_e_retorna_204(): void
    {
        $status = BugometroStatus::factory()->create();

        $this->deleteJson("/api/bugometro_status/{$status->id}")->assertNoContent();

        $this->assertDatabaseMissing('bugometro_status', ['id' => $status->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/bugometro_status/999')->assertNotFound();
    }
}
