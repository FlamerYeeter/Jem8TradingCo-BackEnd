<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\CheckoutController;

// Ensure test user
$user = Account::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'first_name' => 'Test',
        'last_name' => 'User',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

// Ensure products
$p1 = Product::firstOrCreate(['product_id' => 2], [
    'product_name' => 'Test Product A',
    'price' => 9.99,
    'product_stocks' => 10,
    'description' => 'Auto-created product A',
]);

$p2 = Product::firstOrCreate(['product_id' => 3], [
    'product_name' => 'Test Product B',
    'price' => 12.50,
    'product_stocks' => 10,
    'description' => 'Auto-created product B',
]);

// Clear existing cart and create cart items
Cart::where('user_id', $user->id)->delete();
Cart::create(['user_id' => $user->id, 'product_id' => $p1->product_id, 'quantity' => 1, 'total' => 9.99, 'status' => 'active']);
Cart::create(['user_id' => $user->id, 'product_id' => $p2->product_id, 'quantity' => 2, 'total' => 25.00, 'status' => 'active']);

// Build request
$request = Request::create('/api/checkout', 'POST', [
    'payment_method' => 'card',
    'payment_reference' => 'TEST123',
    'shipping_fee' => 0,
]);

$request->setUserResolver(function () use ($user) { return $user; });

$ctrl = new CheckoutController();
$resp = $ctrl->store($request);

// Print response
http_response_code($resp->getStatusCode());
echo $resp->getContent() . PHP_EOL;
