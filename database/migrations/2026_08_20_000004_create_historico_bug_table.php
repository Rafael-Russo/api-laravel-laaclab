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
            $table->foreignId('jogo_id')->index()->constrained('jogos')->cascadeOnDelete();
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
