<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use Illuminate\Http\Request;
use App\Http\Resources\WargaResource;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class WargaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // <-- Diubah untuk menerima Request
    {
        $query = Warga::query();

        // Logika tenant yang sudah ada (DIPERTAHANKAN)
        if (!auth()->user() || !auth()->user()->is_global) {
            $tenant = app()->has('tenant') ? app('tenant') : null;
            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }
        }

        // --- LOGIKA BARU UNTUK SEARCH DAN FILTER DIMASUKKAN DI SINI ---

        // Logika untuk Pencarian (Search)
        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nik', 'like', "%{$searchTerm}%")
                  ->orWhere('nama', 'like', "%{$searchTerm}%")
                  ->orWhere('no_kk', 'like', "%{$searchTerm}%");
            });
        }

        // Logika untuk Filter Dinamis berdasarkan kolom spesifik
        $filterableColumns = [
            'no_kk',
            'jenis_kelamin',
            'status_perkawinan',
            'pendidikan',
            'pekerjaan',
            'agama',
            'jorong',
            'status_domisili'
        ];

        foreach ($filterableColumns as $column) {
            if ($request->has($column) && $request->input($column) != '') {
                $query->where($column, $request->input($column));
            }
        }

        // --- AKHIR LOGIKA BARU ---

        // Eksekusi query dengan paginasi dan sertakan query string
        $wargas = $query->latest()->paginate(20)->withQueryString(); // <-- Diubah dengan latest() dan withQueryString()

        return WargaResource::collection($wargas);
    }

    // ====================================================================
    // SEMUA METHOD DI BAWAH INI TIDAK DIUBAH SAMA SEKALI
    // ====================================================================

    public function store(Request $request)
    {
       $validated = $request->validate([
            'nik' => 'required|string|max:20',
            'no_kk' => 'nullable|string|max:20',
            'nama' => 'required|string|max:150',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'status_perkawinan' => 'required|in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati',
            'pendidikan' => 'nullable|string|max:50',
            'pekerjaan' => 'nullable|string|max:100',
            'agama' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'jorong' => 'nullable|string|max:50',
            'status_domisili' => 'required|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);
        \Log::debug('Store warga validated data:', $validated);

        if (Warga::where('nik_hash', hash('sha256', $request->nik))->exists()) {
            return response()->json(['error' => 'NIK sudah terdaftar'], 422);
        }

        $warga = Warga::create($validated);

        return new WargaResource($warga);
    }

    public function show($id)
    {
        $warga = Warga::findOrFail($id);
        return new WargaResource($warga);
    }

    public function update(Request $request, $id)
    {
        $warga = Warga::findOrFail($id);

        $validated = $request->validate([
            'nik' => 'nullable|string|max:20',
            'no_kk' => 'nullable|string|max:20',
            'nama' => 'nullable|string|max:150',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
            'status_perkawinan' => 'nullable|in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati',
        ]);

        if ($request->filled('nik')) {
            $nikHash = hash('sha256', $request->nik);
            if (Warga::where('nik_hash', $nikHash)->where('id', '!=', $warga->id)->exists()) {
                return response()->json(['error' => 'NIK sudah terdaftar'], 422);
            }
        }

        $warga->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil diupdate',
            'data' => new WargaResource($warga)
        ], 200);
    }

    public function destroy($id)
    {
        $warga = Warga::findOrFail($id);
        $warga->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil dihapus'
        ], 200);
    }

    public function findByNik(Request $request)
    {
        $request->validate(['nik' => 'required|string']);
        $nik_hash = hash('sha256', $request->nik);
        $warga = Warga::where('nik_hash', $nik_hash)->first();

        if (!$warga) {
            return response()->json(['message' => 'Warga not found'], 404);
        }
        return response()->json($warga);
    }

    public function findByNoKk(Request $request)
    {
        $request->validate(['no_kk' => 'required|string']);
        $no_kk_hash = hash('sha256', $request->no_kk);
        $warga = Warga::where('no_kk_hash', $no_kk_hash)->first();

        if (!$warga) {
            return response()->json(['message' => 'Warga not found'], 404);
        }
        return response()->json($warga);
    }

    public function filters(Request $request)
    {
        $fields = explode(',', $request->fields ?? '');
        $result = [];

        foreach($fields as $field) {
            $result[$field] = Warga::select($field)->distinct()->pluck($field);
        }

        return response()->json($result);
    }

    public function stats()
    {
        $stats = [
            'gender' => Warga::select('jenis_kelamin')->distinct()->count(),
            'domisili' => Warga::select('status_domisili')->distinct()->count(),
        ];

        return response()->json($stats);
    }

    public function checkUnique(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'nullable|string',
            'no_kk' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $response = [];
        if ($request->nik) $response['nik_exists'] = Warga::where('nik',$request->nik)->exists();
        if ($request->no_kk) $response['no_kk_exists'] = Warga::where('no_kk',$request->no_kk)->exists();

        return response()->json($response);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->all();
        $inserted = [];

        foreach($data as $item) {
            $validator = Validator::make($item, [
                'nik' => 'required|string|unique:wargas,nik',
                'no_kk' => 'required|string',
                'nama' => 'required|string',
            ]);
            if ($validator->fails()) continue;
            $inserted[] = Warga::create($item);
        }
        return response()->json($inserted);
    }

    public function exportCsv(Request $request)
    {
        $filename = "wargas_export.csv";
        $wargas = Warga::all();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}"
        ];

        $callback = function() use ($wargas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['id','nik','no_kk','nama','jenis_kelamin','rt','rw','jorong']);
            foreach($wargas as $w) {
                fputcsv($file, [$w->id,$w->nik,$w->no_kk,$w->nama,$w->jenis_kelamin,$w->rt,$w->rw,$w->jorong]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
