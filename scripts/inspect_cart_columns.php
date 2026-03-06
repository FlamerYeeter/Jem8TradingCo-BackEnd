<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$rows = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cart'");
if (empty($rows)) {
    echo "cart table not found\n";
} else {
    foreach ($rows as $r) {
        echo "{$r->COLUMN_NAME} | {$r->COLUMN_TYPE} | nullable: {$r->IS_NULLABLE} | key: {$r->COLUMN_KEY} | extra: {$r->EXTRA}\n";
    }
}
