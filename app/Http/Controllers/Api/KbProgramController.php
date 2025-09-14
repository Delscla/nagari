<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KbProgram;
use Illuminate\Support\Facades\Validator;

class KbProgramController extends Controller
{
    // GET /api/kb-programs
    public function index(Request $request)
    {
        $query = KbProgram::with(['warga', 'tenant']);

        // Filter tenant otomatis jika user bukan global
        if (!auth()->user()->is_global) {
            $query->where('tenant_id', app('tenant')->id);
        } else if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter warga
        if ($request->has('warga_id')) {
            $query->where('warga_id', $request->warga_id);
        }

        // Filter jenis KB
        if ($request->has('jenis_kb')) {
            $query->where('jenis_kb', $request->jenis_kb);
        }

        // Filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    // GET /api/kb-programs/{id}
    public function show($id)
    {
        $kbProgram = KbProgram::with(['warga', 'tenant'])->findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $kbProgram->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($kbProgram);
    }

    // POST /api/kb-programs
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warga_id' => 'required|exists:wargas,id',
            'jenis_kb' => 'required|in:IUD,Implan,Suntik,Pil,Kondom,MOW,MOP,Lainnya',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'nullable|in:Aktif,Nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['tenant_id'] = app('tenant')->id;
        $data['status'] = $data['status'] ?? 'Aktif';

        $kbProgram = KbProgram::create($data);

        return response()->json([
            'message' => 'KB Program berhasil dibuat',
            'data' => $kbProgram->load(['warga', 'tenant'])
        ], 201);
    }

    // PUT/PATCH /api/kb-programs/{id}
    public function update(Request $request, $id)
    {
        $kbProgram = KbProgram::findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $kbProgram->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'jenis_kb' => 'nullable|in:IUD,Implan,Suntik,Pil,Kondom,MOW,MOP,Lainnya',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'nullable|in:Aktif,Nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kbProgram->update($validator->validated());

        return response()->json([
            'message' => 'KB Program berhasil diperbarui',
            'data' => $kbProgram->load(['warga', 'tenant'])
        ]);
    }

    // DELETE /api/kb-programs/{id}
    public function destroy($id)
    {
        $kbProgram = KbProgram::findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $kbProgram->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $kbProgram->delete();

        return response()->json([
            'message' => 'KB Program berhasil dihapus'
        ]);
    }
}
