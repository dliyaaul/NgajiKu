<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('absensi_tarjamah')]
#[Fillable(['tarjamah_pertemuan_id', 'santri_program_id', 'status', 'created_by'])]
class AbsensiTarjamah extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<TarjamahPertemuan, $this>
     */
    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(TarjamahPertemuan::class, 'tarjamah_pertemuan_id');
    }

    /**
     * @return BelongsTo<SantriProgram, $this>
     */
    public function santriProgram(): BelongsTo
    {
        return $this->belongsTo(SantriProgram::class);
    }
}
