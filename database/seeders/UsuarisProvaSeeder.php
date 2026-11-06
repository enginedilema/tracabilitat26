<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsuarisProvaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(
                'Aquest seeder nomes es pot executar en local.'
            );
        }

        $password = config('tracabilitat.seed_password');



        $usuaris = [
            [
                'name' => 'Administrador Prova',
                'email' => 'admin@tracabilitat.test',
            ],
            [
                'name' => 'Tecnic Prova',
                'email' => 'tecnic@tracabilitat.test',
            ],
        ];

        foreach ($usuaris as $usuari) {
            User::updateOrCreate(
                ['email' => $usuari['email']],
                [
                    'name' => $usuari['name'],
                    'password' => Hash::make($password),
                ]
            );
        }

        $this->command->info(
            'Usuaris de prova creats o actualitzats.'
        );
    }
}
