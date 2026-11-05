<?php

use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\KelasController;
use App\Http\Controllers\Api\V1\PembayaranController;
use App\Http\Controllers\Api\V1\PertemuanController;
use App\Http\Controllers\Api\V1\RekeningController;
use App\Http\Controllers\Api\V1\RiwayatAbsensiController;
use App\Http\Controllers\Api\V1\TagihanController;
use App\Http\Controllers\Api\V1\TarifController;
use App\Http\Controllers\BuktiTransferController;
use Illuminate\Support\Facades\Route;

/*
| API untuk aplikasi mobile (Flutter). Autentikasi memakai token Sanctum:
| POST /api/v1/auth/login → kirim `Authorization: Bearer {token}` di request berikutnya.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Tagihan & pembayaran. Daftar/detail tagihan otomatis dibatasi ke milik pengguna
        // (admin: semua). Pengajuan pembayaran hanya oleh pemilik tagihan.
        Route::whereUuid(['tagihan', 'pembayaran', 'rekening', 'tarif'])->group(function () {
            Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
            Route::get('/tagihan/{tagihan}', [TagihanController::class, 'show'])->name('tagihan.show');
            Route::post('/tagihan/{tagihan}/pembayaran', [PembayaranController::class, 'store'])->name('tagihan.bayar');
            Route::get('/pembayaran/rekening', [RekeningController::class, 'index'])->name('rekening.index');
            Route::get('/pembayaran/{pembayaran}/bukti', BuktiTransferController::class)->name('pembayaran.bukti');

            Route::middleware('role:admin')->group(function () {
                Route::post('/tagihan/generate-reguler', [TagihanController::class, 'generateReguler'])->name('tagihan.generate-reguler');

                Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
                Route::get('/pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
                Route::post('/pembayaran/{pembayaran}/verifikasi', [PembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');

                Route::post('/pembayaran/rekening', [RekeningController::class, 'store'])->name('rekening.store');
                Route::put('/pembayaran/rekening/{rekening}', [RekeningController::class, 'update'])->name('rekening.update');
                Route::patch('/pembayaran/rekening/{rekening}/aktif', [RekeningController::class, 'setAktif'])->name('rekening.aktif');

                Route::get('/pembayaran/tarif', [TarifController::class, 'index'])->name('tarif.index');
                Route::get('/pembayaran/tarif/opsi', [TarifController::class, 'opsi'])->name('tarif.opsi');
                Route::post('/pembayaran/tarif', [TarifController::class, 'store'])->name('tarif.store');
                Route::get('/pembayaran/tarif/{tarif}', [TarifController::class, 'show'])->name('tarif.show');
                Route::put('/pembayaran/tarif/{tarif}', [TarifController::class, 'update'])->name('tarif.update');
                Route::patch('/pembayaran/tarif/{tarif}/aktif', [TarifController::class, 'setAktif'])->name('tarif.aktif');
            });
        });

        Route::get('/santri/riwayat-absensi', RiwayatAbsensiController::class)
            ->middleware('role:santri')
            ->name('santri.riwayat-absensi');

        Route::middleware('role:admin,pengajar')
            ->prefix('absensi')
            ->name('absensi.')
            ->whereUuid(['kelas', 'pertemuan'])
            ->group(function () {
                Route::get('/programs', [KelasController::class, 'programs'])->name('programs');
                Route::get('/{program}/kelas', [KelasController::class, 'index'])->name('kelas.index');
                Route::get('/{program}/opsi', [KelasController::class, 'opsi'])->name('opsi');

                Route::get('/{program}/kelas/{kelas}/pertemuan', [PertemuanController::class, 'index'])->name('pertemuan.index');
                Route::post('/{program}/kelas/{kelas}/pertemuan', [PertemuanController::class, 'store'])->name('pertemuan.store');
                Route::get('/{program}/pertemuan/{pertemuan}', [PertemuanController::class, 'show'])->name('pertemuan.show');
                Route::put('/{program}/pertemuan/{pertemuan}', [PertemuanController::class, 'update'])->name('pertemuan.update');
                Route::delete('/{program}/pertemuan/{pertemuan}', [PertemuanController::class, 'destroy'])->name('pertemuan.destroy');

                Route::get('/{program}/pertemuan/{pertemuan}/absensi', [AbsensiController::class, 'show'])->name('show');
                Route::put('/{program}/pertemuan/{pertemuan}/absensi', [AbsensiController::class, 'update'])->name('update');
            });
    });
});
