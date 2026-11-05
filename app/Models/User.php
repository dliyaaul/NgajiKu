<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun sekaligus profil pengguna (gabungan `auth.users` + `profiles` Supabase).
 */
#[Fillable(['username', 'email', 'password', 'role', 'alamat', 'no_hp', 'angkatan', 'avatar_url', 'lembaga_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPengajar(): bool
    {
        return $this->role === UserRole::Pengajar;
    }

    /**
     * @return BelongsTo<Lembaga, $this>
     */
    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    /**
     * Keanggotaan santri pada program.
     *
     * @return HasMany<SantriProgram, $this>
     */
    public function santriPrograms(): HasMany
    {
        return $this->hasMany(SantriProgram::class, 'santri_id');
    }

    /**
     * Program yang diajar (untuk pengajar).
     *
     * @return HasMany<PengajarProgram, $this>
     */
    public function pengajarPrograms(): HasMany
    {
        return $this->hasMany(PengajarProgram::class, 'pengajar_id');
    }

    /**
     * @return HasMany<KelasPengajaran, $this>
     */
    public function kelasDiajar(): HasMany
    {
        return $this->hasMany(KelasPengajaran::class, 'pengajar_id');
    }
}
