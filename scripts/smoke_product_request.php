<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $ctrl = app(\App\Http\Controllers\ProductRequestController::class);

    // Create product request
    $req = Illuminate\Http\Request::create('/product-requests', 'POST', [
        'product' => json_encode(['id' => 1, 'name' => 'Smoke Test Product']),
        'description' => 'smoke test from script',
    ]);
    $create = $ctrl->store($req);
    echo "CREATE:\n" . $create->getContent() . "\n\n";

    $body = json_decode($create->getContent(), true);
    $id = $body['data']['id'] ?? null;

    if ($id) {
        // Update status to found
        $req2 = Illuminate\Http\Request::create("/admin/product-requests/{$id}/status", 'PATCH', ['status' => 'found']);
        $upd = $ctrl->updateStatus($req2, $id);
        echo "UPDATE:\n" . $upd->getContent() . "\n\n";

        // Create order as admin: set a real admin user if exists
        $req3 = Illuminate\Http\Request::create("/admin/product-requests/{$id}/create-order", 'POST', []);
        $admin = \App\Models\Account::first();
        if ($admin) {
            $req3->setUserResolver(function() use ($admin) { return $admin; });
        }
        $co = $ctrl->createOrder($req3, $id);
        echo "CREATE_ORDER:\n" . $co->getContent() . "\n\n";

        // Show single product request
        $show = $ctrl->show($id);
        echo "SHOW:\n" . $show->getContent() . "\n";
    } else {
        echo "No id returned from create\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
