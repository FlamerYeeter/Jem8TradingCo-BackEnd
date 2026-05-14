<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDepartment
{
    public function handle(Request $request, Closure $next, $allowed = null)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        if (method_exists($user, 'is_admin') && ($user->is_admin ?? false)) {
            return $next($request);
        }
        if (($user->is_admin ?? false) || in_array(strtolower($user->role ?? ''), ['admin','administrator'])) {
            return $next($request);
        }

        if (!$allowed) return $next($request);

        $allowedArr = array_map('strtolower', array_map('trim', explode(',', $allowed)));
        $dept = strtolower(trim($user->department ?? ''));

        if (in_array($dept, $allowedArr)) return $next($request);

        return response()->json(['message' => 'Unauthorized (department)'], 403);
    }
}
