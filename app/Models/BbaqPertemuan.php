<?php

namespace App\Models;

use Database\Factories\BbaqPertemuanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

#[Table('bbaq_pertemuan')]
#[UseFactory(BbaqPertemuanFactory::class)]
#[Fillable(['program_id', 'kelas_pengajaran_id', 'tanggal', 'pengajar_id', 'pengganti_pengajar_id', 'materi_pembelajaran', 'metode', 'created_by'])]
class BbaqPertemuan extends Pertemuan
{
    public static function penggantiColumn(): string
    {
        return 'pengganti_pengajar_id';
    }

    public static function absensiModel(): string
    {
        return Absensi::class;
    }

    public static function absensiForeignKey(): string
    {
        return 'bbaq_pertemuan_id';
    }
}
