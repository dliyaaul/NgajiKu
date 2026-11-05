<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan perpindahan kelas santri.
 */
#[Table('riwayat_kelas_santri')]
#[Fillable(['santri_program_id', 'kelas_asal_id', 'kelas_tujuan_id', 'berlaku_mulai', 'alasan', 'diubah_oleh'])]
class RiwayatKelasSantri extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
        ];
    }

    /**
     * @return BelongsTo<SantriProgram, $this>
     */
    public function santriProgram(): BelongsTo
    {
        return $this->belongsTo(SantriProgram::class);
    }

    /**
     * @return BelongsTo<KelasPengajaran, $this>
     */
    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(KelasPengajaran::class, 'kelas_asal_id');
    }

    /**
     * @return BelongsTo<KelasPengajaran, $this>
     */
    public function kelasTujuan(): BelongsTo
    {
        return $this->belongsTo(KelasPengajaran::class, 'kelas_tujuan_id');
    }

    /**
     * Admin/staff yang memindahkan kelas.
     *
     * @return BelongsTo<User, $this>
     */
    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
