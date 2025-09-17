<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Keluarga;
use App\Models\Warga;
use App\Http\Resources\KeluargaResource;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeluargaController extends Controller
{

    public function index()
    {
        // PERBAIKAN: Tambahkan withCount untuk menghitung jumlah anggota
        $keluargas = Keluarga::with('kepalaKeluarga')->withCount('anggotas')->get();
        return KeluargaResource::collection($keluargas);
    }

    // --- FUNGSI BARU: Untuk halaman detail ---
    public function show($id)
    {
        // Memuat semua relasi yang dibutuhkan: kepala keluarga dan semua anggotanya
        $keluarga = Keluarga::with(['kepalaKeluarga', 'anggotas'])->find($id);

        if (!$keluarga) {
            return response()->json(['message' => 'Data keluarga tidak ditemukan.'], 404);
        }

        return new KeluargaResource($keluarga);
    }

    // --- FUNGSI STORE YANG DIMODIFIKASI ---
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

        // --- LOGIKA BARU: Tambahkan semua warga dengan No. KK yang sama sebagai anggota ---
        $wargaDenganKkSama = Warga::where('no_kk', $request->no_kk)->pluck('id');
        if ($wargaDenganKkSama->isNotEmpty()) {
            $keluarga->anggotas()->sync($wargaDenganKkSama);
        }
        // --- SELESAI ---

        // Menggunakan Resource untuk mengembalikan data yang sudah diformat
        return new KeluargaResource($keluarga->load(['kepalaKeluarga', 'anggotas']));
    }

    // ... Sisa fungsi (lookup, filters, exportCsv, dll.) tetap sama seperti file Anda sebelumnya ...
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
