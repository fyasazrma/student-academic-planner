<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request only when the authenticated user has one of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowedRoles = [];

        foreach ($roles as $role) {
            $allowedRole = UserRole::tryFrom($role);

            if ($allowedRole !== null) {
                $allowedRoles[] = $allowedRole;
            }
        }

        if ($allowedRoles === [] || ! in_array($user->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
