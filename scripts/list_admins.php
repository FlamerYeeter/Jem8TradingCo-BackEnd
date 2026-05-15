<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;

$admins = Account::where('role', 'admin')
    ->get(['id','first_name','last_name','email','phone_number','role','created_at']);

echo $admins->toJson(JSON_PRETTY_PRINT);
