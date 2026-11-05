<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Absensi Tahfidz beserta setoran hafalan (surat & rentang ayat).
 */
#[Table('absensi_tahfidz')]
#[Fillable(['tahfidz_pertemuan_id', 'santri_program_id', 'tanggal', 'status', 'jenis_tahfidz', 'surat', 'ayat_mulai', 'ayat_selesai', 'keterangan', 'created_by'])]
class AbsensiTahfidz extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'ayat_mulai' => 'integer',
            'ayat_selesai' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TahfidzPertemuan, $this>
     */
    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(TahfidzPertemuan::class, 'tahfidz_pertemuan_id');
    }

    /**
     * @return BelongsTo<SantriProgram, $this>
     */
    public function santriProgram(): BelongsTo
    {
        return $this->belongsTo(SantriProgram::class);
    }
}
