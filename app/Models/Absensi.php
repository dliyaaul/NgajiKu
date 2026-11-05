<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Absensi BBAQ (berpertemuan) dan absensi kegiatan PTL (`program` = PTL,
 * tanpa pertemuan, menunjuk langsung ke `user_id`).
 */
#[Table('absensi')]
#[Fillable(['bbaq_pertemuan_id', 'santri_program_id', 'user_id', 'tanggal', 'program', 'kegiatan', 'pbm_aktual', 'status', 'catatan', 'created_by'])]
class Absensi extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * @return BelongsTo<BbaqPertemuan, $this>
     */
    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(BbaqPertemuan::class, 'bbaq_pertemuan_id');
    }

    /**
     * @return BelongsTo<SantriProgram, $this>
     */
    public function santriProgram(): BelongsTo
    {
        return $this->belongsTo(SantriProgram::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
