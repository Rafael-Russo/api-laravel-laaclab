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
            // ->index() explicito: constrained() sozinho emite so a clausula
            // FOREIGN KEY. O MySQL cria um indice de apoio como efeito
            // colateral, o SQLite nao — e o SQLite e o banco padrao aqui.
            $table->foreignId('jogo_id')->index()->constrained('jogos')->cascadeOnDelete();
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
