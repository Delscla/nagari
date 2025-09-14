<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PelayananRequest extends Model
{
    use HasFactory;

    /**
     * PERBAIKAN: Nama tabel di sini harus jamak agar sesuai
     * dengan nama tabel yang dibuat oleh file migrasi.
     */
    protected $table = 'pelayanan_requests';

    // Kolom yang diizinkan untuk diisi secara massal, diselaraskan dengan migrasi
    protected $fillable = [
        'tenant_id',
        'pelayanan_jenis_id',
        'warga_id',
        'user_id',
        'nomor_surat',
        'status',
        'keterangan_pemohon',
        'keterangan_staff',
        'file_path',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_selesai' => 'datetime',
    ];

    /**
     * Terapkan scope tenant secara otomatis.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();
            if ($user && !$user->is_global) {
                $tenant = app()->has('tenant') ? app('tenant') : null;
                if ($tenant) {
                    // Menambahkan nama tabel untuk menghindari ambiguitas di query JOIN
                    $builder->where('pelayanan_requests.tenant_id', $tenant->id);
                }
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && app()->has('tenant')) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }

    // Relasi ke Tenant
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Relasi ke Jenis Pelayanan
    public function jenis()
    {
        return $this->belongsTo(PelayananJenis::class, 'pelayanan_jenis_id');
    }

    // Relasi ke Warga (pemohon)
    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    // Relasi ke User (staff yang memproses)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke file-file lampiran.
     */
    public function attachments()
    {
        return $this->hasMany(PelayananAttachment::class);
    }
}

