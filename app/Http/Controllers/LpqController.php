<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\PengajarPengganti;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Beranda manajemen data LPQ: ringkasan jumlah tiap data induk.
 */
class LpqController extends Controller
{
    public function index(): Response
    {
        $jumlahPeran = User::query()->toBase()->selectRaw('role, count(*) as jumlah')->groupBy('role')->pluck('jumlah', 'role');

        $santriTanpaProgram = User::query()
            ->where('role', UserRole::Santri)
            ->whereDoesntHave('santriPrograms')
            ->count();

        $santriTanpaKelas = SantriProgram::query()->whereNull('kelas_pengajaran_id')->count();

        return Inertia::render('lpq/index', [
            'jumlah' => [
                'admin' => (int) ($jumlahPeran[UserRole::Admin->value] ?? 0),
                'staff' => (int) ($jumlahPeran[UserRole::Staff->value] ?? 0),
                'pengajar' => (int) ($jumlahPeran[UserRole::Pengajar->value] ?? 0),
                'santri' => (int) ($jumlahPeran[UserRole::Santri->value] ?? 0),
                'pengajar_pengganti' => PengajarPengganti::query()->where('aktif', true)->count(),
                'jenis_kelas' => KelasType::query()->count(),
                'kelas_aktif' => KelasPengajaran::query()->where('aktif', true)->count(),
                'program' => Program::query()->count(),
                'lembaga' => Lembaga::query()->count(),
            ],
            'perhatian' => [
                'santri_tanpa_program' => $santriTanpaProgram,
                'pendaftaran_tanpa_kelas' => $santriTanpaKelas,
                'kelas_tanpa_pengajar' => KelasPengajaran::query()->where('aktif', true)->whereNull('pengajar_id')->count(),
            ],
        ]);
    }
}
