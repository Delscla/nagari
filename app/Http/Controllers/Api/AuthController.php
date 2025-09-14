<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek apakah request datang dari subdomain tenant.
        $tenant = app()->has('tenant') ? app('tenant') : null;

        if ($tenant) {
            // --- ALUR LOGIN TENANT ---

            // Cari user berdasarkan email, tanpa mempedulikan status global.
            $user = User::where('email', $request->email)->first();

            // Jika user tidak ada atau password salah.
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Email atau password salah.'],
                ]);
            }

            // Periksa apakah user adalah user global. User global tidak boleh login dari subdomain.
            if ($user->is_global) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'User global tidak bisa login dari subdomain tenant.',
                ], 403);
            }

            // ========================= PERBAIKAN DI SINI =========================
            // Cek apakah user ini terdaftar di tenant yang diakses.
            // Kita secara eksplisit menyebutkan 'tenants.id' untuk menghindari galat ambiguous column.
            if (!$user->tenants()->where('tenants.id', $tenant->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak terdaftar di tenant ini.',
                ], 403);
            }
            // =====================================================================


        } else {
            // --- ALUR LOGIN GLOBAL (DOMAIN UTAMA) ---

            // Cari user yang merupakan user global.
            $user = User::where('email', $request->email)->where('is_global', true)->first();

            // Jika user global tidak ada atau password salah.
            if (!$user || !Hash::check($request->password, $user->password)) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya user global yang bisa login di domain utama.',
                ], 401);
            }
        }

        // Jika semua validasi lolos, buat token.
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => $user->load(['roles', 'subRoles']) // Muat relasi roles untuk ditampilkan
        ]);
    }


    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}

