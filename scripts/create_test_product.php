<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Category;

$c = Category::firstOrCreate([
    'category_name' => 'Test Category'
]);

$p = Product::first();
if (!$p) {
    $p = Product::create([
        'product_name' => 'Test Product',
        'category_id' => $c->category_id,
        'product_stocks' => 10,
        'description' => 'A test product',
        'price' => 9.99,
        'isSale' => false,
    ]);
    echo "Created product id: {$p->product_id}\n";
} else {
    echo "Product exists id: {$p->product_id}\n";
}
