<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\Jogo;
use App\Models\Plataforma;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    /** E-mail e senha do usuario demo, citados no README e usados pelo frontend. */
    private const DEMO_EMAIL = 'nikola@laac.test';

    private const DEMO_SENHA = 'laaclab123';

    public function test_o_seed_popula_um_catalogo_navegavel(): void
    {
        $this->seed();

        // Menos que isto e o grid da Biblioteca nao da para julgar layout,
        // ordenacao nem paginacao futura.
        $this->assertGreaterThanOrEqual(40, Jogo::count());
    }

    public function test_todo_jogo_semeado_tem_capa_e_genero(): void
    {
        $this->seed();

        // Sem esta linha o teste passaria com o catalogo vazio: zero jogos
        // tambem significa zero capas nulas.
        $this->assertGreaterThan(0, Jogo::count());
        $this->assertSame(0, Jogo::whereNull('capa_url')->count());
        $this->assertSame(0, Jogo::whereNull('genero')->count());
    }

    public function test_os_jogos_estao_ligados_a_plataformas(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(4, Plataforma::count());
        $this->assertSame(0, Jogo::doesntHave('plataformas')->count());
    }

    public function test_o_usuario_demo_tem_biblioteca_com_favoritos(): void
    {
        $this->seed();

        $demo = Usuario::where('email', self::DEMO_EMAIL)->firstOrFail();

        $this->assertGreaterThanOrEqual(20, $demo->jogos()->count());
        $this->assertGreaterThan(0, $demo->jogos()->wherePivot('favorito', true)->count());
    }

    public function test_o_usuario_demo_entra_com_a_senha_documentada(): void
    {
        $this->seed();

        $this->postJson('/api/login', [
            'email' => self::DEMO_EMAIL,
            'senha' => self::DEMO_SENHA,
        ])->assertOk();
    }

    public function test_as_avaliacoes_cruzam_o_corte_de_cinco_pontos(): void
    {
        $this->seed();

        // O frontend separa polegar para cima de para baixo em nota >= 5.0.
        // Um seed inteiro de um lado so esconderia metade da tela de Detalhe.
        $this->assertGreaterThan(0, Avaliacao::where('nota', '>=', 5.0)->count());
        $this->assertGreaterThan(0, Avaliacao::where('nota', '<', 5.0)->count());
    }

    public function test_ha_curtidas_em_avaliacoes(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, \DB::table('curtidas_avaliacoes')->count());
    }

    public function test_semear_duas_vezes_nao_duplica_o_catalogo(): void
    {
        $this->seed();
        $contagem = Jogo::count();
        $this->assertGreaterThan(0, $contagem);

        $this->seed();

        $this->assertSame($contagem, Jogo::count());
    }
}
