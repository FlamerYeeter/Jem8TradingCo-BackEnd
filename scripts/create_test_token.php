<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = $argv[1] ?? 'postman@example.com';
$password = $argv[2] ?? 'password';

$user = User::where('email', $email)->first();
if (! $user) {
    $user = User::create([
        'name' => 'Postman User',
        'email' => $email,
        'password' => bcrypt($password),
    ]);
    echo "Created user: {$email}\n";
} else {
    echo "Found user: {$email}\n";
}

$token = $user->createToken('postman-token')->plainTextToken;
echo "Personal access token (PLAINTEXT):\n" . $token . "\n";
echo "Set this value as cookie 'jem8_token' in Postman (or send as cookie).\n";
