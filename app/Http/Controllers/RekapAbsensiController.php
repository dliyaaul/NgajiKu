<?php

namespace App\Http\Controllers;

use App\Models\KelasPengajaran;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiException;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RekapAbsensiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RekapAbsensiController extends Controller
{
    public function __construct(
        private RekapAbsensiService $rekap,
        private AbsensiService $absensi,
    ) {}

    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'mulai' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d'],
            'program' => ['nullable', 'in:bbaq,tahfidz,tarjamah'],
            'kelas' => ['nullable', 'uuid', 'exists:kelas_pengajaran,id'],
        ]);

        $sampai = CarbonImmutable::parse(
            $filter['sampai'] ?? now()->toDateString()
        )->startOfDay();

        $mulai = CarbonImmutable::parse(
            $filter['mulai'] ?? $sampai->subDays(29)->toDateString()
        )->startOfDay();

        if ($mulai->gt($sampai)) {
            throw ValidationException::withMessages([
                'mulai' => 'Tanggal mulai tidak boleh melebihi tanggal akhir.',
            ]);
        }

        if ($mulai->diffInDays($sampai) > 89) {
            throw ValidationException::withMessages([
                'mulai' => 'Rentang rekap maksimal 90 hari.',
            ]);
        }

        $program = isset($filter['program'])
            ? AbsensiProgram::from($filter['program'])
            : null;

        $kelasId = $filter['kelas'] ?? null;

        $data = $this->rekap->ambil(
            $request->user(),
            $mulai,
            $sampai,
            $program,
            $kelasId,
        );

        // Daftar program yang dapat dipilih.
        $programs = [];

        foreach (AbsensiProgram::cases() as $item) {
            try {
                $kelasProgram = $this->absensi->getKelasList(
                    $item,
                    $request->user()
                );

                $programs[] = [
                    'value' => $item->value,
                    'label' => $item->label(),
                    'jumlah_kelas' => $kelasProgram->count(),
                ];
            } catch (AbsensiException) {
                continue;
            }
        }

        // Daftar kelas mengikuti program dan hak akses pengguna.
        $kelasOptions = collect();

        $programKelas = $program
            ? [$program]
            : AbsensiProgram::cases();

        foreach ($programKelas as $item) {
            try {
                $kelasList = $this->absensi->getKelasList(
                    $item,
                    $request->user()
                );

                foreach ($kelasList as $kelas) {
                    $kelasOptions->push([
                        'id' => $kelas->id,
                        'nama' => $kelas->nama_kelas,
                        'program' => $item->value,
                    ]);
                }
            } catch (AbsensiException) {
                continue;
            }
        }

        return Inertia::render('absensi/rekap', [
            ...$data,

            'programs' => $programs,
            'kelasOptions' => $kelasOptions->values()->all(),

            'filter' => [
                'mulai' => $mulai->toDateString(),
                'sampai' => $sampai->toDateString(),
                'program' => $program?->value,
                'kelas' => $kelasId,
            ],
        ]);
    }
}
