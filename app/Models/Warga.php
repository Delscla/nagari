<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class Warga extends Model
{
    protected $fillable = [
        'tenant_id', 'nik', 'no_kk', 'nama', 'tempat_lahir',
        'tanggal_lahir', 'jenis_kelamin', 'status_perkawinan',
        'pendidikan', 'pekerjaan', 'agama', 'alamat',
        'rt', 'rw', 'jorong', 'status_domisili',
        'no_hp', 'email'
    ];

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
            // jangan set no_kk_hash di sini, sudah di setter
        });
    }

    public function setNikAttribute($value)
    {
        if ($value) {
            $this->attributes['nik'] = Crypt::encryptString($value);
            $this->attributes['nik_hash'] = hash('sha256', $value);
        }
    }

    public function getNikAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setNoKkAttribute($value)
    {
        if ($value) {
            $this->attributes['no_kk'] = Crypt::encryptString($value);
            $this->attributes['no_kk_hash'] = hash('sha256', $value);
        } else {
            $this->attributes['no_kk'] = null;
            $this->attributes['no_kk_hash'] = null;
        }
    }

    public function getNoKkAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function keluargas()
{
    return $this->belongsToMany(Keluarga::class, 'keluarga_warga')
                ->withPivot('hubungan')
                ->withTimestamps();
}

}
