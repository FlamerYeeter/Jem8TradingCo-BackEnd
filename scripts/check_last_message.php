<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;

$m = Message::orderBy('created_at', 'desc')->first();

if ($m) {
    echo $m->toJson(JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No messages found.\n";
}
