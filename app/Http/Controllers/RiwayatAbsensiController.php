<?php

namespace App\Http\Controllers;

use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RingkasanAbsensi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatAbsensiController extends Controller
{
    /**
     * Riwayat absensi milik santri yang sedang login.
     */
    public function index(Request $request, AbsensiService $absensi, RingkasanAbsensi $ringkasan): Response
    {
        $filter = AbsensiProgram::tryFrom((string) $request->query('program'));
        $riwayat = $absensi->getRiwayatSantri($request->user(), $filter);

        return Inertia::render('santri/riwayat-absensi', [
            'items' => $riwayat['items'],
            'gagal' => $riwayat['gagal'],
            'ringkasan' => $ringkasan->hitungStatus($riwayat['items']),
            'programs' => AbsensiProgram::cases(),
            'filter' => $filter,
        ]);
    }
}
