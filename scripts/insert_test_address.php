<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = [
    'user_id' => 2,
    'type' => 'personal',
    'company_name' => null,
    'company_role' => null,
    'company_number' => null,
    'company_email' => null,
    'street' => '5677 Taylor St.',
    'barangay' => 'Brgy. Pio Del Pilar',
    'city' => 'Makati City',
    'province' => 'Metro Manila',
    'postal_code' => '1230',
    'country' => 'Philippines',
    'status' => 'active',
];

$address = \App\Models\UserAddress::create($data);

print_r($address->toArray());
