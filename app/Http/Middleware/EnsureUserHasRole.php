<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'UNAUTHENTICATED', 'message' => 'Please log in.'], 401);
        }

        if (!$user->hasRole(...$roles)) {
            return response()->json(['error' => 'FORBIDDEN', 'message' => 'You do not have the required access.'], 403);
        }

        return $next($request);
    }
}
