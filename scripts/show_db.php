<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "ENV DB_DATABASE=" . env('DB_DATABASE') . "\n";
echo "config database=" . config('database.connections.mysql.database') . "\n";
echo "DB host=" . config('database.connections.mysql.host') . "\n";
echo "DB user=" . config('database.connections.mysql.username') . "\n";
