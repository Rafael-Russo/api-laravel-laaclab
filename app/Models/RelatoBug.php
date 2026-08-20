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
