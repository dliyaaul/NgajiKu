<?php

namespace App\Models;

use Database\Factories\SantriProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Keanggotaan santri pada program (beserta kelas, pengajar, dan lembaganya).
 * Absensi santri selalu menunjuk ke baris ini, bukan langsung ke user.
 */
#[Fillable(['santri_id', 'program_id', 'kelas_type_id', 'kelas_pengajaran_id', 'pengajar_id', 'lembaga_id', 'default_pbm'])]
class SantriProgram extends Model
{
    /** @use HasFactory<SantriProgramFactory> */
    use HasFactory, HasUuids;

    /**
     * Alasan perpindahan kelas yang ikut dicatat di riwayat pada penyimpanan berikutnya.
     */
    public ?string $alasanPindahKelas = null;

    /**
     * Port trigger Supabase `catat_riwayat_kelas_santri`: penempatan kelas pertama dan
     * setiap perpindahan kelas dicatat ke `riwayat_kelas_santri`.
     */
    protected static function booted(): void
    {
        static::created(function (SantriProgram $pendaftaran): void {
            if ($pendaftaran->kelas_pengajaran_id !== null) {
                $pendaftaran->catatRiwayatKelas(null);
            }
        });

        static::updated(function (SantriProgram $pendaftaran): void {
            if ($pendaftaran->wasChanged('kelas_pengajaran_id')) {
                $pendaftaran->catatRiwayatKelas($pendaftaran->getOriginal('kelas_pengajaran_id'));
            }
        });
    }

    /**
     * Pendaftaran yang sudah punya absensi, tagihan, atau tarif tidak boleh dihapus
     * (absensi ikut terhapus secara cascade) maupun dipindah program.
     */
    public function punyaRiwayatTransaksi(): bool
    {
        return Absensi::query()->where('santri_program_id', $this->id)->exists()
            || AbsensiTahfidz::query()->where('santri_program_id', $this->id)->exists()
            || AbsensiTarjamah::query()->where('santri_program_id', $this->id)->exists()
            || Tagihan::query()->where('santri_program_id', $this->id)->exists()
            || PembayaranTarif::query()->where('santri_program_id', $this->id)->exists();
    }

    private function catatRiwayatKelas(?string $kelasAsalId): void
    {
        $this->riwayatKelas()->create([
            'kelas_asal_id' => $kelasAsalId,
            'kelas_tujuan_id' => $this->kelas_pengajaran_id,
            'berlaku_mulai' => now()->toDateString(),
            'diubah_oleh' => auth()->id(),
            'alasan' => filled($this->alasanPindahKelas) ? $this->alasanPindahKelas : null,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(User::class, 'santri_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<KelasType, $this>
     */
    public function kelasType(): BelongsTo
    {
        return $this->belongsTo(KelasType::class);
    }

    /**
     * @return BelongsTo<KelasPengajaran, $this>
     */
    public function kelasPengajaran(): BelongsTo
    {
        return $this->belongsTo(KelasPengajaran::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * @return BelongsTo<Lembaga, $this>
     */
    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    /**
     * @return HasMany<RiwayatKelasSantri, $this>
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSantri::class);
    }
}
