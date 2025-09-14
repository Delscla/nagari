<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil host dari request.
        $host = $request->getHost();

        // Ambil domain utama dari konfigurasi aplikasi (file .env -> APP_URL).
        // parse_url akan mengambil 'nagari.test' dari 'http://nagari.test'
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Jika host sama persis dengan domain utama, berarti ini bukan akses subdomain.
        // Hapus instance tenant jika ada dan lanjutkan.
        if ($host === $baseDomain) {
            if (app()->has('tenant')) {
                app()->forgetInstance('tenant');
            }
            return $next($request);
        }

        // Ekstrak bagian subdomain.
        // Contoh: 'cilandak.nagari.test' akan menghasilkan 'cilandak'.
        $subdomain = str_replace('.' . $baseDomain, '', $host);

        // Jika setelah di-replace hasilnya masih sama dengan host awal,
        // atau subdomain adalah 'www', maka ini bukan format yang kita inginkan. Lanjutkan saja.
        if ($subdomain === $host || $subdomain === 'www' || $subdomain === '') {
             if (app()->has('tenant')) {
                app()->forgetInstance('tenant');
            }
            return $next($request);
        }

        // Cari tenant berdasarkan subdomain yang sudah bersih.
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // Jika tenant tidak ditemukan, hentikan request. Ini sangat penting untuk keamanan.
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        // Daftarkan tenant yang ditemukan ke dalam service container.
        app()->instance('tenant', $tenant);

        Log::debug('TenantMiddleware Resolved', [
            'host' => $host,
            'base_domain' => $baseDomain,
            'resolved_subdomain' => $subdomain,
            'tenant_id' => $tenant->id
        ]);

        return $next($request);
    }
}

