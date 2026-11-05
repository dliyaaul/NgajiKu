<?php

namespace App\Enums;

/**
 * Peran pengguna NgajiKu (kolom `users.role`, sebelumnya `profiles.role`).
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Pengajar = 'pengajar';
    case Santri = 'santri';
    case KoordinatorLembaga = 'koordinator_lembaga';

    /**
     * Halaman awal web setelah login; null bila role ini hanya memakai aplikasi mobile.
     */
    public function dashboardRoute(): ?string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Staff => 'staff.dashboard',
            self::Pengajar => 'pengajar.dashboard',
            self::Santri => 'santri.dashboard',
            self::KoordinatorLembaga => null,
        };
    }

    public function bisaMemakaiWeb(): bool
    {
        return $this->dashboardRoute() !== null;
    }
}
