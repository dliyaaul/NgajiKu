<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus data induk dengan aman: bila masih dirujuk data lain (foreign key RESTRICT),
 * penghapusan dibatalkan dan pengguna mendapat pesan yang jelas, bukan halaman galat.
 */
trait HapusDataInduk
{
    /**
     * @param  callable(): void|null  $sebelumHapus  dijalankan dalam transaksi yang sama
     */
    protected function hapusDataInduk(Model $model, string $berhasil, string $masihDipakai, ?callable $sebelumHapus = null): RedirectResponse
    {
        try {
            DB::transaction(function () use ($model, $sebelumHapus): void {
                if ($sebelumHapus !== null) {
                    $sebelumHapus();
                }

                $model->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->with('error', $masihDipakai);
        }

        return back()->with('success', $berhasil);
    }
}
