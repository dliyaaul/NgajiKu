<?php

namespace App\Http\Resources;

use App\Models\Pembayaran;
use App\Models\PembayaranLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengajuan pembayaran. `bukti_url` memerlukan token yang sama (Authorization: Bearer).
 *
 * @mixin Pembayaran
 */
class PembayaranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_pembayaran' => $this->nomor_pembayaran,
            'tagihan_id' => $this->tagihan_id,
            'rekening_tujuan_id' => $this->rekening_tujuan_id,
            'tanggal_transfer' => $this->tanggal_transfer->toDateString(),
            'nominal_transfer' => (float) $this->nominal_transfer,
            'nama_pengirim' => $this->nama_pengirim,
            'rekening_pengirim' => $this->rekening_pengirim,
            'catatan' => $this->catatan,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'alasan_penolakan' => $this->alasan_penolakan,
            'diverifikasi_at' => $this->diverifikasi_at?->toIso8601String(),
            'bukti_url' => $request->is('api/*')
                ? route('api.v1.pembayaran.bukti', $this->id)
                : route('pembayaran.bukti', $this->id),
            'bukti_pdf' => str_ends_with(strtolower($this->bukti_transfer), '.pdf'),
            'created_at' => $this->created_at?->toIso8601String(),
            'rekening' => $this->whenLoaded('rekeningTujuan', fn () => (new RekeningResource($this->rekeningTujuan))->resolve($request)),
            'tagihan' => $this->whenLoaded('tagihan', fn () => [
                'id' => $this->tagihan->id,
                'nomor_tagihan' => $this->tagihan->nomor_tagihan,
                'total' => (float) $this->tagihan->total,
                'periode_mulai' => $this->tagihan->periode_mulai->toDateString(),
                'periode_selesai' => $this->tagihan->periode_selesai->toDateString(),
                'nama_santri' => $this->tagihan->santriProgram?->santri?->username,
                'nama_lembaga' => $this->tagihan->lembaga?->nama_lembaga,
            ]),
            'log' => $this->whenLoaded('log', fn () => $this->log->sortBy('created_at')->values()->map(fn (PembayaranLog $log) => [
                'status_lama' => $log->status_lama,
                'status_baru' => $log->status_baru,
                'catatan' => $log->catatan,
                'created_at' => $log->created_at?->toIso8601String(),
            ])),
        ];
    }
}
