<?php

use Illuminate\Support\Facades\Route;

Route::middleware('tenant')->group(function () {
    Route::get('/', function () {
        // Jika ada tenant aktif
        $tenant = app()->has('tenant') ? app('tenant') : null;

        if ($tenant) {
            return response()->json([
                'message' => "Tenant aktif: {$tenant->name} (subdomain: {$tenant->subdomain})"
            ]);
        }

        // Halaman utama pusat
        return response()->json([
            'message' => "Ini halaman utama pusat (semua nagari)"
        ]);
    });
});

require __DIR__.'/auth.php';
