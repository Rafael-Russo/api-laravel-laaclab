# LaaC Lab — Fase 3: Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar as quatro entidades do Bugômetro — `bugometro_status`, `metricas_bug`, `relatos_bug` e `historico_bug` — com CRUD REST completo, todas filhas de `jogos`.

**Architecture:** Mesmo padrão das fases anteriores: um `apiResource` por entidade, migration + Model + Factory + Controller, validação inline extraída para `private function regras()`, 404 por route-model binding. O que é novo nesta fase: a primeira relação **1:1** do projeto (`bugometro_status.jogo_id` é único de coluna única, não par composto) e o segundo timestamp irregular (`historico_bug` cria em `registrado_em`).

**Tech Stack:** PHP 8.3, Laravel 13.17, PHPUnit 12.5, SQLite (dev e testes) / MySQL (opcional via `.env`).

**Spec:** [docs/superpowers/specs/2026-08-19-laac-lab-api-design.md](../specs/2026-08-19-laac-lab-api-design.md)

**Fases anteriores:** [Fases 0-1](2026-08-19-laac-lab-fases-0-1.md) e [Fase 2](2026-08-19-laac-lab-fase-2.md). Ponto de partida: 115 testes verdes, 35 rotas sob `/api`, 10 migrations, 7 entidades.

**Pendências conhecidas:** [docs/pendencias.md](../../pendencias.md) — nada ali bloqueia esta fase; a seção D desse documento é a origem de duas das decisões abaixo.

## Global Constraints

- Tabelas e colunas em português, exatamente como no DDL: `bugometro_status`, `metricas_bug`, `relatos_bug`, `historico_bug`.
- Todo Model declara `protected $table` explicitamente — o pluralizador do Eloquent é inglês e não é confiável nesses nomes.
- Todo Model declara `const CREATED_AT` e `const UPDATED_AT`. Nesta fase **`historico_bug` cria em `registrado_em`**, não em `criado_em`.
- Migrations usam **exclusivamente** o Schema Builder. Nenhum `DB::statement`, nenhum SQL cru, nenhum tipo específico de vendor.
- Toda FK usa `foreignId(...)->constrained('tabela')->cascadeOnDelete()`.
- Todo `Route::apiResource` declara `->parameters()` explicitamente (spec §4.2).
- URI de cada recurso = nome exato da tabela em `snake_case`.
- Status HTTP: `index`/`show`/`update` → 200, `store` → 201, `destroy` → 204, ID inexistente → 404, validação falha → 422.
- Todo controller estende `App\Http\Controllers\Controller` e tipa o retorno de todos os métodos como `JsonResponse`.
- **Um commit por entidade de domínio completa** — migration, model, factory, controller, rotas e testes juntos, nunca pela metade.
- **Cada FK nova ganha o seu próprio teste de cascata** — apagar o pai e afirmar que o filho sumiu. A regra é por FK, não por entidade.
- Nenhuma tarefa fecha com `php artisan test` vermelho.

## Decisões desta fase

**`bugometro_status` é 1:1 e NÃO usa `completaOPar()`.** Esta é a armadilha que a seção D1 de [pendencias.md](../../pendencias.md) registra. As três entidades da Fase 2 com par único composto precisam de `completaOPar()` porque a regra `unique` mora num campo e o outro campo do par pode vir sozinho num `PUT` parcial. Aqui não: o `jogo_id` é único sozinho, então a regra mora no **próprio campo que está mudando**. Se o `PUT` não manda `jogo_id`, o `sometimes` pula a regra — e isso é **correto**, porque ausente significa inalterado, que não pode colidir consigo mesmo. Portanto:

- `regras(?BugometroStatus $existente = null)` — **sem `Request`**, como no `AvaliacaoController`.
- **Nenhum `completaOPar()`.**
- `Rule::unique('bugometro_status', 'jogo_id')->ignore($existente)` pendurada em `jogo_id`.

Na migration o modificador vem **antes** do `constrained()`:
`$table->foreignId('jogo_id')->unique()->constrained('jogos')->cascadeOnDelete();`

**`historico_bug` cria em `registrado_em`.** Segundo timestamp irregular do projeto; o primeiro foi `adicionado_em` em `biblioteca_usuario`, que funciona e serve de precedente. Dois lugares têm que concordar: a coluna na migration e a constante `CREATED_AT` no model. (O terceiro lugar da checklist — `withTimestamps()` — não se aplica, porque não há pivô aqui.)

**Nulabilidade lida do jeito que a spec escreve.** A seção 5 da spec marca `nullable` explicitamente onde quer (`bugometro_status.pontuacao` e `.status`), e é silenciosa nas demais colunas destas quatro tabelas. Em toda a Fase 1 e 2, silêncio significou obrigatório. Então: **`pontuacao` e `status` são nullable; todo o resto é `NOT NULL` e `required`.** Um relato de bug sem título ou um histórico sem contagens não representa nada.

**Dois limites numéricos que o DDL não tem.** `porcentagem` é validada `min:0|max:100` e as quatro colunas `quantidade_*` são validadas `min:0`. O DDL declara os dois apenas como `INT`, mas uma porcentagem fora de 0–100 e uma contagem de crashes negativa são dados sem significado. É o mesmo tipo de leitura que produziu `max:9.9` para `nota` na Fase 2.

**`->parameters()` finalmente ganha o dia em duas rotas.** O singularizador inglês devolveria `metricas_bug` e `relatos_bug` inalterados (a última palavra, `bug`, já é singular), quando o parâmetro correto é `metrica_bug` e `relato_bug`. Sem a declaração explícita o binding quebraria — a mesma classe de falha que `avaliacoes` → `avaliaco` na Fase 2.

**`routes/api.php` é editado de forma incremental** nas quatro tasks (um import na ordem alfabética, uma rota no fim), não reescrito por inteiro. O arquivo já tem sete recursos e reescrevê-lo quatro vezes aumentaria a superfície de divergência entre as cópias.

---

### Task 1: Entidade `bugometro_status` completa

O estado atual do Bugômetro de um jogo: uma pontuação e um rótulo. **Um jogo tem no máximo um.** Nome de tabela no singular no DDL — mantido (spec §4.2).

**Files:**
- Create: `database/migrations/2026_08_20_000001_create_bugometro_status_table.php`
- Create: `app/Models/BugometroStatus.php`
- Create: `database/factories/BugometroStatusFactory.php`
- Create: `app/Http/Controllers/BugometroStatusController.php`
- Modify: `app/Models/Jogo.php` (adiciona `bugometroStatus()`)
- Modify: `routes/api.php`
- Test: `tests/Feature/BugometroStatusApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Jogo` e `Database\Factories\JogoFactory` (Fase 1).
- Produces:
  - `App\Models\BugometroStatus` — tabela `bugometro_status`, `$fillable = ['jogo_id', 'pontuacao', 'status']`, relacionamento `jogo()`.
  - `Database\Factories\BugometroStatusFactory` — cria o seu próprio jogo via `Jogo::factory()`, então cada chamada gera um `jogo_id` distinto e nunca colide com o índice único.
  - `Jogo::bugometroStatus()` — `HasOne`.
  - Rotas `bugometro_status.*` sob `/api/bugometro_status`, parâmetro `{bugometro_status}`.

**Commit:** um só, no Step 8.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/BugometroStatusApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=BugometroStatusApiTest`

Expected: FAIL com `Class "App\Models\BugometroStatus" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000001_create_bugometro_status_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nome no singular, como no DDL de origem. Ver secao 4.2 da spec.
        Schema::create('bugometro_status', function (Blueprint $table) {
            $table->id();
            // Relacao 1:1 com jogo: unique de coluna unica, nao par composto.
            // O modificador vem ANTES de constrained().
            $table->foreignId('jogo_id')->unique()->constrained('jogos')->cascadeOnDelete();
            $table->integer('pontuacao')->nullable();
            $table->string('status', 20)->nullable();
            // O DDL so tem atualizado_em; criado_em e acrescentado por
            // consistencia. Ver secao 3.3 da spec.
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bugometro_status');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/BugometroStatus.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugometroStatus extends Model
{
    /** @use HasFactory<\Database\Factories\BugometroStatusFactory> */
    use HasFactory;

    protected $table = 'bugometro_status';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'jogo_id',
        'pontuacao',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'jogo_id' => 'integer',
            'pontuacao' => 'integer',
        ];
    }

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class, 'jogo_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/BugometroStatusFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\BugometroStatus;
use App\Models\Jogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugometroStatus>
 */
class BugometroStatusFactory extends Factory
{
    protected $model = BugometroStatus::class;

    /**
     * jogo_id e uma factory, nao um id fixo: como a coluna e unica, cada
     * chamada precisa do seu proprio jogo para nao colidir.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'pontuacao' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['estavel', 'instavel', 'critico', 'injogavel']),
        ];
    }
}
```

- [ ] **Step 6: Adicionar `bugometroStatus()` ao Model `Jogo`**

Em `app/Models/Jogo.php`: acrescentar o import
`use Illuminate\Database\Eloquent\Relations\HasOne;` junto aos outros imports
de relacionamento, e o método abaixo depois de `avaliacoes()`:

```php
    public function bugometroStatus(): HasOne
    {
        return $this->hasOne(BugometroStatus::class, 'jogo_id');
    }
```

Não alterar mais nada no arquivo — `$table`, as constantes de timestamp,
`$fillable`, os casts, `plataformas()` e `avaliacoes()` ficam como estão.

- [ ] **Step 7: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller BugometroStatusController --api`

Substituir todo o conteúdo de `app/Http/Controllers/BugometroStatusController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\BugometroStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BugometroStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BugometroStatus::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $status = BugometroStatus::create($dados);

        return response()->json($status, 201);
    }

    public function show(BugometroStatus $bugometroStatus): JsonResponse
    {
        return response()->json($bugometroStatus);
    }

    public function update(Request $request, BugometroStatus $bugometroStatus): JsonResponse
    {
        $dados = $request->validate($this->regras($bugometroStatus));

        $bugometroStatus->update($dados);

        return response()->json($bugometroStatus);
    }

    public function destroy(BugometroStatus $bugometroStatus): JsonResponse
    {
        $bugometroStatus->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update.
     *
     * A unicidade aqui e de coluna unica (relacao 1:1 com jogo), nao de par
     * composto: a regra mora no proprio campo que muda. Por isso este
     * controller nao recebe a Request nem tem completaOPar() — se o update
     * nao envia jogo_id, o "sometimes" pula a checagem, o que e correto,
     * porque ausente significa inalterado e nao pode colidir consigo mesmo.
     *
     * @return array<string, mixed>
     */
    private function regras(?BugometroStatus $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => [
                $obrigatorio, 'integer', 'exists:jogos,id',
                Rule::unique('bugometro_status', 'jogo_id')->ignore($existente),
            ],
            'pontuacao' => 'nullable|integer',
            'status' => 'nullable|string|max:20',
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\BugometroStatusController;` mantendo a ordem
alfabética (fica logo depois de `BibliotecaUsuarioController`), e a rota ao
final do arquivo:

```php
Route::apiResource('bugometro_status', BugometroStatusController::class)
    ->parameters(['bugometro_status' => 'bugometro_status']);
```

- [ ] **Step 8: Rodar o teste, conferir as rotas e commitar**

Run: `php artisan test --filter=BugometroStatusApiTest`
Expected: PASS, 17 testes.

Run: `php artisan test`
Expected: PASS, 132 testes (115 das fases anteriores + 17).

Run: `php artisan route:list --path=api`
Expected: 40 rotas; as novas usam o parâmetro `{bugometro_status}`.

```bash
git add -A
git commit -m "feat: entidade bugometro_status completa"
```

---

### Task 2: Entidade `metricas_bug` completa

Métricas agregadas de bugs por jogo: tipo, severidade e a porcentagem de ocorrência. Um jogo tem várias.

**Files:**
- Create: `database/migrations/2026_08_20_000002_create_metricas_bug_table.php`
- Create: `app/Models/MetricaBug.php`
- Create: `database/factories/MetricaBugFactory.php`
- Create: `app/Http/Controllers/MetricaBugController.php`
- Modify: `app/Models/Jogo.php` (adiciona `metricasBug()`)
- Modify: `routes/api.php`
- Test: `tests/Feature/MetricaBugApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Jogo` e `Database\Factories\JogoFactory`.
- Produces:
  - `App\Models\MetricaBug` — tabela `metricas_bug`, `$fillable = ['jogo_id', 'tipo', 'severidade', 'porcentagem']`, relacionamento `jogo()`.
  - `Database\Factories\MetricaBugFactory`.
  - `Jogo::metricasBug()` — `HasMany`.
  - Rotas `metricas_bug.*` sob `/api/metricas_bug`, parâmetro `{metrica_bug}`.

**Commit:** um só, no Step 8.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/MetricaBugApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=MetricaBugApiTest`

Expected: FAIL com `Class "App\Models\MetricaBug" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000002_create_metricas_bug_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metricas_bug', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jogo_id')->constrained('jogos')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('severidade', 20);
            $table->integer('porcentagem');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metricas_bug');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/MetricaBug.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricaBug extends Model
{
    /** @use HasFactory<\Database\Factories\MetricaBugFactory> */
    use HasFactory;

    protected $table = 'metricas_bug';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'jogo_id',
        'tipo',
        'severidade',
        'porcentagem',
    ];

    protected function casts(): array
    {
        return [
            'jogo_id' => 'integer',
            'porcentagem' => 'integer',
        ];
    }

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class, 'jogo_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/MetricaBugFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Jogo;
use App\Models\MetricaBug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetricaBug>
 */
class MetricaBugFactory extends Factory
{
    protected $model = MetricaBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'tipo' => fake()->randomElement(['crash', 'bug', 'fps_drop', 'stutter']),
            'severidade' => fake()->randomElement(['baixa', 'media', 'alta', 'critica']),
            'porcentagem' => fake()->numberBetween(0, 100),
        ];
    }
}
```

- [ ] **Step 6: Adicionar `metricasBug()` ao Model `Jogo`**

Em `app/Models/Jogo.php`, acrescentar o método abaixo depois de
`bugometroStatus()`. O import de `HasMany` já existe no arquivo:

```php
    public function metricasBug(): HasMany
    {
        return $this->hasMany(MetricaBug::class, 'jogo_id');
    }
```

- [ ] **Step 7: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller MetricaBugController --api`

Substituir todo o conteúdo de `app/Http/Controllers/MetricaBugController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\MetricaBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricaBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(MetricaBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $metrica = MetricaBug::create($dados);

        return response()->json($metrica, 201);
    }

    public function show(MetricaBug $metricaBug): JsonResponse
    {
        return response()->json($metricaBug);
    }

    public function update(Request $request, MetricaBug $metricaBug): JsonResponse
    {
        $dados = $request->validate($this->regras($metricaBug));

        $metricaBug->update($dados);

        return response()->json($metricaBug);
    }

    public function destroy(MetricaBug $metricaBug): JsonResponse
    {
        $metricaBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?MetricaBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'tipo' => "$obrigatorio|string|max:20",
            'severidade' => "$obrigatorio|string|max:20",
            // Porcentagem: o DDL so diz INT, mas fora de 0-100 nao significa nada.
            'porcentagem' => "$obrigatorio|integer|min:0|max:100",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\MetricaBugController;` mantendo a ordem alfabética,
e a rota ao final do arquivo:

```php
Route::apiResource('metricas_bug', MetricaBugController::class)
    ->parameters(['metricas_bug' => 'metrica_bug']);
```

- [ ] **Step 8: Rodar o teste, conferir as rotas e commitar**

Run: `php artisan test --filter=MetricaBugApiTest`
Expected: PASS, 15 testes.

Run: `php artisan test`
Expected: PASS, 147 testes.

Run: `php artisan route:list --path=api`
Expected: 45 rotas. **Conferir que o parâmetro é `{metrica_bug}` e não `{metricas_bug}`** — o singularizador inglês deixaria `metricas_bug` intacto, porque a última palavra já é singular.

```bash
git add -A
git commit -m "feat: entidade metricas_bug completa"
```

---

### Task 3: Entidade `relatos_bug` completa

Relatos individuais de bug num jogo: título, descrição, severidade e origem. Um jogo tem vários.

**Files:**
- Create: `database/migrations/2026_08_20_000003_create_relatos_bug_table.php`
- Create: `app/Models/RelatoBug.php`
- Create: `database/factories/RelatoBugFactory.php`
- Create: `app/Http/Controllers/RelatoBugController.php`
- Modify: `app/Models/Jogo.php` (adiciona `relatosBug()`)
- Modify: `routes/api.php`
- Test: `tests/Feature/RelatoBugApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Jogo` e `Database\Factories\JogoFactory`.
- Produces:
  - `App\Models\RelatoBug` — tabela `relatos_bug`, `$fillable = ['jogo_id', 'titulo', 'descricao', 'severidade', 'origem']`, relacionamento `jogo()`.
  - `Database\Factories\RelatoBugFactory`.
  - `Jogo::relatosBug()` — `HasMany`.
  - Rotas `relatos_bug.*` sob `/api/relatos_bug`, parâmetro `{relato_bug}`.

**Commit:** um só, no Step 8.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/RelatoBugApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=RelatoBugApiTest`

Expected: FAIL com `Class "App\Models\RelatoBug" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000003_create_relatos_bug_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relatos_bug', function (Blueprint $table) {
            $table->id();
            // constrained() ja cria o indice em jogo_id, cobrindo o
            // CREATE INDEX idx_jogo do DDL de origem.
            $table->foreignId('jogo_id')->constrained('jogos')->cascadeOnDelete();
            $table->string('titulo', 100);
            $table->text('descricao');
            $table->string('severidade', 20);
            $table->string('origem', 50);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relatos_bug');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/RelatoBug.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelatoBug extends Model
{
    /** @use HasFactory<\Database\Factories\RelatoBugFactory> */
    use HasFactory;

    protected $table = 'relatos_bug';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'jogo_id',
        'titulo',
        'descricao',
        'severidade',
        'origem',
    ];

    protected function casts(): array
    {
        return [
            'jogo_id' => 'integer',
        ];
    }

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class, 'jogo_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/RelatoBugFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Jogo;
use App\Models\RelatoBug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelatoBug>
 */
class RelatoBugFactory extends Factory
{
    protected $model = RelatoBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'titulo' => fake()->sentence(6),
            'descricao' => fake()->paragraph(),
            'severidade' => fake()->randomElement(['baixa', 'media', 'alta', 'critica']),
            'origem' => fake()->randomElement(['relato de usuario', 'telemetria', 'QA interno']),
        ];
    }
}
```

`fake()->sentence(6)` produz um título bem abaixo do limite de 100 caracteres.

- [ ] **Step 6: Adicionar `relatosBug()` ao Model `Jogo`**

Em `app/Models/Jogo.php`, acrescentar o método abaixo depois de
`metricasBug()`. O import de `HasMany` já existe no arquivo:

```php
    public function relatosBug(): HasMany
    {
        return $this->hasMany(RelatoBug::class, 'jogo_id');
    }
```

- [ ] **Step 7: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller RelatoBugController --api`

Substituir todo o conteúdo de `app/Http/Controllers/RelatoBugController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\RelatoBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatoBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RelatoBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $relato = RelatoBug::create($dados);

        return response()->json($relato, 201);
    }

    public function show(RelatoBug $relatoBug): JsonResponse
    {
        return response()->json($relatoBug);
    }

    public function update(Request $request, RelatoBug $relatoBug): JsonResponse
    {
        $dados = $request->validate($this->regras($relatoBug));

        $relatoBug->update($dados);

        return response()->json($relatoBug);
    }

    public function destroy(RelatoBug $relatoBug): JsonResponse
    {
        $relatoBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?RelatoBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'titulo' => "$obrigatorio|string|max:100",
            'descricao' => "$obrigatorio|string|max:5000",
            'severidade' => "$obrigatorio|string|max:20",
            'origem' => "$obrigatorio|string|max:50",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\RelatoBugController;` mantendo a ordem alfabética,
e a rota ao final do arquivo:

```php
Route::apiResource('relatos_bug', RelatoBugController::class)
    ->parameters(['relatos_bug' => 'relato_bug']);
```

- [ ] **Step 8: Rodar o teste, conferir as rotas e commitar**

Run: `php artisan test --filter=RelatoBugApiTest`
Expected: PASS, 14 testes.

Run: `php artisan test`
Expected: PASS, 161 testes.

Run: `php artisan route:list --path=api`
Expected: 50 rotas. **Conferir que o parâmetro é `{relato_bug}` e não `{relatos_bug}`.**

```bash
git add -A
git commit -m "feat: entidade relatos_bug completa"
```

---

### Task 4: Entidade `historico_bug` completa e fechamento da fase

Snapshots periódicos das contagens de problema de um jogo. É a tabela cujo timestamp de criação se chama **`registrado_em`**.

Esta task fecha a fase, então termina com as verificações completas e o README.

**Files:**
- Create: `database/migrations/2026_08_20_000004_create_historico_bug_table.php`
- Create: `app/Models/HistoricoBug.php`
- Create: `database/factories/HistoricoBugFactory.php`
- Create: `app/Http/Controllers/HistoricoBugController.php`
- Modify: `app/Models/Jogo.php` (adiciona `historicoBug()`)
- Modify: `routes/api.php`
- Modify: `README.md`
- Test: `tests/Feature/HistoricoBugApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Jogo` e `Database\Factories\JogoFactory`.
- Produces:
  - `App\Models\HistoricoBug` — tabela `historico_bug`, `CREATED_AT = 'registrado_em'`, `$fillable = ['jogo_id', 'quantidade_crash', 'quantidade_bug', 'quantidade_fps_drop', 'quantidade_stutter']`, relacionamento `jogo()`.
  - `Database\Factories\HistoricoBugFactory`.
  - `Jogo::historicoBug()` — `HasMany` (vários snapshots ao longo do tempo).
  - Rotas `historico_bug.*` sob `/api/historico_bug`, parâmetro `{historico_bug}`.

**Commit:** um só, no Step 9.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/HistoricoBugApiTest.php`:

```php
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
            ->assertJsonMissingPath('criado_em');
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=HistoricoBugApiTest`

Expected: FAIL com `Class "App\Models\HistoricoBug" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000004_create_historico_bug_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_bug', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jogo_id')->constrained('jogos')->cascadeOnDelete();
            $table->integer('quantidade_crash');
            $table->integer('quantidade_bug');
            $table->integer('quantidade_fps_drop');
            $table->integer('quantidade_stutter');
            // O DDL chama o timestamp de criacao de "registrado_em" nesta
            // tabela. Ver secao 3.3 da spec.
            $table->timestamp('registrado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_bug');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/HistoricoBug.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoBug extends Model
{
    /** @use HasFactory<\Database\Factories\HistoricoBugFactory> */
    use HasFactory;

    protected $table = 'historico_bug';

    // O DDL chama o timestamp de criacao de "registrado_em" nesta tabela.
    const CREATED_AT = 'registrado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'jogo_id',
        'quantidade_crash',
        'quantidade_bug',
        'quantidade_fps_drop',
        'quantidade_stutter',
    ];

    protected function casts(): array
    {
        return [
            'jogo_id' => 'integer',
            'quantidade_crash' => 'integer',
            'quantidade_bug' => 'integer',
            'quantidade_fps_drop' => 'integer',
            'quantidade_stutter' => 'integer',
        ];
    }

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class, 'jogo_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/HistoricoBugFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\HistoricoBug;
use App\Models\Jogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricoBug>
 */
class HistoricoBugFactory extends Factory
{
    protected $model = HistoricoBug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jogo_id' => Jogo::factory(),
            'quantidade_crash' => fake()->numberBetween(0, 500),
            'quantidade_bug' => fake()->numberBetween(0, 500),
            'quantidade_fps_drop' => fake()->numberBetween(0, 500),
            'quantidade_stutter' => fake()->numberBetween(0, 500),
        ];
    }
}
```

- [ ] **Step 6: Adicionar `historicoBug()` ao Model `Jogo`**

Em `app/Models/Jogo.php`, acrescentar o método abaixo depois de
`relatosBug()`. O import de `HasMany` já existe no arquivo:

```php
    public function historicoBug(): HasMany
    {
        return $this->hasMany(HistoricoBug::class, 'jogo_id');
    }
```

É `HasMany`, não `HasOne`: cada linha é um snapshot num momento, e um jogo
acumula vários ao longo do tempo.

- [ ] **Step 7: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller HistoricoBugController --api`

Substituir todo o conteúdo de `app/Http/Controllers/HistoricoBugController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\HistoricoBug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoricoBugController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(HistoricoBug::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $registro = HistoricoBug::create($dados);

        return response()->json($registro, 201);
    }

    public function show(HistoricoBug $historicoBug): JsonResponse
    {
        return response()->json($historicoBug);
    }

    public function update(Request $request, HistoricoBug $historicoBug): JsonResponse
    {
        $dados = $request->validate($this->regras($historicoBug));

        $historicoBug->update($dados);

        return response()->json($historicoBug);
    }

    public function destroy(HistoricoBug $historicoBug): JsonResponse
    {
        $historicoBug->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?HistoricoBug $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        // Contagens: o DDL so diz INT, mas contagem negativa nao significa nada.
        return [
            'jogo_id' => "$obrigatorio|integer|exists:jogos,id",
            'quantidade_crash' => "$obrigatorio|integer|min:0",
            'quantidade_bug' => "$obrigatorio|integer|min:0",
            'quantidade_fps_drop' => "$obrigatorio|integer|min:0",
            'quantidade_stutter' => "$obrigatorio|integer|min:0",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\HistoricoBugController;` mantendo a ordem
alfabética, e a rota ao final do arquivo:

```php
Route::apiResource('historico_bug', HistoricoBugController::class)
    ->parameters(['historico_bug' => 'historico_bug']);
```

- [ ] **Step 8: Verificações de fechamento da fase**

Run: `php artisan test --filter=HistoricoBugApiTest`
Expected: PASS, 15 testes.

Run: `php artisan test`
Expected: PASS, **176 testes**.

Run: `php artisan migrate:fresh --seed`
Expected: 14 migrations rodam sem erro, nesta ordem: `sessions`, `cache`, `jobs`, `usuarios`, `jogos`, `plataformas`, `jogos_plataformas`, `biblioteca_usuario`, `avaliacoes`, `curtidas_avaliacoes`, `bugometro_status`, `metricas_bug`, `relatos_bug`, `historico_bug`.

Run: `php artisan route:list --path=api`
Expected: **55 rotas** — 5 para cada um dos 11 recursos.

- [ ] **Step 9: Documentar e commitar**

Em `README.md`, na seção "LaaC Lab — API", substituir a linha que lista os
recursos disponíveis por:

```markdown
Recursos disponíveis: `usuarios`, `jogos`, `plataformas`, `jogos_plataformas`,
`biblioteca_usuario`, `avaliacoes`, `curtidas_avaliacoes`, `bugometro_status`,
`metricas_bug`, `relatos_bug`, `historico_bug`.
```

E acrescentar, logo abaixo do parágrafo que já explica as cascatas:

```markdown
Os quatro recursos do Bugômetro (`bugometro_status`, `metricas_bug`,
`relatos_bug`, `historico_bug`) são todos filhos de um jogo e somem junto com
ele. `bugometro_status` é o único com relação 1:1: um jogo tem no máximo um
status, e tentar criar um segundo retorna 422.

`porcentagem` em `metricas_bug` vai de 0 a 100; as quatro contagens de
`historico_bug` não aceitam valores negativos. Nenhum dos dois limites está no
DDL de origem — foram acrescentados porque os valores fora deles não têm
significado.

Nesta entrega o Bugômetro é CRUD puro: nada calcula `pontuacao` ou `status`
automaticamente a partir das métricas.
```

Não alterar o resto do README.

```bash
git add -A
git commit -m "feat: entidade historico_bug completa e documentacao da Fase 3"
```

---

## Estado esperado ao fim do plano

- 14 migrations, 11 models de domínio, 11 controllers de API, 11 factories.
- 55 rotas sob `/api`, cobertas por 176 testes verdes.
- As oito FKs desta fase, todas apontando para `jogos`, com teste de cascata cada uma.
- 4 commits, um por entidade completa:

| Commit | Conteúdo |
|---|---|
| Task 1 | entidade `bugometro_status` completa (1:1) |
| Task 2 | entidade `metricas_bug` completa |
| Task 3 | entidade `relatos_bug` completa |
| Task 4 | entidade `historico_bug` completa + README |

## Próximo passo (fora deste plano)

Fase 4 — o fórum: `categorias`, `topicos` e `posts`. `topicos` tem duas FKs
(`usuario_id` e `categoria_id`) e `posts` tem duas (`topico_id` e
`usuario_id`), o que traz de volta a cascata de dois saltos
(`usuario`/`categoria` → `topico` → `post`). Nenhuma das três tem constraint de
unicidade. Ganha o seu próprio plano.

As pendências acumuladas continuam em [docs/pendencias.md](../../pendencias.md),
para decisão depois da Fase 5. Duas ficam materialmente mais caras a partir
desta fase: `index` sem paginação agora inclui `relatos_bug` e `historico_bug`,
que são fluxos sem limite superior; e nada aqui calcula o Bugômetro, então a
lógica que alimentaria `bugometro_status` continua sendo trabalho futuro.
