<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        $userRole = strtolower($user->role?->name ?? '');

        $allowedRoles = array_map(fn($role) => strtolower($role), $roles);

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso.'
            ], 403);
        }

        return $next($request);
    }
}