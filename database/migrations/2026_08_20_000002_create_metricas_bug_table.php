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
            $table->foreignId('jogo_id')->index()->constrained('jogos')->cascadeOnDelete();
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
