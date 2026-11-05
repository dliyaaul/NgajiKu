<?php

namespace App\Enums;

enum StatusPembayaran: string
{
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
        };
    }
}
