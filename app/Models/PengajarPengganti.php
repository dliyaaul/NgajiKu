<?php

namespace App\Models;

use Database\Factories\PengajarPenggantiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengajar pengganti dari luar (bukan akun pengguna).
 */
#[Table('pengajar_pengganti')]
#[Fillable(['nama', 'no_hp', 'aktif', 'created_by'])]
class PengajarPengganti extends Model
{
    /** @use HasFactory<PengajarPenggantiFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * @return HasMany<BbaqPertemuan, $this>
     */
    public function bbaqPertemuan(): HasMany
    {
        return $this->hasMany(BbaqPertemuan::class, BbaqPertemuan::penggantiColumn());
    }

    /**
     * @return HasMany<TahfidzPertemuan, $this>
     */
    public function tahfidzPertemuan(): HasMany
    {
        return $this->hasMany(TahfidzPertemuan::class, TahfidzPertemuan::penggantiColumn());
    }

    /**
     * @return HasMany<TarjamahPertemuan, $this>
     */
    public function tarjamahPertemuan(): HasMany
    {
        return $this->hasMany(TarjamahPertemuan::class, TarjamahPertemuan::penggantiColumn());
    }
}
