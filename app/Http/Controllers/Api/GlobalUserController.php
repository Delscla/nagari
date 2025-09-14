<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class GlobalUserController extends Controller
{
    // 📋 List semua user global
    public function index()
    {
        $users = User::where('is_global', true)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar semua user global',
            'data'    => $users,
            'meta'    => [
                'total' => $users->count()
            ]
        ], 200);
    }

    // ➕ Tambah user global
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email',
                'password' => 'required|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        // 🔎 Cek apakah email sudah ada
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User dengan email ini sudah ada'
            ], 409); // Conflict
        }

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'is_global' => true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil membuat akun global',
            'data'    => $user
        ], 201);
    }

    // 👀 Detail user global
    public function show($id)
    {
        $user = User::where('is_global', true)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail user global',
            'data'    => $user
        ], 200);
    }

    // ✏️ Update user global
    public function update(Request $request, $id)
    {
        $user = User::where('is_global', true)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name'     => 'sometimes|string|max:255',
                'email'    => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Global user berhasil diupdate',
            'data'    => $user
        ], 200);
    }

    // 🗑 Hapus user global
    public function destroy($id)
    {
        $user = User::where('is_global', true)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Global user berhasil dihapus'
        ], 200);
    }
}
