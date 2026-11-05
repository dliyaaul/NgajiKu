<?php

namespace App\Models;

use Database\Factories\TahfidzPertemuanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

#[Table('tahfidz_pertemuan')]
#[UseFactory(TahfidzPertemuanFactory::class)]
#[Fillable(['program_id', 'kelas_pengajaran_id', 'tanggal', 'pengajar_id', 'pengganti_pengajar_id', 'materi_pembelajaran', 'metode', 'created_by'])]
class TahfidzPertemuan extends Pertemuan
{
    public static function penggantiColumn(): string
    {
        return 'pengganti_pengajar_id';
    }

    public static function absensiModel(): string
    {
        return AbsensiTahfidz::class;
    }

    public static function absensiForeignKey(): string
    {
        return 'tahfidz_pertemuan_id';
    }
}
