<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Keluarga extends Model
{
    protected $fillable = [
        'tenant_id',
        'no_kk',
        'kepala_keluarga_id',
        'alamat',
        'rt',
        'rw',
        'jorong',
    ];

    // --- Global Scope Tenant ---
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app()->has('tenant') ? app('tenant') : null;

            if ($tenant && (!auth()->check() || !auth()->user()->is_global)) {
                $builder->where('tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model) {
            $tenant = app()->has('tenant') ? app('tenant') : null;
            if ($tenant && empty($model->tenant_id)) {
                $model->tenant_id = $tenant->id;
            }
        });
    }

    // --- Relasi ke Warga (kepala keluarga) ---
    public function kepalaKeluarga()
    {
        return $this->belongsTo(Warga::class, 'kepala_keluarga_id');
    }

    // --- Relasi banyak anggota keluarga ---
    public function anggotas()
    {
        return $this->belongsToMany(Warga::class, 'keluarga_warga', 'keluarga_id', 'warga_id')
                    ->withTimestamps();
    }
}
