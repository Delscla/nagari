<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeluargaWarga extends Model
{
    use HasFactory;

    protected $table = 'keluarga_warga';

    protected $fillable = [
        'keluarga_id',
        'warga_id',
        'hubungan',
    ];

    // 🔗 Relasi
    public function keluarga()
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }
}
