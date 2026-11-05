<?php

namespace App\Http\Resources;

use App\Models\Pertemuan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pertemuan BBAQ/Tahfidz/Tarjamah dalam bentuk seragam. Kolom pengganti
 * yang berbeda nama di tiap tabel diseragamkan menjadi `pengganti_id`.
 *
 * @mixin Pertemuan
 */
class PertemuanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'kelas_pengajaran_id' => $this->kelas_pengajaran_id,
            'tanggal' => $this->tanggal->toDateString(),
            'metode' => $this->metode,
            'materi_pembelajaran' => $this->materi_pembelajaran,
            'pengajar_id' => $this->pengajar_id,
            'pengganti_id' => $this->penggantiId(),
            'pengajar' => $this->whenLoaded('pengajar', fn () => $this->pengajar ? [
                'id' => $this->pengajar->id,
                'username' => $this->pengajar->username,
            ] : null),
            'pengganti' => $this->whenLoaded('pengganti', fn () => $this->pengganti ? [
                'id' => $this->pengganti->id,
                'nama' => $this->pengganti->nama,
            ] : null),
            'rekap' => $this->whenLoaded('absensi', fn () => $this->rekap()),
            'editable' => $this->isEditable(),
            'batas_edit' => $this->batasEdit()?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
