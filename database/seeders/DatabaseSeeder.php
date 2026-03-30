<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\admin_backup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name'  => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Register product seeder
        $this->call(\Database\Seeders\ProductSeeder::class);
    }
}