<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    // 📌 GET /tenants → list tenant sesuai role
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->is_global) {
            // Global staff boleh lihat semua tenant
            $tenants = Tenant::all();
        } else {
            // Staff nagari hanya boleh lihat tenant miliknya
            $tenants = $user->tenants()->get();
        }

        return response()->json([
            'status' => true,
            'data'   => $tenants
        ]);
    }

    // 📌 GET /tenants/count → hitung tenant
    public function count(Request $request)
    {
        $user = $request->user();

        $count = $user->is_global
            ? Tenant::count()
            : $user->tenants()->count();

        return response()->json([
            'status' => true,
            'count'  => $count
        ]);
    }

    // 📌 POST /tenants → tambah tenant
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:domain,subdomain',
            'domain'    => 'required_if:type,domain|nullable|string|unique:tenants,domain',
            'subdomain' => 'required_if:type,subdomain|nullable|string|unique:tenants,subdomain',
        ]);

        $tenant = Tenant::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Tenant berhasil ditambahkan',
            'data'    => $tenant
        ], 201);
    }

    // 📌 GET /tenants/{id} → detail tenant
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if ($user->is_global) {
            $tenant = Tenant::findOrFail($id);
        } else {
            $tenant = $user->tenants()->findOrFail($id);
        }

        return response()->json([
            'status' => true,
            'data'   => $tenant
        ]);
    }

    // 📌 PUT /tenants/{id} → update tenant
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($user->is_global) {
            $tenant = Tenant::findOrFail($id);
        } else {
            $tenant = $user->tenants()->findOrFail($id);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'type'      => 'sometimes|required|in:domain,subdomain',
            'domain'    => 'required_if:type,domain|nullable|string|unique:tenants,domain,' . $tenant->id,
            'subdomain' => 'required_if:type,subdomain|nullable|string|unique:tenants,subdomain,' . $tenant->id,
        ]);

        $tenant->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Tenant berhasil diperbarui',
            'data'    => $tenant
        ]);
    }

    // 📌 DELETE /tenants/{id} → hapus tenant
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->is_global) {
            $tenant = Tenant::findOrFail($id);
        } else {
            $tenant = $user->tenants()->findOrFail($id);
        }

        $tenant->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Tenant berhasil dihapus'
        ]);
    }
}
