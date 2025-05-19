<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Vérifie si l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Vérifie si l'utilisateur a le rôle requis
        if ($request->user()->role !== $role) {
            return response()->json(['message' => 'Unauthorized. Required role: ' . $role], 403);
        }

        return $next($request);
    }
}