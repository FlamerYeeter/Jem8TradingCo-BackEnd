<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Review;
use App\Models\User;

$user = User::firstOrCreate([
    'email' => 'test@example.com'
], [
    'name' => 'Test User',
    'password' => bcrypt('secret')
]);

$review = Review::create([
    'product_id' => 2,
    'user_id' => $user->id,
    'rating' => 5,
    'review_text' => 'Great product!',
    'status' => 'pending',
]);

echo "Created review id: {$review->review_id}\n";
