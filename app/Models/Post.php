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
