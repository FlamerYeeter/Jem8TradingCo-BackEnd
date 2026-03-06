<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;

$email = 'test@example.com';
$phone = '0912345678';

$account = Account::where('email', $email)->first();
if (!$account) {
    $account = Account::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone_number' => $phone,
        'email' => $email,
        'password' => bcrypt('secret'),
        'email_verified_at' => now(),
    ]);
    echo "Created account id: {$account->id}\n";
} else {
    echo "Account exists id: {$account->id}\n";
}
$token = $account->createToken('auth_token')->plainTextToken;
echo "TOKEN: {$token}\n";
