<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;

// Create a request with valid payload
$data = [
    'trip_id' => 'test-trip-' . time(),
    'user_id' => 1,
    'lat' => 12.345678,
    'lng' => 98.765432,
    'accuracy' => 4.5,
    'speed' => 2.2,
    'bearing' => 180,
];

$request = Request::create('/internal-test', 'POST', $data);

$controller = new App\Http\Controllers\LocationController();
$response = $controller->store($request);

// If controller returned a response instance
if ($response instanceof \Illuminate\Http\JsonResponse) {
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo $response->getContent() . PHP_EOL;
} else {
    // Fallback: print var_export
    var_export($response);
}
