<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AppDevTeamSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Mark Cyrus Mendoza', 'email' => 'mark.c.mendoza@example.com'],
            ['name' => 'Miguel Carlo Tapalla', 'email' => 'miguel.c.tapalla@example.com'],
            ['name' => 'Marvin Tomales', 'email' => 'marvin.tomales@example.com'],
            ['name' => 'Eivrian Nicholson S. Pacis', 'email' => 'eivrian.pacis@example.com'],
            ['name' => 'Thomas Adrian M. Naguit', 'email' => 'thomas.naguit@example.com'],
            ['name' => 'Jhimar Carl U. Motea', 'email' => 'jhimar.motea@example.com'],
            ['name' => 'Clark Kent B. Raguhos', 'email' => 'clark.raguhos@example.com'],
            ['name' => 'Francis Dave C. Sulit', 'email' => 'francis.sulit@example.com'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
