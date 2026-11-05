<?php

namespace App\Http\Controllers;

use App\Models\KelasPengajaran;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiController extends Controller
{
    /**
     * Pilihan program absensi.
     */
    public function index(): Response
    {
        return Inertia::render('absensi/index', [
            'programs' => AbsensiProgram::cases(),
        ]);
    }

    /**
     * Daftar kelas pengajaran pada program.
     */
    public function kelas(Request $request, AbsensiProgram $program, AbsensiService $absensi): Response
    {
        return Inertia::render('absensi/kelas', [
            'program' => $program,
            'kelasList' => $absensi->getKelasList($program, $request->user())
                ->map(fn (KelasPengajaran $kelas): array => [
                    'id' => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'pengajar' => $kelas->pengajar?->username,
                ]),
        ]);
    }
}
