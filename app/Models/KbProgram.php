<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'warga_id',
        'jenis_kb',
        'tanggal_mulai',
        'tanggal_berakhir',
        'status'
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
