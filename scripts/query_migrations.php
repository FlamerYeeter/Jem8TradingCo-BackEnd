<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$rows = DB::table('migrations')->where('migration', 'like', '%checkout%')->get();
if ($rows->isEmpty()) {
    echo "no migrations rows for checkout found\n";
} else {
    foreach ($rows as $r) {
        echo "migration: {$r->migration} | batch: {$r->batch}\n";
    }
}
