<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jogo extends Model
{
    /** @use HasFactory<\Database\Factories\JogoFactory> */
    use HasFactory;

    protected $table = 'jogos';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'nome',
        'descricao',
        'genero',
        'classificacao',
        'desenvolvedora',
        'data_lancamento',
        'capa_url',
    ];

    protected function casts(): array
    {
        return [
            // Y-m-d para o JSON sair como "2020-12-10", nao datetime ISO.
            'data_lancamento' => 'date:Y-m-d',
        ];
    }
}
