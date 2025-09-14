<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PelayananAttachment extends Model
{
    use HasFactory;

    protected $table = 'pelayanan_attachments';

    /**
     * PERBAIKAN: Menambahkan properti $fillable yang hilang.
     * Ini akan mengizinkan Mass Assignment dari controller.
     */
    protected $fillable = [
        'tenant_id',
        'pelayanan_request_id',
        'file_path',
        'file_name',
        'file_type',
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
                    $builder->where('pelayanan_attachments.tenant_id', $tenant->id);
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
     * Relasi ke parent request.
     */
    public function pelayananRequest()
    {
        return $this->belongsTo(PelayananRequest::class, 'pelayanan_request_id');
    }
}

