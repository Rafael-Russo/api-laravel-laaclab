<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_com_credenciais_validas_devolve_o_usuario(): void
    {
        $usuario = Usuario::factory()->create([
            'nome_usuario' => 'nikola98',
            'email' => 'nikola@laac.test',
            'senha_hash' => 'senha-secreta',
        ]);

        $this->postJson('/api/login', [
            'email' => 'nikola@laac.test',
            'senha' => 'senha-secreta',
        ])
            ->assertOk()
            ->assertJsonPath('id', $usuario->id)
            ->assertJsonPath('nome_usuario', 'nikola98');
    }

    public function test_login_nunca_devolve_o_hash_da_senha(): void
    {
        Usuario::factory()->create([
            'email' => 'nikola@laac.test',
            'senha_hash' => 'senha-secreta',
        ]);

        $this->postJson('/api/login', [
            'email' => 'nikola@laac.test',
            'senha' => 'senha-secreta',
        ])
            ->assertOk()
            ->assertJsonMissingPath('senha_hash');
    }

    public function test_senha_errada_devolve_401(): void
    {
        Usuario::factory()->create([
            'email' => 'nikola@laac.test',
            'senha_hash' => 'senha-secreta',
        ]);

        $this->postJson('/api/login', [
            'email' => 'nikola@laac.test',
            'senha' => 'chute-errado',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_email_inexistente_devolve_401(): void
    {
        $this->postJson('/api/login', [
            'email' => 'ninguem@laac.test',
            'senha' => 'senha-secreta',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_email_inexistente_e_senha_errada_sao_indistinguiveis(): void
    {
        // O endpoint nao pode revelar quais e-mails existem: as duas recusas
        // precisam ser identicas em status e em corpo.
        Usuario::factory()->create([
            'email' => 'nikola@laac.test',
            'senha_hash' => 'senha-secreta',
        ]);

        $senhaErrada = $this->postJson('/api/login', [
            'email' => 'nikola@laac.test',
            'senha' => 'chute-errado',
        ]);

        $emailInexistente = $this->postJson('/api/login', [
            'email' => 'ninguem@laac.test',
            'senha' => 'chute-errado',
        ]);

        $this->assertSame($senhaErrada->status(), $emailInexistente->status());
        $this->assertSame($senhaErrada->json(), $emailInexistente->json());
    }

    public function test_campos_faltando_devolvem_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'senha']);
    }

    public function test_email_malformado_devolve_422(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nao-e-email',
            'senha' => 'senha-secreta',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
