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
