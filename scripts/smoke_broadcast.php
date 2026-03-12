<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Message;
use App\Events\NewMessage;

echo "Creating test message...\n";

$m = Message::create([
    'chatroom_id' => 1,
    'messages' => 'Smoke test message from script',
    'status' => 1,
    'cart_id' => null,
]);

echo "Dispatching NewMessage event...\n";
event(new NewMessage($m));

echo "Done. Check storage/logs/laravel.log for broadcast entry.\n";
