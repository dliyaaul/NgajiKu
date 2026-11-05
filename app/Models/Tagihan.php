<?php

namespace App\Models;

use App\Enums\StatusTagihan;
use App\Enums\TipePembayar;
use App\Enums\UserRole;
use Database\Factories\TagihanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tagihan untuk santri (Reguler/Privat) atau koordinator lembaga (Lembaga).
 */
#[Table('tagihan')]
#[Fillable(['nomor_tagihan', 'tipe_pembayar', 'santri_program_id', 'lembaga_id', 'periode_mulai', 'periode_selesai', 'tanggal_tagihan', 'tanggal_jatuh_tempo', 'subtotal', 'diskon', 'total', 'status', 'created_by'])]
class Tagihan extends Model
{
    /** @use HasFactory<TagihanFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe_pembayar' => TipePembayar::class,
            'status' => StatusTagihan::class,
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'tanggal_tagihan' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Tagihan yang boleh dilihat pengguna (pengganti RLS Supabase): admin melihat semua,
     * santri hanya tagihan pendaftarannya, koordinator hanya tagihan lembaganya.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function terlihatOleh(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Admin => null,
            UserRole::Santri => $query
                ->where('tipe_pembayar', TipePembayar::Santri)
                ->whereHas('santriProgram', fn (Builder $santriProgram) => $santriProgram->where('santri_id', $user->id)),
            UserRole::KoordinatorLembaga => $query
                ->where('tipe_pembayar', TipePembayar::KoordinatorLembaga)
                ->where('lembaga_id', $user->lembaga_id ?? ''),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Pencarian nomor tagihan, nama santri, atau nama lembaga.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function cari(Builder $query, ?string $kata): void
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return;
        }

        $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $kata).'%';

        $query->where(fn (Builder $query) => $query
            ->where('nomor_tagihan', 'like', $pola)
            ->orWhereHas('santriProgram.santri', fn (Builder $santri) => $santri->where('username', 'like', $pola))
            ->orWhereHas('lembaga', fn (Builder $lembaga) => $lembaga->where('nama_lembaga', 'like', $pola)));
    }

    /**
     * Relasi yang dibutuhkan untuk menampilkan nama pembayar.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function denganPembayar(Builder $query): void
    {
        $query->with(['santriProgram.santri:id,username', 'santriProgram.program:id,nama', 'lembaga:id,nama_lembaga']);
    }

    /**
     * Pengguna adalah pihak yang wajib membayar tagihan ini.
     */
    public function dimilikiOleh(User $user): bool
    {
        return match ($this->tipe_pembayar) {
            TipePembayar::Santri => $user->role === UserRole::Santri
                && $this->santriProgram?->santri_id === $user->id,
            TipePembayar::KoordinatorLembaga => $user->role === UserRole::KoordinatorLembaga
                && $user->lembaga_id !== null
                && $this->lembaga_id === $user->lembaga_id,
        };
    }

    public function bisaDibayar(): bool
    {
        return $this->status->bisaDibayar();
    }

    /**
     * @return HasMany<TagihanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TagihanItem::class);
    }

    /**
     * @return HasMany<Pembayaran, $this>
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
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
