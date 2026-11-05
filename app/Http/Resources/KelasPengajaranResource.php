<?php

namespace App\Http\Resources;

use App\Models\KelasPengajaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KelasPengajaran
 */
class KelasPengajaranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_kelas' => $this->nama_kelas,
            'program_id' => $this->program_id,
            'kelas_type_id' => $this->kelas_type_id,
            'lembaga_id' => $this->lembaga_id,
            'aktif' => $this->aktif,
            'pengajar' => $this->whenLoaded('pengajar', fn () => $this->pengajar ? [
                'id' => $this->pengajar->id,
                'username' => $this->pengajar->username,
            ] : null),
        ];
    }
}
