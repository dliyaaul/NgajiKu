<?php

namespace App\Services\Absensi;

use App\Models\BbaqPertemuan;
use App\Models\Pertemuan;
use App\Models\TahfidzPertemuan;
use App\Models\TarjamahPertemuan;
use Illuminate\Database\Eloquent\Model;

/**
 * Program yang memiliki fitur absensi beserta model pertemuan & absensinya.
 */
enum AbsensiProgram: string
{
    case Bbaq = 'bbaq';
    case Tahfidz = 'tahfidz';
    case Tarjamah = 'tarjamah';

    public const STATUS_HADIR = 'Hadir';

    public const STATUS_BELUM_ABSEN = 'Belum Absen';

    /**
     * Status yang ditampilkan & diterima dari formulir/API.
     *
     * @var list<string>
     */
    public const STATUSES = ['Hadir', 'Izin', 'Sakit', 'Alpha'];

    /**
     * Ejaan lama "Alpa" yang masih dipakai tabel Tahfidz & Tarjamah (dan aplikasi mobile).
     */
    public const STATUS_ALPA = 'Alpa';

    /**
     * @var list<string>
     */
    public const METODE = ['Offline', 'Online'];

    /**
     * @var list<string>
     */
    public const JENIS_TAHFIDZ = ['Ziyadah', 'Murojaah'];

    public function label(): string
    {
        return match ($this) {
            self::Bbaq => 'BBAQ',
            self::Tahfidz => 'Tahfidz',
            self::Tarjamah => 'Tarjamah',
        };
    }

    /**
     * Nama program pada tabel `programs`.
     */
    public function programName(): string
    {
        return match ($this) {
            self::Bbaq => 'BBAQ',
            self::Tahfidz => 'Tahfidz',
            self::Tarjamah => 'Tarjamah Lafdziyah',
        };
    }

    /**
     * @return class-string<Pertemuan>
     */
    public function pertemuanModel(): string
    {
        return match ($this) {
            self::Bbaq => BbaqPertemuan::class,
            self::Tahfidz => TahfidzPertemuan::class,
            self::Tarjamah => TarjamahPertemuan::class,
        };
    }

    /**
     * @return class-string<Model>
     */
    public function absensiModel(): string
    {
        return $this->pertemuanModel()::absensiModel();
    }

    /**
     * Kolom pada tabel absensi yang menunjuk ke tabel pertemuan.
     */
    public function pertemuanForeignKey(): string
    {
        return $this->pertemuanModel()::absensiForeignKey();
    }

    /**
     * Kolom pengajar pengganti pada tabel pertemuan.
     */
    public function penggantiColumn(): string
    {
        return $this->pertemuanModel()::penggantiColumn();
    }

    /**
     * Nilai status yang disimpan: `absensi` (BBAQ) memakai "Alpha", sedangkan
     * `absensi_tahfidz` & `absensi_tarjamah` memakai "Alpa".
     */
    public function statusTersimpan(string $status): string
    {
        return $status === 'Alpha' && $this !== self::Bbaq ? self::STATUS_ALPA : $status;
    }

    /**
     * Menyeragamkan status dari database untuk ditampilkan ("Alpa" → "Alpha").
     */
    public static function statusTampil(?string $status): ?string
    {
        return $status !== null && strcasecmp($status, self::STATUS_ALPA) === 0 ? 'Alpha' : $status;
    }

    public function hasHafalan(): bool
    {
        return $this === self::Tahfidz;
    }

    /**
     * Tanggal pada baris absensi mengikuti tanggal pertemuan (BBAQ & Tahfidz).
     */
    public function absensiMenyimpanTanggal(): bool
    {
        return $this !== self::Tarjamah;
    }
}
