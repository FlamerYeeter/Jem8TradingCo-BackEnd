<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Illuminate\Support\Str;

class AdminAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            ['department' => 'Sales', 'email' => 'admin.sales@example.com'],
            ['department' => 'Marketing', 'email' => 'admin.marketing@example.com'],
            ['department' => 'IT', 'email' => 'admin.it@example.com'],
            ['department' => 'Finance', 'email' => 'admin.finance@example.com'],
        ];

        foreach ($admins as $idx => $a) {
            Account::updateOrCreate(
                ['email' => $a['email']],
                [
                    'first_name' => $a['department'],
                    'last_name' => 'Admin',
                    'phone_number' => '090000000' . (80 + $idx),
                    'password' => bcrypt('password123'),
                    'role' => 'admin',
                    'department' => $a['department'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
