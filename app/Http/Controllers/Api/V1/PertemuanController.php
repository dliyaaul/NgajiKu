<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ResolvesAbsensi;
use App\Http\Controllers\Controller;
use App\Http\Requests\PertemuanRequest;
use App\Http\Resources\PertemuanResource;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PertemuanController extends Controller
{
    use ResolvesAbsensi;

    public function __construct(private AbsensiService $absensi) {}

    /**
     * Pertemuan pada kelas, terbaru lebih dulu, beserta rekap status absensi.
     */
    public function index(AbsensiProgram $program, string $kelas): AnonymousResourceCollection
    {
        $kelasData = $this->resolveKelas($program, $kelas);

        return PertemuanResource::collection($this->absensi->getPertemuanList($program, $kelasData));
    }

    public function store(PertemuanRequest $request, AbsensiProgram $program, string $kelas): JsonResponse
    {
        $kelasData = $this->resolveKelas($program, $kelas);

        $pertemuan = $this->absensi->createPertemuan($program, $kelasData, $request->pertemuanData(), $request->user());

        return (new PertemuanResource($pertemuan->load(['pengajar:id,username', 'pengganti:id,nama'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AbsensiProgram $program, string $pertemuan): PertemuanResource
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        return new PertemuanResource($pertemuanData);
    }

    public function update(PertemuanRequest $request, AbsensiProgram $program, string $pertemuan): PertemuanResource
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->updatePertemuan($program, $pertemuanData, $request->pertemuanData());

        return new PertemuanResource($pertemuanData->refresh()->load(['pengajar:id,username', 'pengganti:id,nama']));
    }

    public function destroy(AbsensiProgram $program, string $pertemuan): Response
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->deletePertemuan($pertemuanData);

        return response()->noContent();
    }
}
