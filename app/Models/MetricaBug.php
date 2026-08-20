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
