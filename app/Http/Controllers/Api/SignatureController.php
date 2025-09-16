<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    public function index()
    {
        $signatures = Signature::where('tenant_id', tenant('id'))->get()->map(function ($signature) {
            $signature->image_url = Storage::url($signature->signature_image_path);
            return $signature;
        });
        return response()->json($signatures);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'signature_image' => 'required|image|mimes:png|max:1024',
        ]);

        $path = $request->file('signature_image')->store('signatures', 'public');

        $signature = Signature::create([
            'tenant_id' => tenant('id'),
            'name' => $validated['name'],
            'position' => $validated['position'],
            'signature_image_path' => $path,
        ]);

        $signature->image_url = Storage::url($signature->signature_image_path);

        return response()->json(['message' => 'Tanda tangan berhasil ditambahkan.', 'data' => $signature], 201);
    }

    public function destroy(Signature $signature)
    {
        if ($signature->tenant_id !== tenant('id')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($signature->signature_image_path);
        $signature->delete();

        return response()->json(['message' => 'Tanda tangan berhasil dihapus.']);
    }
}
