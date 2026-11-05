<?php

namespace App\Http\Resources;

use App\Models\PembayaranTarif;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PembayaranTarif
 */
class TarifResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_tarif' => $this->nama_tarif,
            'kelas_type_id' => $this->kelas_type_id,
            'jenis_layanan' => $this->whenLoaded('kelasType', fn () => $this->kelasType->jenis()?->value),
            'program_id' => $this->program_id,
            'santri_program_id' => $this->santri_program_id,
            'lembaga_id' => $this->lembaga_id,
            'nominal' => (float) $this->nominal,
            'satuan' => $this->satuan,
            'berlaku_mulai' => $this->berlaku_mulai->toDateString(),
            'berlaku_sampai' => $this->berlaku_sampai?->toDateString(),
            'aktif' => $this->aktif,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
