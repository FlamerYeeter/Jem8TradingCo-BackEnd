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
    public function handle(Request $request, Closure $next)
    {

        // If session-based authentication (cookie + Sanctum) is present, allow it.
        if (auth()->check() || $request->user()) {
            return $next($request);
        }

        // Fallback to token stored in cookie named 'jem8_token'
        $token = $request->cookie('jem8_token');

        if (!$token) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No token found'
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Set authenticated user from personal access token
        auth()->setUser($accessToken->tokenable);

        return $next($request);
    }
}
