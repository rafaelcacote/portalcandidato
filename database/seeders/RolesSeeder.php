<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['admin', 'avaliador', 'candidato'] as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@portalcandidato.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'ativo' => true,
            ],
        );

        $admin->syncRoles(['admin']);
    }
}
