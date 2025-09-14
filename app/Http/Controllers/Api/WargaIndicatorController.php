<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WargaIndicator;
use Illuminate\Support\Facades\Validator;

class WargaIndicatorController extends Controller
{
    // GET /api/warga-indicators
    public function index(Request $request)
    {
        $query = WargaIndicator::with(['warga', 'tenant']);

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

        // Filter status miskin
        if ($request->has('miskin')) {
            $query->where('miskin', filter_var($request->miskin, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->paginate(10));
    }

    // GET /api/warga-indicators/{id}
    public function show($id)
    {
        $wargaIndicator = WargaIndicator::with(['warga', 'tenant'])->findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $wargaIndicator->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($wargaIndicator);
    }

    // POST /api/warga-indicators
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warga_id' => 'required|exists:wargas,id',
            'miskin' => 'boolean',
            'disabilitas' => 'boolean',
            'lansia' => 'boolean',
            'yatim_piatu' => 'boolean',
            'status_bantuan' => 'nullable|in:PKH,BLT,BPNT,KIS,Tidak Ada',
            'keterangan' => 'nullable|string',
            'penghasilan' => 'nullable|numeric|min:0',
            'sumber_penghasilan' => 'nullable|string|max:150',
            'status_kemiskinan' => 'nullable|in:Miskin,Non-Miskin,Rentan',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['tenant_id'] = app('tenant')->id;

        $wargaIndicator = WargaIndicator::create($data);

        return response()->json([
            'message' => 'Warga Indicator berhasil dibuat',
            'data' => $wargaIndicator->load(['warga', 'tenant'])
        ], 201);
    }

    // PUT/PATCH /api/warga-indicators/{id}
    public function update(Request $request, $id)
    {
        $wargaIndicator = WargaIndicator::findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $wargaIndicator->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'miskin' => 'boolean',
            'disabilitas' => 'boolean',
            'lansia' => 'boolean',
            'yatim_piatu' => 'boolean',
            'status_bantuan' => 'nullable|in:PKH,BLT,BPNT,KIS,Tidak Ada',
            'keterangan' => 'nullable|string',
            'penghasilan' => 'nullable|numeric|min:0',
            'sumber_penghasilan' => 'nullable|string|max:150',
            'status_kemiskinan' => 'nullable|in:Miskin,Non-Miskin,Rentan',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wargaIndicator->update($validator->validated());

        return response()->json([
            'message' => 'Warga Indicator berhasil diperbarui',
            'data' => $wargaIndicator->load(['warga', 'tenant'])
        ]);
    }

    // DELETE /api/warga-indicators/{id}
    public function destroy($id)
    {
        $wargaIndicator = WargaIndicator::findOrFail($id);

        // Cek tenant
        if (!auth()->user()->is_global && $wargaIndicator->tenant_id !== app('tenant')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wargaIndicator->delete();

        return response()->json([
            'message' => 'Warga Indicator berhasil dihapus'
        ]);
    }
}
