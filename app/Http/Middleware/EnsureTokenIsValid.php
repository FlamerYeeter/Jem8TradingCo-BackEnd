<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    private function isValidToken(String $token)
    {
        // Implement your token validation logic here
        Log::debug($token . " from middleware");
        return !empty($token); // or false based on validation
    }
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('jem8_token');

        if(!$token) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Token is invalid',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Attach authenticated user to request
        $request->setUserResolver(function () use ($accessToken) {
            return $accessToken->tokenable;
        });

        return $next($request);
    }
}
