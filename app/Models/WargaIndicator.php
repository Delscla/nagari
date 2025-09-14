<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WargaIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'warga_id',
        'miskin',
        'disabilitas',
        'lansia',
        'yatim_piatu',
        'status_bantuan',
        'keterangan',
        'penghasilan',          // ditambahkan
        'sumber_penghasilan',   // ditambahkan
        'status_kemiskinan',    // ditambahkan
    ];

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
