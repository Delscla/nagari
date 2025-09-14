<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArsipSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArsipSuratController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ArsipSurat::query();

        // Filter berdasarkan pencarian (nomor surat atau judul)
        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nomor_surat', 'like', "%{$searchTerm}%")
                  ->orWhere('judul', 'like', "%{$searchTerm}%");
            });
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori') && $request->input('kategori') != '') {
            $query->where('kategori', $request->input('kategori'));
        }

        return $query->latest()->paginate(15);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_surat' => 'required|string|max:50|unique:arsip_surats,nomor_surat',
            'judul' => 'required|string|max:150',
            'kategori' => 'required|string|in:Surat Tugas,Surat Keputusan,Surat Undangan,Surat Edaran,Notulen,Lainnya',
            'tanggal' => 'required|date',
            'file_pdf' => 'required|file|mimes:pdf|max:2048', // Maks 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Simpan file
        $tenant = app('tenant');
        $filePath = $request->file('file_pdf')->store("tenants/{$tenant->id}/arsip_surat");

        $arsip = ArsipSurat::create([
            'nomor_surat' => $request->nomor_surat,
            'judul' => $request->judul,
            'kategori' => $request->kategori,
            'tanggal' => $request->tanggal,
            'file_pdf' => $filePath,
            'ditandatangani_oleh' => $request->ditandatangani_oleh,
            'isi' => $request->isi,
        ]);

        return response()->json([
            'message' => 'Arsip surat berhasil dibuat.',
            'data' => $arsip
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $arsip = ArsipSurat::findOrFail($id);
        return response()->json($arsip);
    }

    /**
     * Update the specified resource in storage.
     * Note: HTML form-data does not support PUT/PATCH, so we use POST for updates.
     */
    public function update(Request $request, string $id)
    {
        $arsip = ArsipSurat::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nomor_surat' => 'required|string|max:50|unique:arsip_surats,nomor_surat,' . $arsip->id,
            'judul' => 'required|string|max:150',
            'kategori' => 'required|string|in:Surat Tugas,Surat Keputusan,Surat Undangan,Surat Edaran,Notulen,Lainnya',
            'tanggal' => 'required|date',
            'file_pdf' => 'nullable|file|mimes:pdf|max:2048', // File tidak wajib saat update
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dataToUpdate = $request->except('file_pdf');

        if ($request->hasFile('file_pdf')) {
            // Hapus file lama jika ada
            if ($arsip->file_pdf && Storage::exists($arsip->file_pdf)) {
                Storage::delete($arsip->file_pdf);
            }

            // Simpan file baru
            $tenant = app('tenant');
            $filePath = $request->file('file_pdf')->store("tenants/{$tenant->id}/arsip_surat");
            $dataToUpdate['file_pdf'] = $filePath;
        }

        $arsip->update($dataToUpdate);

        return response()->json([
            'message' => 'Arsip surat berhasil diperbarui.',
            'data' => $arsip
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $arsip = ArsipSurat::findOrFail($id);

        // Hapus file dari storage sebelum menghapus record database
        if ($arsip->file_pdf && Storage::exists($arsip->file_pdf)) {
            Storage::delete($arsip->file_pdf);
        }

        $arsip->delete();

        return response()->json([
            'message' => 'Arsip surat berhasil dihapus.'
        ], 200);
    }
}

