<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Orquestra os seeders de conteudo.
 *
 * Cada fase da API acrescenta o seu proprio seeder a esta lista, em vez de
 * engordar um arquivo unico: a Fase 3 tera o do Bugometro, a Fase 4 o do
 * forum. A ordem importa — o conteudo do demo depende do catalogo existir.
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
