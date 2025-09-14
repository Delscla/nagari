<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KeluargaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'no_kk'               => $this->no_kk,
            'alamat'              => $this->alamat,
            'rt'                  => $this->rt,
            'rw'                  => $this->rw,
            'jorong'              => $this->jorong,

            // Kepala keluarga (pakai resource Warga juga)
            'kepala_keluarga'     => new WargaResource($this->whenLoaded('kepalaKeluarga')),

            // Anggota keluarga (pakai collection)
            'anggotas'            => WargaResource::collection($this->whenLoaded('anggotas')),

            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
