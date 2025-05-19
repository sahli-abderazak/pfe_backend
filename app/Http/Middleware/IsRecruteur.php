<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsRecruteur
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'recruteur') {
            return $next($request);
        }

        return response()->json(['message' => 'Accès non autorisé (recruteur requis)'], 403);
    }
}

