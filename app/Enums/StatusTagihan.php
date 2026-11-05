<?php

namespace App\Enums;

enum StatusTagihan: string
{
    case BelumBayar = 'belum_bayar';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Lunas = 'lunas';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Belum Bayar',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Lunas => 'Lunas',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Pembayaran baru hanya bisa diajukan untuk tagihan yang belum dibayar
     * atau yang pembayaran sebelumnya ditolak.
     */
    public function bisaDibayar(): bool
    {
        return $this === self::BelumBayar || $this === self::Ditolak;
    }
}
