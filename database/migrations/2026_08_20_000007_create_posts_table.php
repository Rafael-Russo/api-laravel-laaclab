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
