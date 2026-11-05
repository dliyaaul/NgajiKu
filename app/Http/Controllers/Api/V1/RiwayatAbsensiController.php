<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RingkasanAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiwayatAbsensiController extends Controller
{
    /**
     * Riwayat absensi santri yang login, opsional difilter `?program=bbaq|tahfidz|tarjamah`.
     */
    public function __invoke(Request $request, AbsensiService $absensi, RingkasanAbsensi $ringkasan): JsonResponse
    {
        $request->validate([
            'program' => ['nullable', Rule::enum(AbsensiProgram::class)],
        ]);

        $riwayat = $absensi->getRiwayatSantri(
            $request->user(),
            AbsensiProgram::tryFrom((string) $request->query('program'))
        );

        return response()->json([
            'data' => $riwayat['items'],
            'meta' => [
                'ringkasan' => $ringkasan->hitungStatus($riwayat['items']),
            ],
        ]);
    }
}
