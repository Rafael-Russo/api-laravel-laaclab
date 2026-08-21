<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topico extends Model
{
    /** @use HasFactory<\Database\Factories\TopicoFactory> */
    use HasFactory;

    protected $table = 'topicos';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'usuario_id',
        'categoria_id',
        'titulo',
    ];

    protected function casts(): array
    {
        return [
            'usuario_id' => 'integer',
            'categoria_id' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
