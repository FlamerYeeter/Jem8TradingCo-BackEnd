<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accounts')->insertOrIgnore([
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone_number' => '09170000001',
                'email' => 'admin@example.com',
                'email_verified_at' => Carbon::now(), // verified
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Normal',
                'last_name' => 'User',
                'phone_number' => '09170000002',
                'email' => 'user@example.com',
                'email_verified_at' => Carbon::now(), // verified
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}