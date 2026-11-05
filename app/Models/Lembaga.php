<?php

namespace App\Models;

use Database\Factories\LembagaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('lembaga')]
#[Fillable(['nama_lembaga', 'pbm_type', 'alamat', 'program_id', 'pengajar_id'])]
class Lembaga extends Model
{
    /** @use HasFactory<LembagaFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Kolom lama; pengajar lembaga kini mengikuti kelas Lembaga-nya.
     *
     * @return BelongsTo<User, $this>
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * Satu lembaga memiliki paling banyak satu kelas (unique `kelas_pengajaran.lembaga_id`).
     *
     * @return HasOne<KelasPengajaran, $this>
     */
    public function kelas(): HasOne
    {
        return $this->hasOne(KelasPengajaran::class);
    }

    /**
     * @return HasMany<KelasPengajaran, $this>
     */
    public function kelasPengajaran(): HasMany
    {
        return $this->hasMany(KelasPengajaran::class);
    }

    /**
     * @return HasMany<SantriProgram, $this>
     */
    public function santriPrograms(): HasMany
    {
        return $this->hasMany(SantriProgram::class);
    }

    /**
     * Akun koordinator lembaga (aplikasi mobile).
     *
     * @return HasMany<User, $this>
     */
    public function koordinator(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
