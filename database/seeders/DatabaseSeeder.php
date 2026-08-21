<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Orquestra os seeders de conteudo.
 *
 * Uma fase que precise de dados de demonstracao acrescenta o seu proprio
 * seeder a esta lista, em vez de engordar um arquivo unico. A ordem importa —
 * o conteudo do demo depende do catalogo existir.
 *
 * O Bugometro (Fase 3) entrou sem seeder: as telas que o consomem ainda nao
 * existem, e semear metrica sem tela para exibi-la seria dado morto.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CatalogoSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
