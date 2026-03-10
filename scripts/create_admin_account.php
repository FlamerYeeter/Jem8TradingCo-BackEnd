<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;

$email = 'admin@example.com';
$password = 'adminsecret';

$account = Account::where('email', $email)->first();
if (! $account) {
    $account = Account::create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'phone_number' => '09990001111',
        'email' => $email,
        'password' => bcrypt($password),
        'email_verified_at' => now(),
    ]);
    echo "Created admin account id: {$account->id}\n";
} else {
    echo "Admin account exists id: {$account->id}\n";
}

$token = $account->createToken('admin_token')->plainTextToken;
echo "TOKEN: {$token}\n";
