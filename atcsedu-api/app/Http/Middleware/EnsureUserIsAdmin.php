<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin access is required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
