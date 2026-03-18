<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAccounts();
        $this->seedUserAddresses();
        $this->seedCheckouts();
        $this->seedDeliveries();
        $this->seedContacts();
        $this->seedViews();
    }

    // ── Accounts ──────────────────────────────────────────────
    private function seedAccounts(): void
    {
        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Carlo', 'Liza', 'Marco', 'Nina', 'Paolo', 'Rosa',
            'Miguel', 'Clara', 'Ramon', 'Elena', 'Diego', 'Sofia', 'Luis', 'Carla', 'Andres', 'Bianca',
        ];
        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Rivera', 'Ramos', 'Dela Cruz',
            'Villanueva', 'Bautista', 'Aquino', 'Castillo', 'Morales', 'Gonzales', 'Hernandez', 'Lopez', 'Diaz', 'Perez',
        ];

        $accounts = [];
        for ($i = 0; $i < 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName  = $lastNames[array_rand($lastNames)];
            $createdAt = Carbon::now()->subDays(rand(0, 365));
            $verified  = rand(0, 1);

            $accounts[] = [
                'first_name'                    => $firstName,
                'last_name'                     => $lastName,
                'phone_number'                  => '09' . rand(100000000, 999999999),
                'email'                         => strtolower($firstName . '.' . $lastName . $i . '@gmail.com'),
                'password'                      => Hash::make('password'),
                'profile_image'                 => null,
                'email_verification_code'       => null,
                'email_verification_expires_at' => null,
                'password_reset_code'           => null,
                'password_reset_expires_at'     => null,
                'email_verified_at'             => $verified ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                'remember_token'                => null,
                'created_at'                    => $createdAt,
                'updated_at'                    => $createdAt,
            ];
        }

        DB::table('accounts')->insert($accounts);
        $this->command->info('✅ Accounts seeded (50)');
    }

    // ── User Addresses ─────────────────────────────────────────
    private function seedUserAddresses(): void
    {
        $accountIds = DB::table('accounts')->pluck('id')->toArray();

        $addresses = [
            ['address' => '123 Ayala Ave',      'city' => 'Makati'],
            ['address' => '456 BGC High Street', 'city' => 'Taguig'],
            ['address' => '789 Roxas Blvd',      'city' => 'Manila'],
            ['address' => '321 Ortigas Ave',     'city' => 'Pasig'],
            ['address' => '654 Quirino Highway', 'city' => 'Quezon City'],
            ['address' => '987 Shaw Blvd',       'city' => 'Mandaluyong'],
            ['address' => '111 Marcos Highway',  'city' => 'Antipolo'],
            ['address' => '222 Aguinaldo Hwy',   'city' => 'Cavite'],
            ['address' => '333 Magsaysay Ave',   'city' => 'Caloocan'],
            ['address' => '444 Rizal Ave',       'city' => 'Paranaque'],
        ];

        $companies = [
            ['name' => 'Santos Trading',    'role' => 'Owner'],
            ['name' => 'Reyes Enterprises', 'role' => 'Manager'],
            ['name' => 'Cruz and Co',       'role' => 'Procurement'],
            ['name' => 'Garcia Supplies',   'role' => 'CEO'],
            ['name' => 'Mendoza Corp',      'role' => 'Director'],
        ];

        $rows = [];
        foreach ($accountIds as $userId) {
            $addr    = $addresses[array_rand($addresses)];
            $company = $companies[array_rand($companies)];

            $rows[] = [
                'user_id'        => $userId,
                'company_name'   => $company['name'],
                'company_role'   => $company['role'],
                'company_number' => '02-' . rand(1000000, 9999999),
                'company_email'  => strtolower(str_replace(' ', '', $company['name'])) . '@business.com',
                'address'        => $addr['address'] . ', ' . $addr['city'],
                'status'         => rand(0, 1) ? 'active' : 'inactive',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        DB::table('user_addresses')->insert($rows);
        $this->command->info('✅ User Addresses seeded (50)');
    }

    // ── Checkouts ──────────────────────────────────────────────
    private function seedCheckouts(): void
    {
        $accountIds     = DB::table('accounts')->pluck('id')->toArray();
        $paymentMethods = ['gcash', 'cash', 'credit_card', 'bank_transfer', 'maya'];

        $checkouts = [];
        for ($i = 0; $i < 80; $i++) {
            $createdAt = Carbon::now()->subDays(rand(0, 365));
            $isPaid    = rand(0, 1);

            $checkouts[] = [
                'user_id'              => $accountIds[array_rand($accountIds)],
                'cart_id'              => null,
                'discount_id'          => null,
                'payment_method'       => $paymentMethods[array_rand($paymentMethods)],
                'payment_details'      => json_encode(['reference' => 'REF-' . strtoupper(uniqid())]),
                'shipping_fee'         => rand(50, 200),
                'paid_amount'          => rand(500, 15000),
                'paid_at'              => $isPaid ? $createdAt->copy()->addHours(rand(1, 12)) : null,
                'special_instructions' => rand(0, 1) ? 'Please handle with care.' : null,
                'created_at'           => $createdAt,
                'updated_at'           => $createdAt,
            ];
        }

        DB::table('checkouts')->insert($checkouts);
        $this->command->info('✅ Checkouts seeded (80)');
    }

    // ── Deliveries ─────────────────────────────────────────────
    // enum: 'processing', 'ready', 'on_the_way', 'delivered'
    private function seedDeliveries(): void
    {
        $checkoutIds = DB::table('checkouts')->pluck('checkout_id')->toArray();
        $statuses    = ['processing', 'ready', 'on_the_way', 'delivered'];

        $deliveries = [];
        foreach ($checkoutIds as $checkoutId) {
            $createdAt = Carbon::now()->subDays(rand(0, 365));

            $deliveries[] = [
                'checkout_id' => $checkoutId,
                'status'      => $statuses[array_rand($statuses)],
                'driver_id'   => rand(1, 5),
                'notes'       => rand(0, 1) ? 'Leave at front door.' : null,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ];
        }

        DB::table('deliveries')->insert($deliveries);
        $this->command->info('✅ Deliveries seeded (80)');
    }

    // ── Contacts ──────────────────────────────────────────────
    // enum: 'pending', 'read', 'replied'
        private function seedContacts(): void
    {
        $firstNames = ['Juan', 'Maria', 'Carlo', 'Ana', 'Liza', 'Marco', 'Nina', 'Paolo', 'Rosa', 'Miguel'];
        $lastNames  = ['Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Rivera', 'Ramos', 'Aquino'];

        $messages = [
            'I would like to inquire about your products.',
            'Can I get a bulk order discount?',
            'When will my order arrive?',
            'I have an issue with my recent order.',
            'Do you deliver to Cebu?',
            'What are your payment options?',
            'I want to request a product catalog.',
            'Is the item still available in stock?',
            'Can I change my delivery address?',
            'I would like to follow up on my order.',
        ];

        $statuses = ['pending', 'read', 'replied'];

        // ✅ FK references users table, not accounts
        $userIds = DB::table('users')->pluck('id')->toArray();

        // if no users exist, set sender as null (it's nullable)
        $contacts = [];
        for ($i = 0; $i < 20; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName  = $lastNames[array_rand($lastNames)];
            $createdAt = Carbon::now()->subDays(rand(0, 90));

            $contacts[] = [
                'sender'       => !empty($userIds) ? $userIds[array_rand($userIds)] : null,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone_number' => '09' . rand(100000000, 999999999),
                'email'        => strtolower($firstName . '.' . $lastName . $i . '@gmail.com'),
                'message'      => $messages[array_rand($messages)],
                'status'       => $statuses[array_rand($statuses)],
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
            ];
        }

        

        DB::table('contacts')->insert($contacts);
        $this->command->info('✅ Contacts seeded (20)');


        
    }
    private function seedViews(): void
{
    $rows = [];
    for ($i = 30; $i >= 0; $i--) {
        $date = Carbon::now()->subDays($i);
        $rows[] = [
            'views'      => rand(100, 500),
            'visits'     => rand(50, 300),
            'page'       => 'dashboard',
            'user_id'    => null,
            'ip_address' => '127.0.0.' . rand(1, 255),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    DB::table('dashboards')->insert($rows);
    $this->command->info('✅ Dashboard views seeded (30 days)');
}

}