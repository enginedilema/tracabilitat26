<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class RolsProvaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Els rols de prova nomes es poden assignar en local.');
        }

        $comptes = [
            'admin@tracabilitat.test' => 'admin',
            'tecnic@tracabilitat.test' => 'tecnic',
        ];

        foreach ($comptes as $email => $rol) {
            $usuari = User::query()->where('email', $email)->first();
            if (! $usuari) {
                throw new RuntimeException("Compte inexistent: {$email}. Executa abans UsuarisProvaSeeder.");
            }
            $usuari->forceFill(['rol' => $rol, 'actiu' => true])->save();
        }

        $this->command->info('Rols de prova assignats. No s’han modificat les contrasenyes.');
    }
}
