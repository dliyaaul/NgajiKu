<?php

namespace App\Models;

use App\Services\Absensi\AbsensiProgram;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'deskripsi'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, HasUuids;

    /**
     * Program yang namanya dipakai fitur absensi (lihat AbsensiProgram) tidak boleh diganti/dihapus.
     */
    public function terkunci(): bool
    {
        return collect(AbsensiProgram::cases())->contains(fn (AbsensiProgram $program) => $program->programName() === $this->nama);
    }

    /**
     * @return HasMany<KelasPengajaran, $this>
     */
    public function kelasPengajaran(): HasMany
    {
        return $this->hasMany(KelasPengajaran::class);
    }

    /**
     * @return HasMany<PengajarProgram, $this>
     */
    public function pengajarPrograms(): HasMany
    {
        return $this->hasMany(PengajarProgram::class);
    }

    /**
     * @return HasMany<SantriProgram, $this>
     */
    public function santriPrograms(): HasMany
    {
        return $this->hasMany(SantriProgram::class);
    }

    /**
     * @return HasMany<Lembaga, $this>
     */
    public function lembaga(): HasMany
    {
        return $this->hasMany(Lembaga::class);
    }
}
