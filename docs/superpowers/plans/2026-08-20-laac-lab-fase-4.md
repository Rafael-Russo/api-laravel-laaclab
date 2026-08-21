# LaaC Lab — Fase 4: Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar as três entidades do fórum — `categorias`, `topicos` e `posts` — com CRUD REST completo.

**Architecture:** Mesmo padrão das fases anteriores: um `apiResource` por entidade, migration + Model + Factory + Controller, validação inline extraída para `private function regras()`, 404 por route-model binding. O que é novo nesta fase: as duas primeiras tabelas com **duas FKs cada**, e o retorno da cascata de múltiplos saltos — apagar um usuário ou uma categoria tem que derrubar o tópico e, por tabela, os posts dele.

**Tech Stack:** PHP 8.3, Laravel 13.17, PHPUnit 12.5, SQLite (dev e testes) / MySQL (opcional via `.env`).

**Spec:** [docs/superpowers/specs/2026-08-19-laac-lab-api-design.md](../specs/2026-08-19-laac-lab-api-design.md)

**Fases anteriores:** [Fases 0-1](2026-08-19-laac-lab-fases-0-1.md), [Fase 2](2026-08-19-laac-lab-fase-2.md), [Fase 3](2026-08-20-laac-lab-fase-3.md). Ponto de partida: 199 testes verdes, 56 rotas sob `/api` (55 REST + `POST /api/login`), 14 migrations, 11 entidades.

**Pendências conhecidas:** [docs/pendencias.md](../../pendencias.md) — nada ali bloqueia esta fase; a seção D é a origem de três decisões abaixo.

## Global Constraints

- Tabelas e colunas em português, exatamente como no DDL: `categorias`, `topicos`, `posts`.
- Todo Model declara `protected $table` explicitamente — o pluralizador do Eloquent é inglês e não é confiável nesses nomes.
- Todo Model declara `const CREATED_AT = 'criado_em';` e `const UPDATED_AT = 'atualizado_em';`. Nesta fase não há timestamp irregular.
- Migrations usam **exclusivamente** o Schema Builder. Nenhum `DB::statement`, nenhum SQL cru, nenhum tipo específico de vendor.
- Toda FK usa `foreignId(...)->index()->constrained('tabela')->cascadeOnDelete()`, com `->index()` **antes** de `constrained()`.
- Todo `Route::apiResource` declara `->parameters()` explicitamente (spec §4.2).
- URI de cada recurso = nome exato da tabela em `snake_case`.
- Status HTTP: `index`/`show`/`update` → 200, `store` → 201, `destroy` → 204, ID inexistente → 404, validação falha → 422.
- Todo controller estende `App\Http\Controllers\Controller` e tipa o retorno de todos os métodos como `JsonResponse`.
- **Um commit por entidade de domínio completa** — migration, model, factory, controller, rotas e testes juntos, nunca pela metade.
- **Cada FK nova ganha o seu próprio teste de cascata.** A regra é por FK, não por entidade — ver Decisões.
- Nenhuma tarefa fecha com `php artisan test` vermelho.

## Decisões desta fase

**Quatro FKs em duas tabelas: quatro testes de cascata, não dois.** `topicos` tem `usuario_id` e `categoria_id`; `posts` tem `topico_id` e `usuario_id`; `categorias` não tem nenhuma. Esta é exatamente a forma onde contar cascatas por entidade subconta pela metade — a Fase 3 satisfez a regra por acaso, porque todas as suas tabelas tinham uma FK só. Aqui a distinção morde. A tabela abaixo é a lista de verificação:

| Tabela | FK | Teste de cascata |
|---|---|---|
| `topicos` | `usuario_id` → `usuarios` | apagar o usuário apaga os tópicos dele |
| `topicos` | `categoria_id` → `categorias` | apagar a categoria apaga os tópicos dela |
| `posts` | `topico_id` → `topicos` | apagar o tópico apaga os posts dele |
| `posts` | `usuario_id` → `usuarios` | apagar o autor do post apaga o post **e deixa o tópico de pé** |

O último é mais forte que uma cascata simples: prova que a FK exercitada é a do próprio post, não uma herdada do tópico.

**A cascata de múltiplos saltos volta, e é testada duas vezes.** Nenhuma tabela da Fase 3 tinha avô — todas eram filhas diretas de `jogos`. Aqui `posts` está a dois saltos de `usuarios` e de `categorias`, pelos dois caminhos:

- apagar o **autor do tópico** (não o do post) tem que derrubar tópico e posts;
- apagar a **categoria** tem que derrubar tópico e posts.

O precedente é `CurtidaAvaliacaoApiTest::test_apagar_usuario_apaga_avaliacao_e_curtidas_em_cascata`, da Fase 2. O ponto delicado é qual usuário se apaga: as factories dão autores distintos ao tópico e ao post, então apagar o autor do post provaria só um salto.

**Nenhuma das três tem constraint de unicidade.** Não copie a forma de `bugometro_status` (`Rule::unique`) nem a das entidades de par composto da Fase 2 (`completaOPar()`). Os três controllers desta fase têm `regras(?Model $existente = null)` sem `Request` e sem `Rule` — a forma de `RelatoBugController`. Duas categorias com o mesmo nome, dois tópicos com o mesmo título e dois posts idênticos são todos legais, como no DDL.

**`->index()` antes de `constrained()` nas quatro FKs.** A Fase 3 descobriu que `constrained()` emite apenas a cláusula `FOREIGN KEY`: o MySQL cria um índice de apoio como efeito colateral, o SQLite não. Isso cobre o `CREATE INDEX idx_topico ON posts(topico_id)` do DDL de origem, que é o caminho de leitura mais quente do fórum. A ordem importa e falha em silêncio se invertida — `constrained()` devolve um objeto de chave estrangeira, não a coluna.

**Nulabilidade lida pelo silêncio da spec.** A seção 5 marca `nullable` explicitamente onde quer e é silenciosa nestas três tabelas. Então `nome`, `titulo`, `conteudo` e as quatro FKs são todos `NOT NULL` e `required`. Um post sem conteúdo não é um post.

**`Categoria` NÃO ganha `topicos()`.** A seção 5.1 da spec lista `Topico belongsTo Categoria`, mas não lista o inverso. Nenhum teste desta fase precisa dele — os testes de cascata usam `assertDatabaseMissing`, que não passa por relacionamento. Adicionar um relacionamento sem consumidor é exatamente o "Extra" que a revisão procura. A omissão é deliberada, não esquecimento.

---

### Task 1: Entidade `categorias` completa

As seções do fórum. A tabela mais simples do schema junto com `plataformas`: só `id` e `nome`, sem FK nenhuma.

**Files:**
- Create: `database/migrations/2026_08_20_000005_create_categorias_table.php`
- Create: `app/Models/Categoria.php`
- Create: `database/factories/CategoriaFactory.php`
- Create: `app/Http/Controllers/CategoriaController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/CategoriaApiTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `App\Models\Categoria` — tabela `categorias`, `$fillable = ['nome']`.
  - `Database\Factories\CategoriaFactory`.
  - Rotas `categorias.*` sob `/api/categorias`, parâmetro `{categoria}`.
  - A Task 2 referencia `categorias.id` na FK de `topicos`.

**Commit:** um só, no Step 7.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/CategoriaApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=CategoriaApiTest`

Expected: FAIL com `Class "App\Models\Categoria" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000005_create_categorias_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            // O DDL nao define timestamp nesta tabela; o par e acrescentado
            // por consistencia. Ver secao 3.3 da spec.
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/Categoria.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    /** @use HasFactory<\Database\Factories\CategoriaFactory> */
    use HasFactory;

    protected $table = 'categorias';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'nome',
    ];
}
```

Sem relacionamento `topicos()`: a seção 5.1 da spec não o lista e nada nesta
fase o consome. Ver as Decisões deste plano.

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/CategoriaFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    /**
     * Sem unique() no randomElement: a tabela nao tem constraint de
     * unicidade, e a lista finita estouraria com poucos registros.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->randomElement([
                'Geral', 'Bugs e travamentos', 'Desempenho', 'Suporte',
                'Off-topic', 'Guias e dicas', 'Novidades',
            ]),
        ];
    }
}
```

- [ ] **Step 6: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller CategoriaController --api`

Substituir todo o conteúdo de `app/Http/Controllers/CategoriaController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Categoria::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $categoria = Categoria::create($dados);

        return response()->json($categoria, 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json($categoria);
    }

    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $dados = $request->validate($this->regras($categoria));

        $categoria->update($dados);

        return response()->json($categoria);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?Categoria $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'nome' => "$obrigatorio|string|max:50",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\CategoriaController;` mantendo a ordem alfabética
(fica logo depois de `BugometroStatusController`), e a rota **antes** do bloco
de autenticação no fim do arquivo:

```php
Route::apiResource('categorias', CategoriaController::class)
    ->parameters(['categorias' => 'categoria']);
```

- [ ] **Step 7: Rodar o teste, conferir as rotas e commitar**

Run: `php artisan test --filter=CategoriaApiTest`
Expected: PASS, 11 testes.

Run: `php artisan test`
Expected: PASS, 210 testes (199 das fases anteriores + 11).

Run: `php artisan route:list --path=api`
Expected: 61 rotas; as novas usam o parâmetro `{categoria}`.

```bash
git add -A
git commit -m "feat: entidade categorias completa"
```

---

### Task 2: Entidade `topicos` completa

Os tópicos do fórum: quem abriu, em que categoria, com que título. **Primeira tabela do projeto com duas FKs**, então dois testes de cascata.

**Files:**
- Create: `database/migrations/2026_08_20_000006_create_topicos_table.php`
- Create: `app/Models/Topico.php`
- Create: `database/factories/TopicoFactory.php`
- Create: `app/Http/Controllers/TopicoController.php`
- Modify: `app/Models/Usuario.php` (adiciona `topicos()`)
- Modify: `routes/api.php`
- Test: `tests/Feature/TopicoApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Categoria` e `Database\Factories\CategoriaFactory` (Task 1); `App\Models\Usuario` e `Database\Factories\UsuarioFactory` (Fase 1).
- Produces:
  - `App\Models\Topico` — tabela `topicos`, `$fillable = ['usuario_id', 'categoria_id', 'titulo']`, relacionamentos `usuario()` e `categoria()`.
  - `Database\Factories\TopicoFactory` — cria o seu próprio usuário e a sua própria categoria, então `Topico::factory()->create()` funciona sozinho.
  - `Usuario::topicos()` — `HasMany`.
  - Rotas `topicos.*` sob `/api/topicos`, parâmetro `{topico}`.
  - A Task 3 referencia `topicos.id` na FK de `posts` e acrescenta `Topico::posts()`.

**Commit:** um só, no Step 8.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/TopicoApiTest.php`:

```php
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
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=TopicoApiTest`

Expected: FAIL com `Class "App\Models\Topico" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000006_create_topicos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topicos', function (Blueprint $table) {
            $table->id();
            // ->index() explicito: constrained() sozinho emite so a clausula
            // FOREIGN KEY. O MySQL cria um indice de apoio como efeito
            // colateral, o SQLite nao — e o SQLite e o banco padrao aqui.
            $table->foreignId('usuario_id')->index()->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('categoria_id')->index()->constrained('categorias')->cascadeOnDelete();
            $table->string('titulo', 100);
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topicos');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/Topico.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topico extends Model
{
    /** @use HasFactory<\Database\Factories\TopicoFactory> */
    use HasFactory;

    protected $table = 'topicos';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'usuario_id',
        'categoria_id',
        'titulo',
    ];

    protected function casts(): array
    {
        return [
            'usuario_id' => 'integer',
            'categoria_id' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/TopicoFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topico>
 */
class TopicoFactory extends Factory
{
    protected $model = Topico::class;

    /**
     * As FKs sao factories, nao ids fixos: assim Topico::factory()->create()
     * funciona sem preparacao previa.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'categoria_id' => Categoria::factory(),
            'titulo' => fake()->sentence(6),
        ];
    }
}
```

`fake()->sentence(6)` produz um título bem abaixo do limite de 100 caracteres.

- [ ] **Step 6: Adicionar `topicos()` ao Model `Usuario`**

Em `app/Models/Usuario.php`, acrescentar o método abaixo depois de
`avaliacoes()`. O import de `HasMany` já existe no arquivo:

```php
    public function topicos(): HasMany
    {
        return $this->hasMany(Topico::class, 'usuario_id');
    }
```

Não alterar mais nada no arquivo — `$table`, as constantes de timestamp,
`$fillable`, `$hidden`, `$attributes`, os casts, `getAuthPassword()`, `jogos()`
e `avaliacoes()` ficam como estão.

- [ ] **Step 7: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller TopicoController --api`

Substituir todo o conteúdo de `app/Http/Controllers/TopicoController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Topico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Topico::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $topico = Topico::create($dados);

        return response()->json($topico, 201);
    }

    public function show(Topico $topico): JsonResponse
    {
        return response()->json($topico);
    }

    public function update(Request $request, Topico $topico): JsonResponse
    {
        $dados = $request->validate($this->regras($topico));

        $topico->update($dados);

        return response()->json($topico);
    }

    public function destroy(Topico $topico): JsonResponse
    {
        $topico->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?Topico $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'usuario_id' => "$obrigatorio|integer|exists:usuarios,id",
            'categoria_id' => "$obrigatorio|integer|exists:categorias,id",
            'titulo' => "$obrigatorio|string|max:100",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\TopicoController;` mantendo a ordem alfabética, e a
rota **antes** do bloco de autenticação no fim do arquivo:

```php
Route::apiResource('topicos', TopicoController::class)
    ->parameters(['topicos' => 'topico']);
```

- [ ] **Step 8: Rodar o teste, conferir as rotas e commitar**

Run: `php artisan test --filter=TopicoApiTest`
Expected: PASS, 16 testes.

Run: `php artisan test`
Expected: PASS, 226 testes.

Run: `php artisan route:list --path=api`
Expected: 66 rotas; as novas usam o parâmetro `{topico}`.

```bash
git add -A
git commit -m "feat: entidade topicos completa"
```

---

### Task 3: Entidade `posts` completa e fechamento da fase

As respostas dentro de um tópico. Segunda tabela com duas FKs, e a que fica a
**dois saltos** de `usuarios` e de `categorias` — é aqui que a cascata de
múltiplos saltos volta ao projeto.

Esta task fecha a fase, então termina com as verificações completas e o README.

**Files:**
- Create: `database/migrations/2026_08_20_000007_create_posts_table.php`
- Create: `app/Models/Post.php`
- Create: `database/factories/PostFactory.php`
- Create: `app/Http/Controllers/PostController.php`
- Modify: `app/Models/Usuario.php` (adiciona `posts()`)
- Modify: `app/Models/Topico.php` (adiciona `posts()`)
- Modify: `routes/api.php`
- Modify: `README.md`
- Test: `tests/Feature/PostApiTest.php`

**Interfaces:**
- Consumes: `App\Models\Topico` e `Database\Factories\TopicoFactory` (Task 2); `App\Models\Usuario`, `App\Models\Categoria` e suas factories.
- Produces:
  - `App\Models\Post` — tabela `posts`, `$fillable = ['topico_id', 'usuario_id', 'conteudo']`, relacionamentos `topico()` e `usuario()`.
  - `Database\Factories\PostFactory`.
  - `Usuario::posts()` e `Topico::posts()` — `HasMany`.
  - Rotas `posts.*` sob `/api/posts`, parâmetro `{post}`.

**Commit:** um só, no Step 10.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/PostApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Post;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lista_os_posts(): void
    {
        Post::factory()->count(3)->create();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_store_cria_post_e_retorna_201(): void
    {
        $topico = Topico::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Acontece comigo tambem, sempre no mesmo ponto.',
        ])
            ->assertCreated()
            ->assertJsonPath('topico_id', $topico->id)
            ->assertJsonPath('conteudo', 'Acontece comigo tambem, sempre no mesmo ponto.');

        $this->assertDatabaseHas('posts', ['topico_id' => $topico->id]);
    }

    public function test_o_mesmo_topico_pode_ter_varios_posts(): void
    {
        $post = Post::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $post->topico_id,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Segunda resposta no mesmo topico.',
        ])->assertCreated();

        $this->assertDatabaseCount('posts', 2);
    }

    public function test_store_sem_dados_retorna_422(): void
    {
        $this->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['topico_id', 'usuario_id', 'conteudo']);
    }

    public function test_store_com_topico_inexistente_retorna_422(): void
    {
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => 999999,
            'usuario_id' => $usuario->id,
            'conteudo' => 'Conteudo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('topico_id');
    }

    public function test_store_com_usuario_inexistente_retorna_422(): void
    {
        $topico = Topico::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => 999999,
            'conteudo' => 'Conteudo',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario_id');
    }

    public function test_store_com_conteudo_longo_demais_retorna_422(): void
    {
        $topico = Topico::factory()->create();
        $usuario = Usuario::factory()->create();

        $this->postJson('/api/posts', [
            'topico_id' => $topico->id,
            'usuario_id' => $usuario->id,
            'conteudo' => str_repeat('a', 5001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('conteudo');
    }

    public function test_apagar_topico_apaga_seus_posts_em_cascata(): void
    {
        $post = Post::factory()->create();

        Topico::findOrFail($post->topico_id)->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_apagar_o_autor_do_post_apaga_o_post_mas_nao_o_topico(): void
    {
        $post = Post::factory()->create();

        // A factory da autores distintos ao topico e ao post, entao apagar o
        // autor do post exercita a FK do proprio post, nao a herdada.
        Usuario::findOrFail($post->usuario_id)->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('topicos', ['id' => $post->topico_id]);
    }

    public function test_apagar_o_autor_do_topico_apaga_topico_e_posts(): void
    {
        $post = Post::factory()->create();
        $topico = Topico::findOrFail($post->topico_id);

        // Apaga o autor do TOPICO, nao o do post: a cascata tem que percorrer
        // usuario -> topico -> post.
        Usuario::findOrFail($topico->usuario_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_apagar_a_categoria_apaga_topico_e_posts(): void
    {
        $post = Post::factory()->create();
        $topico = Topico::findOrFail($post->topico_id);

        // O outro caminho de dois saltos: categoria -> topico -> post.
        Categoria::findOrFail($topico->categoria_id)->delete();

        $this->assertDatabaseMissing('topicos', ['id' => $topico->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_o_topico_expoe_seus_posts(): void
    {
        $post = Post::factory()->create();

        $topico = Topico::with('posts')->findOrFail($post->topico_id);

        $this->assertCount(1, $topico->posts);
        $this->assertSame($post->id, $topico->posts->first()->id);
    }

    public function test_o_usuario_expoe_seus_posts(): void
    {
        $post = Post::factory()->create();

        $usuario = Usuario::with('posts')->findOrFail($post->usuario_id);

        $this->assertCount(1, $usuario->posts);
        $this->assertSame($post->id, $usuario->posts->first()->id);
    }

    public function test_show_retorna_o_post(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('id', $post->id);
    }

    public function test_show_de_id_inexistente_retorna_404(): void
    {
        $this->getJson('/api/posts/999')->assertNotFound();
    }

    public function test_update_altera_o_conteudo(): void
    {
        $post = Post::factory()->create(['conteudo' => 'Conteudo antigo']);

        $this->putJson("/api/posts/{$post->id}", ['conteudo' => 'Conteudo novo'])
            ->assertOk()
            ->assertJsonPath('conteudo', 'Conteudo novo');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'conteudo' => 'Conteudo novo',
        ]);
    }

    public function test_update_troca_o_topico(): void
    {
        $post = Post::factory()->create();
        $outro = Topico::factory()->create();

        $this->putJson("/api/posts/{$post->id}", ['topico_id' => $outro->id])
            ->assertOk()
            ->assertJsonPath('topico_id', $outro->id);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'topico_id' => $outro->id,
        ]);
    }

    public function test_update_de_id_inexistente_retorna_404(): void
    {
        $this->putJson('/api/posts/999', ['conteudo' => 'x'])->assertNotFound();
    }

    public function test_destroy_remove_o_post_e_retorna_204(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_de_id_inexistente_retorna_404(): void
    {
        $this->deleteJson('/api/posts/999')->assertNotFound();
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=PostApiTest`

Expected: FAIL com `Class "App\Models\Post" not found`.

- [ ] **Step 3: Criar a migration**

Criar `database/migrations/2026_08_20_000007_create_posts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // ->index() explicito: constrained() sozinho emite so a clausula
            // FOREIGN KEY. O MySQL cria um indice de apoio como efeito
            // colateral, o SQLite nao — e o SQLite e o banco padrao aqui.
            // O indice em topico_id cobre o CREATE INDEX idx_topico do DDL.
            $table->foreignId('topico_id')->index()->constrained('topicos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->index()->constrained('usuarios')->cascadeOnDelete();
            $table->text('conteudo');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

- [ ] **Step 4: Criar o Model**

Criar `app/Models/Post.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $table = 'posts';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'topico_id',
        'usuario_id',
        'conteudo',
    ];

    protected function casts(): array
    {
        return [
            'topico_id' => 'integer',
            'usuario_id' => 'integer',
        ];
    }

    public function topico(): BelongsTo
    {
        return $this->belongsTo(Topico::class, 'topico_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
```

- [ ] **Step 5: Criar a Factory**

Criar `database/factories/PostFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Topico;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * topico_id e usuario_id sao factories independentes, entao o autor do
     * topico e o autor do post sao usuarios distintos — o que e o que permite
     * distinguir a cascata de um salto da de dois.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topico_id' => Topico::factory(),
            'usuario_id' => Usuario::factory(),
            'conteudo' => fake()->paragraph(),
        ];
    }
}
```

- [ ] **Step 6: Adicionar `posts()` ao Model `Usuario`**

Em `app/Models/Usuario.php`, acrescentar o método abaixo depois de
`topicos()`. O import de `HasMany` já existe no arquivo:

```php
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'usuario_id');
    }
```

- [ ] **Step 7: Adicionar `posts()` ao Model `Topico`**

Em `app/Models/Topico.php`, acrescentar o import
`use Illuminate\Database\Eloquent\Relations\HasMany;` junto ao import de
`BelongsTo`, e o método abaixo depois de `categoria()`:

```php
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'topico_id');
    }
```

- [ ] **Step 8: Gerar e implementar o controller, e registrar a rota**

Run: `php artisan make:controller PostController --api`

Substituir todo o conteúdo de `app/Http/Controllers/PostController.php` por:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Post::all());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate($this->regras());

        $post = Post::create($dados);

        return response()->json($post, 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $dados = $request->validate($this->regras($post));

        $post->update($dados);

        return response()->json($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(null, 204);
    }

    /**
     * Regras compartilhadas por store e update. Esta tabela nao tem
     * constraint de unicidade, entao nao precisa da Request.
     *
     * @return array<string, mixed>
     */
    private function regras(?Post $existente = null): array
    {
        $obrigatorio = $existente === null ? 'required' : 'sometimes';

        return [
            'topico_id' => "$obrigatorio|integer|exists:topicos,id",
            'usuario_id' => "$obrigatorio|integer|exists:usuarios,id",
            'conteudo' => "$obrigatorio|string|max:5000",
        ];
    }
}
```

Em `routes/api.php`, acrescentar o import
`use App\Http\Controllers\PostController;` mantendo a ordem alfabética, e a
rota **antes** do bloco de autenticação no fim do arquivo:

```php
Route::apiResource('posts', PostController::class)
    ->parameters(['posts' => 'post']);
```

- [ ] **Step 9: Verificações de fechamento da fase**

Run: `php artisan test --filter=PostApiTest`
Expected: PASS, 20 testes.

Run: `php artisan test`
Expected: PASS, **246 testes**.

Run: `php artisan migrate:fresh --seed`
Expected: 17 migrations rodam sem erro, com as três novas na ordem
`categorias`, `topicos`, `posts` ao final. Os seeders do catálogo e do demo
continuam funcionando.

Run: `php artisan route:list --path=api`
Expected: **71 rotas** — 5 para cada um dos 14 recursos, mais `POST api/login`.

- [ ] **Step 10: Documentar e commitar**

Em `README.md`, na seção "LaaC Lab — API", substituir a linha que lista os
recursos disponíveis por:

```markdown
Recursos disponíveis: `usuarios`, `jogos`, `plataformas`, `jogos_plataformas`,
`biblioteca_usuario`, `avaliacoes`, `curtidas_avaliacoes`, `bugometro_status`,
`metricas_bug`, `relatos_bug`, `historico_bug`, `categorias`, `topicos`,
`posts`.
```

E acrescentar, logo abaixo do parágrafo que fala do Bugômetro:

```markdown
O fórum são três recursos encadeados: uma `categoria` contém `topicos`, e cada
tópico contém `posts`. A cascata percorre os dois saltos — apagar um usuário
remove os tópicos que ele abriu **e** os posts dentro deles, e apagar uma
categoria faz o mesmo. Apagar só o autor de um post remove o post e deixa o
tópico de pé.

Nenhuma das três tem constraint de unicidade: duas categorias podem ter o mesmo
nome, e dois posts podem ter o mesmo conteúdo. `conteudo` em `posts` aceita até
5000 caracteres.
```

Não alterar o resto do README.

```bash
git add -A
git commit -m "feat: entidade posts completa e documentacao da Fase 4"
```

---

## Estado esperado ao fim do plano

- 17 migrations, 14 models de domínio, 14 controllers de API, 14 factories.
- 71 rotas sob `/api` (70 REST + `POST /api/login`), cobertas por 246 testes verdes.
- As quatro FKs desta fase, cada uma com o seu próprio teste de cascata, mais dois testes de cascata de dois saltos.
- 3 commits, um por entidade completa:

| Commit | Conteúdo |
|---|---|
| Task 1 | entidade `categorias` completa |
| Task 2 | entidade `topicos` completa (duas FKs) |
| Task 3 | entidade `posts` completa + README |

## Próximo passo (fora deste plano)

Fase 5 — gamificação e feed: `badges`, `usuarios_badges`, `notificacoes` e
`atividades`. Três coisas para o plano dela:

- **`usuarios_badges` é a quarta entidade com par único composto**, o gatilho
  registrado em `pendencias.md` B3 para extrair `regras()`/`completaOPar()` num
  trait em vez de fazer a quarta cópia manual.
- **`usuarios_badges` cria em `conquistado_em`** — terceiro timestamp irregular
  do projeto, depois de `adicionado_em` e `registrado_em`.
- **`atividades.referencia_id` é um id solto**, sem FK no DDL: aponta para uma
  linha de outra tabela sem dizer qual. Decidir se fica como está (fiel ao DDL)
  ou se ganha um discriminador.

Com a Fase 5 o entregável fica completo, e é quando as pendências acumuladas em
[docs/pendencias.md](../../pendencias.md) devem ser decididas de uma vez.
