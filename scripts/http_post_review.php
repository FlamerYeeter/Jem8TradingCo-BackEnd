<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

$token = '1|prPJ2hyfIHnX8B1VvwP4qpCEsjLI6w1CF8fAGSWqc4d43906';
$client = new Client(['base_uri' => 'http://127.0.0.1:8000']);

try {
    $resp = $client->post('/api/products/2/reviews', [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'rating' => 5,
            'review_text' => 'API-created review via Guzzle PHP script',
        ],
        'http_errors' => false,
    ]);

    echo "HTTP/" . $resp->getProtocolVersion() . " " . $resp->getStatusCode() . "\n";
    foreach ($resp->getHeaders() as $k => $v) {
        echo $k . ": " . implode(", ", $v) . "\n";
    }
    echo "\n";
    echo $resp->getBody();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
