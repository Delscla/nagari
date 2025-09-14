<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GlobalUserController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WargaController;
use App\Http\Controllers\Api\KeluargaController;
use App\Http\Controllers\Api\WargaIndicatorController;
use App\Http\Controllers\Api\KbProgramController;
// --- PERBAIKAN: Menambahkan use statement yang hilang ---
use App\Http\Controllers\Api\PelayananController;
use App\Http\Controllers\Api\PelayananJenisController;
use App\Http\Controllers\Api\ArsipSuratController;
use App\Http\Controllers\Api\SuratTemplateController;


// =================== RUTE PUBLIK (GLOBAL) ===================
Route::prefix('public')->group(function () {
    Route::get('/info', function() {
        return response()->json([
            'appName' => 'Aplikasi Nagari Terpadu',
            'version' => '1.0.0'
        ]);
    });
});


// =================== RUTE YANG MEMERLUKAN RESOLUSI TENANT ===================
Route::middleware('tenant')->group(function() {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function() {

        // --- MANAJEMEN DASAR ---
        Route::apiResource('users', UserController::class);
        Route::apiResource('tenants', TenantController::class);

        // --- KEPENDUDUKAN ---
        Route::apiResource('wargas', WargaController::class);
        Route::post('/wargas/search-nik', [WargaController::class, 'findByNik']);
        Route::post('/wargas/search-kk',  [WargaController::class, 'findByNoKk']);

        Route::apiResource('keluargas', KeluargaController::class);
        Route::post('/keluargas/{id}/add-anggota',    [KeluargaController::class, 'addAnggota']);
        Route::post('/keluargas/{id}/remove-anggota', [KeluargaController::class, 'removeAnggota']);

        // --- SOSIAL & KESEHATAN ---
        Route::apiResource('warga-indicators', WargaIndicatorController::class);
        Route::apiResource('kb-programs', KbProgramController::class);

        // --- PELAYANAN SURAT (UNTUK WARGA) ---
        Route::apiResource('pelayanan', PelayananController::class);
        Route::post('/pelayanan/{id}/generate', [PelayananController::class, 'generateSurat']);

        // --- ARSIP SURAT (INTERNAL NAGARI) ---
        Route::apiResource('arsip-surat', ArsipSuratController::class);

        // --- MANAJEMEN MASTER DATA (ADMIN NAGARI) ---
        Route::apiResource('pelayanan-jenis', PelayananJenisController::class);
        Route::apiResource('surat-templates', SuratTemplateController::class);
    });
});

// =================== RUTE GLOBAL YANG MEMERLUKAN AUTH ===================
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('global-users', GlobalUserController::class);
});
