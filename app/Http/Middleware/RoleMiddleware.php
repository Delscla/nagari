<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $roleName)
    {
        $user = $request->user();
        $tenant = app()->has('tenant') ? app('tenant') : null;

        if (!$user || !$tenant) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasRole($roleName, $tenant->id)) {
            abort(403, "Anda tidak punya akses sebagai {$roleName}");
        }

        return $next($request);
    }
}
