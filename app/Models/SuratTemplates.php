<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SuratTemplates extends Model
{
    use HasFactory;

    protected $table = 'surat_templates';

    protected $fillable = [
        'tenant_id',
        'nama_surat',
        'deskripsi',
        'konten',
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
                    $builder->where('surat_templates.tenant_id', $tenant->id);
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
     * PERBAIKAN: Menambahkan relasi balik ke Jenis Pelayanan.
     * Satu template dimiliki oleh satu jenis layanan.
     */
    public function pelayananJenis()
    {
        return $this->hasOne(PelayananJenis::class, 'surat_template_id');
    }
}
