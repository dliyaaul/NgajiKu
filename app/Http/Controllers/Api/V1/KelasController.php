<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\KelasPengajaranResource;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\DaftarSurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KelasController extends Controller
{
    public function __construct(private AbsensiService $absensi) {}

    /**
     * Program yang memiliki fitur absensi.
     */
    public function programs(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (AbsensiProgram $program) => [
                'key' => $program->value,
                'label' => $program->label(),
                'punya_hafalan' => $program->hasHafalan(),
            ], AbsensiProgram::cases()),
        ]);
    }

    /**
     * Kelas aktif pada program (pengajar hanya kelasnya sendiri).
     */
    public function index(Request $request, AbsensiProgram $program): AnonymousResourceCollection
    {
        return KelasPengajaranResource::collection($this->absensi->getKelasList($program, $request->user()));
    }

    /**
     * Pilihan untuk formulir pertemuan & absensi.
     */
    public function opsi(AbsensiProgram $program): JsonResponse
    {
        return response()->json([
            'data' => [
                'metode' => AbsensiProgram::METODE,
                'status' => AbsensiProgram::STATUSES,
                'jenis_tahfidz' => $program->hasHafalan() ? AbsensiProgram::JENIS_TAHFIDZ : [],
                'daftar_surat' => $program->hasHafalan() ? DaftarSurat::daftar() : [],
                'pengajar' => $this->absensi->getPengajarOptions($program),
                'pengganti' => $this->absensi->getPenggantiOptions(),
            ],
        ]);
    }
}
