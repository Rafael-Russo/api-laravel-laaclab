<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGEM_PERMITIDA = 'http://localhost:5000';

    private const ORIGEM_RECUSADA = 'https://malicioso.example';

    public function test_origem_permitida_recebe_o_header_com_a_propria_origem(): void
    {
        // Precisa ecoar a origem, nao "*": um curinga impede que a API algum dia
        // aceite requisicao com credencial, e nao diz nada sobre quem confiamos.
        $this->getJson('/api/jogos', ['Origin' => self::ORIGEM_PERMITIDA])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGEM_PERMITIDA);
    }

    public function test_origem_recusada_nao_recebe_o_header(): void
    {
        $this->getJson('/api/jogos', ['Origin' => self::ORIGEM_RECUSADA])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_preflight_da_origem_permitida_e_aceito(): void
    {
        $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => self::ORIGEM_PERMITIDA,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::ORIGEM_PERMITIDA);
    }

    public function test_a_lista_de_origens_vem_da_configuracao(): void
    {
        // O deploy aponta para o dominio real por env, sem tocar em codigo.
        $this->assertContains(self::ORIGEM_PERMITIDA, config('cors.allowed_origins'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }
}
