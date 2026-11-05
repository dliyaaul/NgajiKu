<?php

namespace App\Http\Resources;

use App\Models\Tagihan;
use App\Models\TagihanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tagihan beserta rinciannya (field sama dengan `TagihanModel` di aplikasi mobile).
 *
 * @mixin Tagihan
 */
class TagihanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_tagihan' => $this->nomor_tagihan,
            'tipe_pembayar' => $this->tipe_pembayar->value,
            'santri_program_id' => $this->santri_program_id,
            'lembaga_id' => $this->lembaga_id,
            'periode_mulai' => $this->periode_mulai->toDateString(),
            'periode_selesai' => $this->periode_selesai->toDateString(),
            'tanggal_tagihan' => $this->tanggal_tagihan->toDateString(),
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'diskon' => (float) $this->diskon,
            'total' => (float) $this->total,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'bisa_dibayar' => $this->bisaDibayar(),
            'nama_santri' => $this->santriProgram?->santri?->username,
            'nama_program' => $this->santriProgram?->program?->nama,
            'nama_lembaga' => $this->lembaga?->nama_lembaga,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (TagihanItem $item) => [
                'id' => $item->id,
                'deskripsi' => $item->deskripsi,
                'jenis' => $item->jenis,
                'referensi_id' => $item->referensi_id,
                'tanggal' => $item->tanggal?->toDateString(),
                'jumlah' => (float) $item->jumlah,
                'satuan' => $item->satuan,
                'harga_satuan' => (float) $item->harga_satuan,
                'subtotal' => (float) $item->subtotal,
                'metadata' => $item->metadata,
            ])),
            // Di-resolve langsung agar bentuknya sama di respons API dan prop Inertia (tanpa pembungkus "data").
            'pembayaran' => $this->whenLoaded('pembayaran', fn () => PembayaranResource::collection($this->pembayaran)->resolve($request)),
        ];
    }
}
