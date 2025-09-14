<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelayananAttachment;
use App\Models\PelayananRequest;
use App\Models\PelayananJenis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PelayananController extends Controller
{
    /**
     * Mengambil semua jenis pelayanan yang tersedia.
     * Endpoint: GET /pelayanan/jenis
     */
    public function getJenisPelayanan()
    {
        $jenisPelayanan = PelayananJenis::all()->groupBy('kategori');
        return response()->json($jenisPelayanan);
    }

    /**
     * Display a listing of the resource.
     * Endpoint: GET /pelayanan
     */
    public function index()
    {
        $requests = PelayananRequest::with(['jenis', 'warga', 'attachments'])->latest()->paginate(10);
        return response()->json($requests);
    }

    /**
     * Store a newly created resource in storage.
     * Endpoint: POST /pelayanan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pelayanan_jenis_id' => 'required|exists:pelayanan_jenis,id',
            'warga_id' => 'required|exists:wargas,id',
            'keterangan_pemohon' => 'nullable|string', // PERBAIKAN: Disesuaikan dengan migrasi
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pelayananRequest = PelayananRequest::create([
            'tenant_id' => app('tenant')->id,
            'user_id' => auth()->id(),
            'pelayanan_jenis_id' => $request->pelayanan_jenis_id,
            'warga_id' => $request->warga_id,
            'status' => 'Diajukan',
            'keterangan_pemohon' => $request->keterangan_pemohon, // PERBAIKAN: Disesuaikan dengan migrasi
            'nomor_surat' => $this->generateNomorSurat(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('pelayanan_attachments/' . app('tenant')->id, 'public');
                $pelayananRequest->attachments()->create([
                    'tenant_id' => app('tenant')->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                ]);
            }
        }

        return response()->json($pelayananRequest->load(['jenis', 'warga', 'attachments']), 201);
    }

    /**
     * Display the specified resource.
     * Endpoint: GET /pelayanan/{id}
     */
    public function show($id)
    {
        $pelayananRequest = PelayananRequest::with(['jenis', 'warga', 'attachments'])->findOrFail($id);
        return response()->json($pelayananRequest);
    }

    /**
     * Update the specified resource in storage.
     * Endpoint: PUT /pelayanan/{id}
     */
    public function update(Request $request, $id)
    {
        $pelayananRequest = PelayananRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['Diajukan', 'Diproses', 'Selesai', 'Ditolak'])],
            'keterangan_staff' => 'nullable|string', // PERBAIKAN: Disesuaikan dengan migrasi
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pelayananRequest->update($request->only('status', 'keterangan_staff'));

        return response()->json($pelayananRequest->load(['jenis', 'warga', 'attachments']));
    }

    /**
     * Remove the specified resource from storage.
     * Endpoint: DELETE /pelayanan/{id}
     */
    public function destroy($id)
    {
        $pelayananRequest = PelayananRequest::findOrFail($id);

        foreach ($pelayananRequest->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $pelayananRequest->delete();

        return response()->json(['message' => 'Permintaan pelayanan berhasil dihapus.'], 200);
    }

    /**
     * Helper untuk generate nomor surat unik (contoh sederhana)
     */
    private function generateNomorSurat()
    {
        $kodeSurat = '470';
        $nomorUrut = PelayananRequest::count() + 1;
        $bulanRomawi = $this->getRomanMonth(date('n'));
        $tahun = date('Y');

        return "{$kodeSurat}/{$nomorUrut}/{$bulanRomawi}/{$tahun}";
    }

    private function getRomanMonth($month)
    {
        $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        return $romanMap[$month] ?? '';
    }
}

