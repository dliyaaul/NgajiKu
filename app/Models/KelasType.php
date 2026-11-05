<?php

namespace App\Models;

use App\Enums\JenisLayanan;
use Database\Factories\KelasTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jenis layanan kelas: Reguler, Privat, atau Lembaga (dibedakan dari namanya).
 */
#[Fillable(['nama'])]
class KelasType extends Model
{
    /** @use HasFactory<KelasTypeFactory> */
    use HasFactory, HasUuids;

    public function jenis(): ?JenisLayanan
    {
        return JenisLayanan::dariNama($this->nama);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function berjenis(Builder $query, JenisLayanan $jenis): void
    {
        $query->whereRaw('LOWER(nama) = ?', [$jenis->value]);
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
}
