<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelayananJenis;
use App\Models\SuratTemplates; // <-- DITAMBAHKAN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- DITAMBAHKAN
use Illuminate\Support\Facades\Validator;

class PelayananJenisController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Mengambil data dengan relasi template-nya
        $jenisPelayanan = PelayananJenis::with('template')->get()->groupBy('kategori');
        return response()->json($jenisPelayanan);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori' => 'required|string|max:100',
            'nama' => 'required|string|max:150|unique:pelayanan_jenis,nama',
            'syarat' => 'nullable|array',
            'template' => 'required|array', // Template wajib ada saat membuat
            'template.konten' => 'required|string',
            'template.deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Gunakan Database Transaction untuk memastikan kedua data berhasil dibuat
        try {
            DB::beginTransaction();

            // 1. Buat Template Surat terlebih dahulu
            $template = SuratTemplates::create([
                'tenant_id' => app('tenant')->id,
                'nama_surat' => $request->nama, // Nama template sama dengan nama jenis layanan
                'konten' => $request->input('template.konten'),
                'deskripsi' => $request->input('template.deskripsi'),
            ]);

            // 2. Buat Jenis Pelayanan dan hubungkan dengan template yang baru dibuat
            $jenis = PelayananJenis::create([
                'tenant_id' => app('tenant')->id,
                'kategori' => $request->kategori,
                'nama' => $request->nama,
                'syarat' => $request->syarat,
                'surat_template_id' => $template->id, // Hubungkan ke template
            ]);

            DB::commit(); // Jika semua berhasil, simpan perubahan

            return response()->json($jenis->load('template'), 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Jika ada error, batalkan semua perubahan
            return response()->json([
                'message' => 'Gagal membuat jenis layanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $jenis = PelayananJenis::with('template')->findOrFail($id);
        return response()->json($jenis);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $jenis = PelayananJenis::with('template')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kategori' => 'sometimes|required|string|max:100',
            'nama' => 'sometimes|required|string|max:150|unique:pelayanan_jenis,nama,' . $id,
            'syarat' => 'nullable|array',
            'template' => 'sometimes|array',
            'template.konten' => 'sometimes|required|string',
            'template.deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Update data Jenis Pelayanan
            $jenis->update($request->except('template'));

            // 2. Jika ada data template di request, update juga template yang terhubung
            if ($request->has('template')) {
                if ($jenis->template) {
                    $jenis->template->update([
                        'nama_surat' => $request->nama ?? $jenis->nama, // Update nama template jika nama jenis diubah
                        'konten' => $request->input('template.konten', $jenis->template->konten),
                        'deskripsi' => $request->input('template.deskripsi', $jenis->template->deskripsi),
                    ]);
                }
            }

            DB::commit();

            return response()->json($jenis->fresh('template'));

        } catch (\Exception $e) {
            DB::rollBack();
             return response()->json([
                'message' => 'Gagal memperbarui jenis layanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $jenis = PelayananJenis::with('template')->findOrFail($id);

        try {
            DB::beginTransaction();
            // Hapus juga template yang terhubung
            if ($jenis->template) {
                $jenis->template->delete();
            }
            $jenis->delete();
            DB::commit();

            return response()->json(['message' => 'Jenis layanan dan template terkait berhasil dihapus.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus jenis layanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

