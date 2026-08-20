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
