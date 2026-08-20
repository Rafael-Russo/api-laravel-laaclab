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
