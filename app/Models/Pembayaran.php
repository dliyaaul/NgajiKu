<?php

namespace App\Models;

use App\Enums\StatusPembayaran;
use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Konfirmasi transfer dari pembayar. Status: menunggu_verifikasi, diterima, ditolak.
 * `bukti_transfer` berisi path file (sebelumnya path di bucket Supabase `bukti-transfer`).
 */
#[Table('pembayaran')]
#[Fillable(['nomor_pembayaran', 'tagihan_id', 'rekening_tujuan_id', 'tanggal_transfer', 'nominal_transfer', 'nama_pengirim', 'rekening_pengirim', 'bukti_transfer', 'catatan', 'status', 'diverifikasi_oleh', 'diverifikasi_at', 'alasan_penolakan', 'created_by'])]
class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusPembayaran::class,
            'tanggal_transfer' => 'date',
            'nominal_transfer' => 'decimal:2',
            'diverifikasi_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PembayaranLog, $this>
     */
    public function log(): HasMany
    {
        return $this->hasMany(PembayaranLog::class);
    }

    /**
     * @return BelongsTo<Tagihan, $this>
     */
    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    /**
     * @return BelongsTo<PembayaranRekening, $this>
     */
    public function rekeningTujuan(): BelongsTo
    {
        return $this->belongsTo(PembayaranRekening::class, 'rekening_tujuan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
