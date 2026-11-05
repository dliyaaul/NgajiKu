<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AbsensiPertemuanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiTransferController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerateTagihanController;
use App\Http\Controllers\KelasPengajaranController;
use App\Http\Controllers\KelasTypeController;
use App\Http\Controllers\LembagaController;
use App\Http\Controllers\LpqController;
use App\Http\Controllers\PengajarPenggantiController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PertemuanController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RekeningPembayaranController;
use App\Http\Controllers\RiwayatAbsensiController;
use App\Http\Controllers\SantriProgramController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TagihanSantriController;
use App\Http\Controllers\TarifPembayaranController;
use App\Http\Controllers\VerifikasiPembayaranController;
use Illuminate\Support\Facades\Route;

// Halaman login sudah mengarahkan pengguna yang masuk ke dashboard sesuai perannya.
Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.process');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Dashboard Santri
Route::middleware(['auth', 'role:santri'])
    ->prefix('santri')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'santri'])
            ->name('santri.dashboard');

        Route::get('/riwayat-absensi', [RiwayatAbsensiController::class, 'index'])
            ->name('santri.riwayat-absensi');

        Route::whereUuid('tagihan')->group(function () {
            Route::get('/tagihan', [TagihanSantriController::class, 'index'])->name('santri.tagihan.index');
            Route::get('/tagihan/{tagihan}', [TagihanSantriController::class, 'show'])->name('santri.tagihan.show');
            Route::post('/tagihan/{tagihan}/bayar', [TagihanSantriController::class, 'bayar'])->name('santri.tagihan.bayar');
        });
    });

// Bukti transfer (admin atau pemilik tagihan; dicek policy).
Route::get('/pembayaran/{pembayaran}/bukti', BuktiTransferController::class)
    ->middleware('auth')
    ->whereUuid('pembayaran')
    ->name('pembayaran.bukti');

// Absensi BBAQ, Tahfidz, Tarjamah (Admin & Pengajar)
Route::middleware(['auth', 'role:admin,pengajar'])
    ->prefix('absensi')
    ->name('absensi.')
    ->whereUuid(['kelas', 'pertemuan'])
    ->group(function () {
        Route::get('/', [AbsensiController::class, 'index'])->name('index');
        Route::get('/{program}', [AbsensiController::class, 'kelas'])->name('kelas');

        Route::get('/{program}/kelas/{kelas}', [PertemuanController::class, 'index'])
            ->name('pertemuan.index');
        Route::get('/{program}/kelas/{kelas}/pertemuan/create', [PertemuanController::class, 'create'])
            ->name('pertemuan.create');
        Route::post('/{program}/kelas/{kelas}/pertemuan', [PertemuanController::class, 'store'])
            ->name('pertemuan.store');
        Route::get('/{program}/pertemuan/{pertemuan}/edit', [PertemuanController::class, 'edit'])
            ->name('pertemuan.edit');
        Route::put('/{program}/pertemuan/{pertemuan}', [PertemuanController::class, 'update'])
            ->name('pertemuan.update');
        Route::delete('/{program}/pertemuan/{pertemuan}', [PertemuanController::class, 'destroy'])
            ->name('pertemuan.destroy');

        Route::get('/{program}/pertemuan/{pertemuan}', [AbsensiPertemuanController::class, 'show'])
            ->name('show');
        Route::put('/{program}/pertemuan/{pertemuan}/absensi', [AbsensiPertemuanController::class, 'update'])
            ->name('update');
    });

// Dashboard Pengajar
Route::middleware(['auth', 'role:pengajar'])
    ->prefix('pengajar')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pengajar'])
            ->name('pengajar.dashboard');
    });

// Dashboard Staff
Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'staff'])
            ->name('staff.dashboard');
    });

// Dashboard Admin
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])
            ->name('admin.dashboard');
    });

// Manajemen data LPQ (Admin)
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/lpq')
    ->name('admin.lpq.')
    ->whereUuid(['user', 'santriProgram', 'pengajarPengganti', 'kelasType', 'kelas', 'program', 'lembaga'])
    ->group(function () {
        Route::get('/', [LpqController::class, 'index'])->name('index');

        Route::get('/pengajar-pengganti', [PengajarPenggantiController::class, 'index'])->name('pengajar-pengganti.index');
        Route::post('/pengajar-pengganti', [PengajarPenggantiController::class, 'store'])->name('pengajar-pengganti.store');
        Route::put('/pengajar-pengganti/{pengajarPengganti}', [PengajarPenggantiController::class, 'update'])->name('pengajar-pengganti.update');
        Route::patch('/pengajar-pengganti/{pengajarPengganti}/aktif', [PengajarPenggantiController::class, 'setAktif'])->name('pengajar-pengganti.aktif');
        Route::delete('/pengajar-pengganti/{pengajarPengganti}', [PengajarPenggantiController::class, 'destroy'])->name('pengajar-pengganti.destroy');

        Route::get('/jenis-kelas', [KelasTypeController::class, 'index'])->name('jenis-kelas.index');
        Route::post('/jenis-kelas', [KelasTypeController::class, 'store'])->name('jenis-kelas.store');
        Route::delete('/jenis-kelas/{kelasType}', [KelasTypeController::class, 'destroy'])->name('jenis-kelas.destroy');
        Route::get('/jenis-kelas/{kelasType}', [KelasPengajaranController::class, 'index'])->name('kelas.index');
        Route::post('/jenis-kelas/{kelasType}/kelas', [KelasPengajaranController::class, 'store'])->name('kelas.store');
        Route::put('/kelas/{kelas}', [KelasPengajaranController::class, 'update'])->name('kelas.update');
        Route::patch('/kelas/{kelas}/aktif', [KelasPengajaranController::class, 'setAktif'])->name('kelas.aktif');
        Route::delete('/kelas/{kelas}', [KelasPengajaranController::class, 'destroy'])->name('kelas.destroy');

        Route::get('/program', [ProgramController::class, 'index'])->name('program.index');
        Route::post('/program', [ProgramController::class, 'store'])->name('program.store');
        Route::put('/program/{program}', [ProgramController::class, 'update'])->name('program.update');
        Route::delete('/program/{program}', [ProgramController::class, 'destroy'])->name('program.destroy');

        Route::get('/lembaga', [LembagaController::class, 'index'])->name('lembaga.index');
        Route::get('/lembaga/create', [LembagaController::class, 'create'])->name('lembaga.create');
        Route::post('/lembaga', [LembagaController::class, 'store'])->name('lembaga.store');
        Route::get('/lembaga/{lembaga}', [LembagaController::class, 'show'])->name('lembaga.show');
        Route::get('/lembaga/{lembaga}/edit', [LembagaController::class, 'edit'])->name('lembaga.edit');
        Route::put('/lembaga/{lembaga}', [LembagaController::class, 'update'])->name('lembaga.update');
        Route::delete('/lembaga/{lembaga}', [LembagaController::class, 'destroy'])->name('lembaga.destroy');

        Route::post('/santri/{user}/program', [SantriProgramController::class, 'store'])->name('santri-program.store');
        Route::put('/santri-program/{santriProgram}', [SantriProgramController::class, 'update'])->name('santri-program.update');
        Route::delete('/santri-program/{santriProgram}', [SantriProgramController::class, 'destroy'])->name('santri-program.destroy');

        // Akun per peran: /admin/lpq/{admin|staff|pengajar|santri}
        Route::whereIn('peran', PenggunaController::PERAN)->group(function () {
            Route::get('/{peran}', [PenggunaController::class, 'index'])->name('pengguna.index');
            Route::get('/{peran}/create', [PenggunaController::class, 'create'])->name('pengguna.create');
            Route::post('/{peran}', [PenggunaController::class, 'store'])->name('pengguna.store');
            Route::get('/{peran}/{user}', [PenggunaController::class, 'show'])->name('pengguna.show');
            Route::get('/{peran}/{user}/edit', [PenggunaController::class, 'edit'])->name('pengguna.edit');
            Route::put('/{peran}/{user}', [PenggunaController::class, 'update'])->name('pengguna.update');
            Route::delete('/{peran}/{user}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');
        });
    });

// Tagihan & Pembayaran (Admin)
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/pembayaran')
    ->name('admin.pembayaran.')
    ->whereUuid(['tagihan', 'pembayaran', 'rekening', 'tarif'])
    ->group(function () {
        Route::get('/', [TagihanController::class, 'index'])->name('index');
        Route::get('/tagihan/{tagihan}', [TagihanController::class, 'show'])->name('tagihan.show');
        Route::get('/generate', [GenerateTagihanController::class, 'create'])->name('generate.create');
        Route::post('/generate', [GenerateTagihanController::class, 'store'])->name('generate.store');

        Route::get('/verifikasi', [VerifikasiPembayaranController::class, 'index'])->name('verifikasi.index');
        Route::put('/verifikasi/{pembayaran}', [VerifikasiPembayaranController::class, 'update'])->name('verifikasi.update');

        Route::get('/rekening', [RekeningPembayaranController::class, 'index'])->name('rekening.index');
        Route::post('/rekening', [RekeningPembayaranController::class, 'store'])->name('rekening.store');
        Route::put('/rekening/{rekening}', [RekeningPembayaranController::class, 'update'])->name('rekening.update');
        Route::patch('/rekening/{rekening}/aktif', [RekeningPembayaranController::class, 'setAktif'])->name('rekening.aktif');

        Route::get('/tarif', [TarifPembayaranController::class, 'index'])->name('tarif.index');
        Route::get('/tarif/create', [TarifPembayaranController::class, 'create'])->name('tarif.create');
        Route::post('/tarif', [TarifPembayaranController::class, 'store'])->name('tarif.store');
        Route::get('/tarif/{tarif}/edit', [TarifPembayaranController::class, 'edit'])->name('tarif.edit');
        Route::put('/tarif/{tarif}', [TarifPembayaranController::class, 'update'])->name('tarif.update');
        Route::patch('/tarif/{tarif}/aktif', [TarifPembayaranController::class, 'setAktif'])->name('tarif.aktif');
    });
