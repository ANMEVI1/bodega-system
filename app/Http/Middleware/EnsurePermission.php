<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Verify authenticated user is active and holds at least one required RBAC permission.
     */
    public function handle(Request $request, Closure $next, string $permissions = ''): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $freshUser = $user->newQuery()->with('role')->find($user->getKey());
        if (! $freshUser) {
            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu sesión ya no es válida.'], 403);
            }

            return redirect()->route('login');
        }

        $user->setRawAttributes($freshUser->getAttributes(), true);
        $user->setRelation('role', $freshUser->role);
        $user->flushPermissionCache();

        if (! $user->isActive()) {
            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu cuenta se encuentra desactivada.'], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta se encuentra desactivada. Contacta al administrador principal.',
            ]);
        }

        $required = array_values(array_filter(array_map('trim', explode('|', $permissions))));
        if (! empty($required) && ! $user->hasAnyPermission($required)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tienes permisos asignados para realizar esta acción.',
                ], 403);
            }

            abort(403, 'No tienes permisos asignados para acceder a este módulo.');
        }

        return $next($request);
    }
}
