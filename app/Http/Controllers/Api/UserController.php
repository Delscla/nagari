<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // 📋 List user tenant sesuai tenant aktif
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $users = User::visibleForTenant($tenant, false)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar user tenant aktif',
            'data'    => $users,
            'meta'    => [
                'total' => $users->count(),
                'tenant_id' => $tenant ? $tenant->id : null,
            ]
        ], 200);
    }

    // ➕ Tambah user baru di tenant
    public function store(Request $request)
    {
        $tenant = app('tenant');

        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email',
                'password' => 'required|min:6',
                'role_id'  => 'required|exists:roles,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        }

        // cek apakah email sudah ada
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);
        }

        // attach user ke tenant
        if ($tenant) {
            $user->tenants()->syncWithoutDetaching([
                $tenant->id => ['role_id' => $validated['role_id']]
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil ditambahkan ke tenant',
            'data'    => $user->load('roles'),
        ], 201);
    }

    // 👀 Detail user dalam tenant
    public function show(Request $request, $id)
    {
        $tenant = app('tenant');
        $authUser = $request->user();

        $user = $authUser->is_global
            ? User::find($id)
            : User::visibleForTenant($tenant)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan di tenant ini'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail user tenant',
            'data'    => $user,
        ], 200);
    }

    // ✏️ Update user tenant
    public function update(Request $request, $id)
    {
        $tenant = app('tenant');
        $authUser = $request->user();

        $user = $authUser->is_global
            ? User::find($id)
            : User::visibleForTenant($tenant)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan di tenant ini'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name'     => 'sometimes|required|string|max:255',
                'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:6',
                'role_id'  => 'sometimes|required|exists:roles,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        }

        // update field dasar
        if (isset($validated['name'])) $user->name = $validated['name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        // update role di tenant
        if (isset($validated['role_id']) && $tenant) {
            $user->tenants()->updateExistingPivot($tenant->id, [
                'role_id' => $validated['role_id']
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil diperbarui',
            'data'    => $user->load('roles'),
        ], 200);
    }

    // ❌ Hapus user dari tenant
    public function destroy(Request $request, $id)
    {
        $tenant = app('tenant');
        $authUser = $request->user();

        $user = $authUser->is_global
            ? User::find($id)
            : User::visibleForTenant($tenant)->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak ditemukan di tenant ini'
            ], 404);
        }

        if (!$authUser->is_global && $tenant) {
            $user->tenants()->detach($tenant->id);

            return response()->json([
                'status'  => 'success',
                'message' => 'User berhasil dikeluarkan dari tenant',
                'data'    => ['user_id' => $user->id, 'tenant_id' => $tenant->id],
            ], 200);
        } else {
            $user->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'User berhasil dihapus permanen',
                'data'    => ['user_id' => $user->id],
            ], 200);
        }
    }
}
