<?php

namespace App\Models;

use Database\Factories\TarjamahPertemuanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

#[Table('tarjamah_pertemuan')]
#[UseFactory(TarjamahPertemuanFactory::class)]
#[Fillable(['program_id', 'kelas_pengajaran_id', 'tanggal', 'pengajar_id', 'pengajar_pengganti_id', 'materi_pembelajaran', 'metode', 'created_by'])]
class TarjamahPertemuan extends Pertemuan
{
    public static function penggantiColumn(): string
    {
        return 'pengajar_pengganti_id';
    }

    public static function absensiModel(): string
    {
        return AbsensiTarjamah::class;
    }

    public static function absensiForeignKey(): string
    {
        return 'tarjamah_pertemuan_id';
    }
}
