<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Keluarga;
use App\Models\Warga;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeluargaController extends Controller
{

     // GET /keluargas
    public function index()
    {
        // PERBAIKAN: Gunakan with() untuk memuat relasi kepalaKeluarga
        $keluargas = Keluarga::with('kepalaKeluarga')->get();
        return response()->json($keluargas);
    }
    // GET /keluargas/lookup?q=...&rt=01&rw=02&jorong=Melati&page=1
    public function lookup(Request $request)
    {
        $q = $request->query('q');
        $rt = $request->query('rt');
        $rw = $request->query('rw');
        $jorong = $request->query('jorong');

        $query = Keluarga::with('kepalaKeluarga')
            ->when($q, fn($qBuilder) => $qBuilder->whereHas('kepalaKeluarga', fn($w) =>
                $w->where('nama', 'like', "%$q%")
            ))
            ->when($rt, fn($qBuilder) => $qBuilder->whereHas('kepalaKeluarga', fn($w) =>
                $w->where('rt', $rt)
            ))
            ->when($rw, fn($qBuilder) => $qBuilder->whereHas('kepalaKeluarga', fn($w) =>
                $w->where('rw', $rw)
            ))
            ->when($jorong, fn($qBuilder) => $qBuilder->whereHas('kepalaKeluarga', fn($w) =>
                $w->where('jorong', $jorong)
            ));

        return $query->paginate(10);
    }

    // GET /keluargas/filters?fields=rt,rw,jorong
    public function filters(Request $request)
    {
        $fields = explode(',', $request->query('fields', 'rt,rw,jorong'));
        $results = [];

        foreach ($fields as $field) {
            $results[$field] = Warga::select($field)
                ->distinct()
                ->whereNotNull($field)
                ->pluck($field);
        }

        return response()->json($results);
    }

    // GET /keluargas/export
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Keluarga::with('kepalaKeluarga');

        if ($request->has('jorong')) {
            $query->whereHas('kepalaKeluarga', fn($w) => $w->where('jorong', $request->jorong));
        }

        $families = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=keluarga.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No KK', 'Kepala Keluarga', 'Alamat', 'RT', 'RW', 'Jorong'];

        $callback = function() use ($families, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($families as $keluarga) {
                $kk = $keluarga->kepalaKeluarga;
                fputcsv($file, [
                    $keluarga->no_kk,
                    $kk?->nama,
                    $keluarga->alamat,
                    $keluarga->rt,
                    $keluarga->rw,
                    $keluarga->jorong,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // POST /keluargas/import
    public function import(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            '*.no_kk' => 'required|string|unique:keluargas,no_kk',
            '*.kepala_keluarga_id' => 'nullable|exists:wargas,id',
            '*.alamat' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $created = [];
        foreach ($data as $item) {
            $keluarga = Keluarga::create([
                'no_kk' => $item['no_kk'],
                'kepala_keluarga_id' => $item['kepala_keluarga_id'] ?? null,
                'alamat' => $item['alamat'] ?? null,
                'rt' => $item['rt'] ?? null,
                'rw' => $item['rw'] ?? null,
                'jorong' => $item['jorong'] ?? null,
                'tenant_id' => app('tenant')->id,
            ]);
            $created[] = $keluarga;
        }

        return response()->json([
            'message' => 'Import berhasil',
            'count' => count($created),
            'data' => $created
        ]);
    }

    // POST /keluargas
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'no_kk' => 'required|string|unique:keluargas,no_kk',
        'kepala_keluarga_id' => 'required|exists:wargas,id',
        'alamat' => 'nullable|string',
        'rt' => 'nullable|string|max:10',
        'rw' => 'nullable|string|max:10',
        'jorong' => 'nullable|string|max:50',
    ]);

    // Validasi custom: pastikan no_kk sama dengan warga
    $validator->after(function ($validator) use ($request) {
        if ($request->kepala_keluarga_id && $request->no_kk) {
            $warga = Warga::find($request->kepala_keluarga_id);
            if (!$warga) {
                $validator->errors()->add('kepala_keluarga_id', 'Warga tidak ditemukan.');
            } elseif ($warga->no_kk !== $request->no_kk) {
                $validator->errors()->add('no_kk', 'No KK tidak cocok dengan Warga yang dipilih.');
            }
        }
    });

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $keluarga = Keluarga::create([
        'tenant_id' => app('tenant')->id,
        'no_kk' => $request->no_kk,
        'kepala_keluarga_id' => $request->kepala_keluarga_id,
        'alamat' => $request->alamat,
        'rt' => $request->rt,
        'rw' => $request->rw,
        'jorong' => $request->jorong,
    ]);

    return response()->json([
        'message' => 'Keluarga berhasil dibuat',
        'data' => $keluarga->load('kepalaKeluarga')
    ], 201);
}


    // POST /keluargas/{id}/add-anggota
    public function addAnggota(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'warga_id' => 'required|exists:wargas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $keluarga = Keluarga::findOrFail($id);
        $keluarga->anggotas()->syncWithoutDetaching([$request->warga_id]);

        return response()->json([
            'message' => 'Anggota berhasil ditambahkan',
            'data' => $keluarga->load(['kepalaKeluarga','anggotas'])
        ]);
    }

    // POST /keluargas/{id}/remove-anggota
    public function removeAnggota(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'warga_id' => 'required|exists:wargas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $keluarga = Keluarga::findOrFail($id);
        $keluarga->anggotas()->detach($request->warga_id);

        return response()->json([
            'message' => 'Anggota berhasil dihapus',
            'data' => $keluarga->load(['kepalaKeluarga','anggotas'])
        ]);
    }
}
