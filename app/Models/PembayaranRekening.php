<?php

namespace App\Models;

use Database\Factories\PembayaranRekeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Rekening tujuan transfer pembayaran.
 */
#[Table('pembayaran_rekening')]
#[Fillable(['nama_bank', 'nomor_rekening', 'nama_pemilik', 'keterangan', 'aktif', 'urutan', 'created_by'])]
class PembayaranRekening extends Model
{
    /** @use HasFactory<PembayaranRekeningFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * Rekening yang ditawarkan kepada pembayar, urut sesuai `urutan`.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aktifUrut(Builder $query): void
    {
        $query->where('aktif', true)->orderBy('urutan')->orderBy('nama_bank');
    }
}
