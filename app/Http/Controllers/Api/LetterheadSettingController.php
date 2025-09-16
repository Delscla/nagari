<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LetterheadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LetterheadSettingController extends Controller
{
    public function show(Request $request)
    {
        $tenantId = tenant('id');
        $setting = LetterheadSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'line1' => 'PEMERINTAH KABUPATEN/KOTA',
                'line2' => 'KECAMATAN • NAGARI/DESA',
                'line3' => 'Alamat lengkap nagari/desa',
                'line4' => 'Kontak (Telepon & Email)',
            ]
        );

        if ($setting->logo_path) {
            $setting->logo_url = Storage::url($setting->logo_path);
        }

        return response()->json($setting);
    }

    public function store(Request $request)
    {
        $tenantId = tenant('id');

        $validated = $request->validate([
            'line1' => 'required|string|max:255',
            'line2' => 'required|string|max:255',
            'line3' => 'required|string|max:255',
            'line4' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $setting = LetterheadSetting::firstOrNew(['tenant_id' => $tenantId]);

        $dataToUpdate = $request->except(['logo', '_method']);

        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            // Simpan logo baru di 'storage/app/public/logos'
            $path = $request->file('logo')->store('logos', 'public');
            $dataToUpdate['logo_path'] = $path;
        }

        $setting->fill($dataToUpdate)->save();

        // Ambil data terbaru untuk dikirim balik
        $updatedSetting = $setting->fresh();
        if ($updatedSetting->logo_path) {
            $updatedSetting->logo_url = Storage::url($updatedSetting->logo_path);
        }

        return response()->json(['message' => 'Pengaturan kop surat berhasil disimpan.', 'data' => $updatedSetting]);
    }
}
