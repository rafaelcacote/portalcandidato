<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed inicial: apenas papéis e usuário admin (sem dados de demonstração).
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
        ]);
    }
}
