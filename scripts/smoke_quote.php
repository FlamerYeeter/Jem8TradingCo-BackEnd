<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $ctrl = app(\App\Http\Controllers\QuoteController::class);

    $payload = [
        'contact' => [
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'phone' => '09000000002',
            'company' => 'University of Makati',
        ],
        'items' => [[ 'product_id' => 3, 'quantity' => 1 ]],
        'notes' => null,
        'order_reference' => null,
    ];

    $req = \App\Http\Requests\StoreContactRequest::create('/quotes', 'POST', $payload);
    // copy files if needed (none here)
    $res = $ctrl->store($req);
    echo "QUOTE CREATE RESPONSE:\n" . $res->getContent() . "\n";

    // Show latest product_requests
    $pr = \App\Models\ProductRequest::orderBy('id','desc')->first();
    if ($pr) {
        echo "LATEST PRODUCT_REQUEST:\n" . json_encode($pr->toArray()) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
