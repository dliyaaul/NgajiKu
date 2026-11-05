<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ResolvesAbsensi;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanAbsensiRequest;
use App\Http\Resources\PertemuanResource;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use Illuminate\Http\JsonResponse;

class AbsensiController extends Controller
{
    use ResolvesAbsensi;

    public function __construct(private AbsensiService $absensi) {}

    /**
     * Formulir absensi: pertemuan + seluruh santri anggota kelas dengan status tersimpannya.
     */
    public function show(AbsensiProgram $program, string $pertemuan): JsonResponse
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        return response()->json([
            'data' => [
                'pertemuan' => new PertemuanResource($pertemuanData),
                'santri' => $this->absensi->getFormulirAbsensi($program, $pertemuanData),
            ],
        ]);
    }

    /**
     * Menyimpan absensi. Status "Belum Absen" menghapus absensi santri tersebut.
     */
    public function update(SimpanAbsensiRequest $request, AbsensiProgram $program, string $pertemuan): JsonResponse
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->saveAbsensi($program, $pertemuanData, $request->absensiRows(), $request->user());

        return response()->json([
            'message' => 'Absensi berhasil disimpan.',
            'data' => [
                'santri' => $this->absensi->getFormulirAbsensi($program, $pertemuanData),
            ],
        ]);
    }
}
