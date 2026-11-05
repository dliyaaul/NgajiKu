<?php

namespace App\Http\Resources;

use App\Models\PembayaranRekening;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PembayaranRekening
 */
class RekeningResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_bank' => $this->nama_bank,
            'nomor_rekening' => $this->nomor_rekening,
            'nama_pemilik' => $this->nama_pemilik,
            'keterangan' => $this->keterangan,
            'aktif' => $this->aktif,
            'urutan' => $this->urutan,
        ];
    }
}
