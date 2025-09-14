<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // Import Builder

class PelayananJenis extends Model
{
    use HasFactory;

    protected $table = 'pelayanan_jenis';

    /**
     * PERBAIKAN: Menambahkan 'tenant_id', 'kategori', dan 'surat_template_id'
     * ke dalam $fillable agar bisa diisi secara massal.
     */
    protected $fillable = [
        'tenant_id', // <-- DITAMBAHKAN
        'kategori',
        'nama',
        'syarat',
        'surat_template_id', // Kolom relasi
    ];

    protected $casts = [
        'syarat' => 'array',
    ];

    /**
     * BEST PRACTICE: Menambahkan 'booted' method untuk menangani tenant_id secara otomatis.
     * Ini memastikan bahwa tenant_id akan selalu diisi setiap kali data baru dibuat.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = auth()->user();
            if ($user && !$user->is_global) {
                $tenant = app()->has('tenant') ? app('tenant') : null;
                if ($tenant) {
                    $builder->where('pelayanan_jenis.tenant_id', $tenant->id);
                }
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && app()->has('tenant')) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }


    /**
     * Relasi ke permintaan layanan.
     */
    public function requests()
    {
        return $this->hasMany(PelayananRequest::class, 'pelayanan_jenis_id');
    }

    /**
     * PERBAIKAN: Mengganti nama relasi menjadi 'template' agar sesuai
     * dengan pemanggilan di controller (cth: with('template')).
     */
    public function template()
    {
        return $this->belongsTo(SuratTemplates::class, 'surat_template_id');
    }
}
