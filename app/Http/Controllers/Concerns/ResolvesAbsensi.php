<?php

namespace App\Http\Controllers\Concerns;

use App\Models\KelasPengajaran;
use App\Models\Pertemuan;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use Illuminate\Support\Facades\Gate;

/**
 * Mencari kelas/pertemuan pada program (404 bila tidak ada) lalu memastikan
 * pengguna boleh mengelolanya (403). Dipakai controller web & API.
 *
 * @property AbsensiService $absensi
 */
trait ResolvesAbsensi
{
    private function resolveKelas(AbsensiProgram $program, ?string $kelasId): KelasPengajaran
    {
        $kelas = $this->absensi->findKelasOrFail($program, $kelasId);

        Gate::authorize('kelolaAbsensi', $kelas);

        return $kelas;
    }

    /**
     * @return array{0: Pertemuan, 1: KelasPengajaran}
     */
    private function resolvePertemuan(AbsensiProgram $program, string $pertemuanId): array
    {
        $pertemuan = $this->absensi->findPertemuanOrFail($program, $pertemuanId);

        return [$pertemuan, $this->resolveKelas($program, $pertemuan->kelas_pengajaran_id)];
    }
}
