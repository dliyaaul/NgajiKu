<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\SantriProgramRequest;
use App\Models\SantriProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Pendaftaran santri pada program (dikelola dari halaman detail santri).
 */
class SantriProgramController extends Controller
{
    use HapusDataInduk;

    public function store(SantriProgramRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::Santri, 404);

        $pendaftaran = new SantriProgram($request->pendaftaranData() + ['santri_id' => $user->id]);
        $pendaftaran->alasanPindahKelas = $request->alasan();
        $pendaftaran->save();

        return back()->with('success', 'Santri berhasil didaftarkan ke program.');
    }

    public function update(SantriProgramRequest $request, SantriProgram $santriProgram): RedirectResponse
    {
        $santriProgram->fill($request->pendaftaranData());
        $pindahKelas = $santriProgram->isDirty('kelas_pengajaran_id');
        $santriProgram->alasanPindahKelas = $request->alasan();

        DB::transaction(fn () => $santriProgram->save());

        return back()->with('success', $pindahKelas ? 'Pendaftaran diperbarui dan perpindahan kelas dicatat.' : 'Pendaftaran berhasil diperbarui.');
    }

    public function destroy(SantriProgram $santriProgram): RedirectResponse
    {
        if ($santriProgram->punyaRiwayatTransaksi()) {
            return back()->with('error', 'Pendaftaran ini sudah memiliki absensi atau tagihan sehingga tidak dapat dihapus.');
        }

        return $this->hapusDataInduk(
            $santriProgram,
            'Santri berhasil dikeluarkan dari program.',
            'Pendaftaran ini masih dipakai data lain.',
            fn () => $santriProgram->riwayatKelas()->delete(),
        );
    }
}
