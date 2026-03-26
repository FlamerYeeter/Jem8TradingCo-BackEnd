<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    /**
     * Routes to SKIP — never log these
     */
    private array $skipRoutes = [
        'api/sanctum/csrf-cookie',
        'sanctum/csrf-cookie',
        'api/admin/activity-logs',  // prevent infinite loop
        'api/login',                // logged manually in AuthController
        'api/logout',               // logged manually in AuthController
        'api/refresh',
    ];

    /**
     * HTTP methods to log
     * Remove 'GET' if you don't want to log every page/product view
     */
    private array $logMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request); // let the request finish first

        try {
            // Only log authenticated users
            if (!Auth::check()) {
                return $response;
            }

            // Skip certain routes
            foreach ($this->skipRoutes as $skip) {
                if (str_contains($request->path(), $skip)) {
                    return $response;
                }
            }

            // Only log allowed methods
            if (!in_array($request->method(), $this->logMethods)) {
                return $response;
            }

            // Only log successful responses (2xx)
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return $response;
            }

            $this->log($request, $response);

        } catch (\Exception $e) {
            // NEVER break the app because of logging
            Log::error('ActivityLog Middleware Error: ' . $e->getMessage());
        }

        return $response;
    }

    private function log(Request $request, Response $response): void
    {
        $user     = Auth::user();
        $method   = $request->method();
        $path     = $request->path();
        $segments = explode('/', $path);

        $resource   = $this->detectResource($segments);
        $resourceId = $this->detectId($segments);
        $action     = $this->buildAction($method, $resource);
        $category   = $this->detectCategory($resource, $path);
        $description = $this->buildDescription($user, $method, $resource, $resourceId, $request);

        ActivityLog::log($user, $action, $category, [
            'reference_table'     => $resource ? str_replace('-', '_', $resource) : null,
            'reference_id'        => $resourceId ?? $this->extractIdFromResponse($response),
            'product_unique_code' => $request->input('order_code') ?? $request->input('product_unique_code') ?? null,
            'mode_of_payment'     => $request->input('payment_method') ?? $request->input('mode_of_payment') ?? null,
            'amount'              => $request->input('amount') ?? $request->input('paid_amount') ?? null,
            'description'         => $description,
        ]);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Get the main resource from URL segments
     * e.g. ['api', 'admin', 'products', '5'] → 'products'
     */
    private function detectResource(array $segments): ?string
    {
        $filtered = array_values(
            array_filter($segments, fn($s) => !in_array($s, ['api', 'admin', '']))
        );

        foreach ($filtered as $segment) {
            if (!is_numeric($segment)) {
                return $segment; // e.g. 'products', 'orders', 'contacts'
            }
        }

        return null;
    }

    /**
     * Get numeric ID from URL
     * e.g. /api/products/5 → 5
     */
    private function detectId(array $segments): ?int
    {
        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                return (int) $segment;
            }
        }
        return null;
    }

    /**
     * Build human-readable action
     * e.g. GET + products → "Viewed product"
     *      POST + orders  → "Created order"
     */
    private function buildAction(string $method, ?string $resource): string
    {
        $label = $resource ? ucfirst(rtrim($resource, 's')) : 'page';

        return match ($method) {
            'GET'    => "Viewed $label",
            'POST'   => "Created $label",
            'PUT',
            'PATCH'  => "Updated $label",
            'DELETE' => "Deleted $label",
            default  => "Accessed $label",
        };
    }

    /**
     * Map resource to category
     */
    private function detectCategory(?string $resource, string $path): string
    {
        return match (true) {
            in_array($resource, ['orders', 'order'])                   => 'orders',
            in_array($resource, ['checkouts', 'checkout', 'payments']) => 'payments',
            in_array($resource, ['contacts', 'contact', 'messages'])   => 'contacts',
            in_array($resource, ['products', 'product', 'stocks'])     => 'stock',
            in_array($resource, ['blogs', 'blog', 'posts'])            => 'blogs',
            in_array($resource, ['backups', 'backup'])                 => 'backups',
            in_array($resource, ['accounts', 'account', 'users'])      => 'account',
            in_array($resource, ['register'])                          => 'account_creation',
            in_array($resource, ['emails', 'email'])                   => 'emails',
            str_contains($path, 'admin')                               => 'admin_view',
            default                                                    => 'other',
        };
    }

    /**
     * Build readable description with extra context from request
     * e.g. "Juan viewed Product #5"
     *      "Maria created Order: ORDER-001"
     */
    private function buildDescription($user, string $method, ?string $resource, ?int $id, Request $request): string
    {
        $name   = $user->name ?? 'Unknown';
        $label  = $resource ? ucfirst(rtrim($resource, 's')) : 'page';
        $idText = $id ? " #$id" : '';
        $extra  = '';

        // Pull extra context from request body
        if ($request->has('title'))    $extra = ': ' . $request->input('title');
        if ($request->has('name'))     $extra = ': ' . $request->input('name');
        if ($request->has('email'))    $extra = ' (' . $request->input('email') . ')';
        if ($request->has('subject'))  $extra = ': ' . $request->input('subject');
        if ($request->has('order_code')) $extra = ': ' . $request->input('order_code');

        $verb = match ($method) {
            'GET'    => 'viewed',
            'POST'   => 'created',
            'PUT',
            'PATCH'  => 'updated',
            'DELETE' => 'deleted',
            default  => 'accessed',
        };

        return "$name $verb $label$idText$extra";
        // e.g. "Juan viewed Product #5"
        // e.g. "Maria created Order: ORDER-001"
        // e.g. "Admin deleted Blog: How to cook rice"
    }

    /**
     * Extract new record ID from JSON response body
     * e.g. { "data": { "id": 5 } } → 5
     */
    private function extractIdFromResponse(Response $response): ?int
    {
        try {
            $body = json_decode($response->getContent(), true);
            return $body['data']['id']
                ?? $body['data']['order_id']
                ?? $body['data']['checkout_id']
                ?? $body['data']['message_id']
                ?? $body['data']['blog_id']
                ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}