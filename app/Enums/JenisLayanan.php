<?php

namespace App\Enums;

/**
 * Jenis layanan dari nama `kelas_types` (Reguler, Privat, Lembaga). Aturan kelas,
 * pendaftaran, dan tarif bergantung pada jenis ini.
 */
enum JenisLayanan: string
{
    case Reguler = 'reguler';
    case Privat = 'privat';
    case Lembaga = 'lembaga';

    public static function dariNama(?string $nama): ?self
    {
        return self::tryFrom(strtolower(trim((string) $nama)));
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Satuan tarif: Reguler per bulan, Privat & Lembaga per pertemuan.
     */
    public function satuanTarif(): string
    {
        return $this === self::Reguler ? 'bulan' : 'pertemuan';
    }
}
