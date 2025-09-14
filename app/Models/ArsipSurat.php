<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ArsipSurat extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'nomor_surat',
        'judul',
        'kategori',
        'isi',
        'file_pdf',
        'ditandatangani_oleh',
        'tanggal',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['file_pdf_url'];

    /**
     * Get the URL for the PDF file.
     *
     * @return string|null
     */
    public function getFilePdfUrlAttribute()
    {
        if ($this->file_pdf) {
            return Storage::url($this->file_pdf);
        }
        return null;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Global scope untuk tenancy
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('tenant') && !auth()->user()?->is_global) {
                $builder->where('tenant_id', app('tenant')->id);
            }
        });

        // Menambahkan tenant_id secara otomatis saat membuat data baru
        static::creating(function ($model) {
            if (app()->has('tenant')) {
                $model->tenant_id = app('tenant')->id;
            }
        });
    }

    /**
     * Relasi ke tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

