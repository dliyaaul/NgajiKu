<?php

namespace App\Models;

use Database\Factories\PembayaranTarifFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarif per jenis kelas; dapat dikhususkan untuk program, santri, atau lembaga.
 */
#[Table('pembayaran_tarif')]
#[Fillable(['nama_tarif', 'kelas_type_id', 'program_id', 'santri_program_id', 'lembaga_id', 'nominal', 'satuan', 'berlaku_mulai', 'berlaku_sampai', 'aktif', 'created_by'])]
class PembayaranTarif extends Model
{
    /** @use HasFactory<PembayaranTarifFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KelasType, $this>
     */
    public function kelasType(): BelongsTo
    {
        return $this->belongsTo(KelasType::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<SantriProgram, $this>
     */
    public function santriProgram(): BelongsTo
    {
        return $this->belongsTo(SantriProgram::class);
    }

    /**
     * @return BelongsTo<Lembaga, $this>
     */
    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }
}
