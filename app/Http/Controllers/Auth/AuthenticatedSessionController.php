<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Ambil tenant jika ada
        $tenant = app()->has('tenant') ? app('tenant') : null;

        // Cek login
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Ambil user yang login
        $user = Auth::user(); // <-- ganti $request->user() dengan ini

        // ===== Cek Tenant =====
        if ($tenant) {
            $hasAccess = $user->roles()
                ->wherePivot('tenant_id', $tenant->id)
                ->exists();

            if (!$hasAccess && !$user->is_global) {
                Auth::logout();
                return response()->json(['message' => 'Unauthorized for this tenant'], 403);
            }
        } else {
            if (!$user->is_global) {
                Auth::logout();
                return response()->json(['message' => 'Unauthorized for pusat'], 403);
            }
        }

        // ===== Buat Token Sanctum =====
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
   public function destroy(Request $request)
{
    // Hapus token API saat ini
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}

}
