<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // 🚫 Jangan redirect ke login, balikin JSON
        if (! $request->expectsJson()) {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.'
            ], 401));
        }
    }
}
