<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SubRoleMiddleware
{
    public function handle($request, Closure $next, $subRoleName)
    {
        $user = $request->user();
        $tenant = app()->has('tenant') ? app('tenant') : null;

        if (!$user || !$tenant) {
            abort(403, 'Unauthorized');
        }

        if (!$user->hasSubRole($subRoleName, $tenant->id)) {
            abort(403, "Anda tidak punya akses sebagai {$subRoleName}");
        }

        return $next($request);
    }
}
