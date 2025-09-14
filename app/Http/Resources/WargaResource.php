<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WargaResource extends JsonResource
{
    public function toArray($request)
    {
        $nik = $this->nik;
        $no_kk = $this->no_kk;

        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
    $nik = substr($nik, 0, 6) . str_repeat('*', strlen($nik)-10) . substr($nik, -4);
    $no_kk = substr($no_kk, 0, 6) . str_repeat('*', strlen($no_kk)-10) . substr($no_kk, -4);
}


        return [
            'id' => $this->id,
            'nik'=> $this->nik,
            'no_kk'=> $this->no_kk,
            'nama'=> $this->nama,
            'tempat_lahir'=> $this->tempat_lahir,
            'tanggal_lahir'=>$this->tanggal_lahir,
            'jenis_kelamin'=> $this->jenis_kelamin,
            'status_perkawinan'=> $this->status_perkawinan,
            'pendidikan'=> $this->pendidikan,
            'pekerjaan'=> $this->pekerjaan,
            'agama'=> $this->agama,
            'alamat'=> $this->alamat,
            'rt'=> $this->rt,
            'rw'=> $this->rw,
            'jorong'=> $this->jorong,
            'status_domisili'=> $this->status_domisili,
            'no_hp'=> $this->no_hp,
            'email'=> $this->email
        ];
    }
}
