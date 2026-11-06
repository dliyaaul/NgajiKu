<?php

namespace App\Http\Controllers;

use App\Enums\StatusTagihan;
use App\Models\Tagihan;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RingkasanAbsensi;
use App\Services\Dashboard\RingkasanDashboardAdmin;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private RingkasanAbsensi $ringkasan
    ) {}

    /**
     * Dashboard Admin.
     *
     * Menampilkan statistik akademik, keuangan,
     * aktivitas terbaru, dan ringkasan absensi.
     */
    public function admin(
        Request $request,
        RingkasanDashboardAdmin $dashboard
    ): Response {
        $dataDashboard = $dashboard->ambil();

        [$mulai, $sampai, $program] = $this->filterGrafik($request);

        return Inertia::render('dashboard/admin', [
            ...$dataDashboard,

            ...$this->ringkasan->untukPengelola(
                $request->user(),
                $mulai,
                $sampai,
                $program,
            ),

            // Kompatibilitas dengan PengingatPembayaran.
            'pembayaranMenunggu' =>
            $dataDashboard['keuangan']['menungguVerifikasi']['jumlah'],
        ]);
    }

    /**
     * Dashboard Pengajar.
     *
     * Data grafik dan ringkasan hanya menampilkan
     * kelas yang diampu oleh pengajar terkait.
     */
    public function pengajar(Request $request): Response
    {
        [$mulai, $sampai, $program] = $this->filterGrafik($request);

        return Inertia::render(
            'dashboard/pengajar',
            $this->ringkasan->untukPengelola(
                $request->user(),
                $mulai,
                $sampai,
                $program,
            )
        );
    }

    /**
     * Dashboard Santri.
     */
    public function santri(
        Request $request,
        AbsensiService $absensi
    ): Response {
        $riwayat = $absensi->getRiwayatSantri($request->user());

        $belumDibayar = Tagihan::query()
            ->terlihatOleh($request->user())
            ->whereIn('status', [
                StatusTagihan::BelumBayar,
                StatusTagihan::Ditolak,
            ])
            ->toBase()
            ->selectRaw(
                'count(*) as jumlah, coalesce(sum(total), 0) as total'
            )
            ->first();

        return Inertia::render('dashboard/santri', [
            ...$this->ringkasan->untukSantri($riwayat['items']),

            'gagal' => $riwayat['gagal'],

            'tagihanBelumDibayar' => [
                'jumlah' => (int) $belumDibayar->jumlah,
                'total' => (float) $belumDibayar->total,
            ],
        ]);
    }

    /**
     * Dashboard Staff.
     */
    public function staff(): Response
    {
        return Inertia::render('dashboard/staff');
    }

    /**
     * Mengambil dan memvalidasi filter grafik absensi.
     *
     * Parameter URL:
     * - mulai: YYYY-MM-DD
     * - sampai: YYYY-MM-DD
     * - program: bbaq | tahfidz | tarjamah
     *
     * Default:
     * - 30 hari terakhir
     * - Semua program
     *
     * @return array{
     *     0: CarbonImmutable,
     *     1: CarbonImmutable,
     *     2: ?AbsensiProgram
     * }
     */
    private function filterGrafik(Request $request): array
    {
        $filter = $request->validate([
            'mulai' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'sampai' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'program' => [
                'nullable',
                'in:bbaq,tahfidz,tarjamah',
            ],
        ]);

        // Tanggal akhir default hari ini.
        $sampai = CarbonImmutable::parse(
            $filter['sampai'] ?? now()->toDateString()
        )->startOfDay();

        // Tanggal mulai default 29 hari sebelum tanggal akhir.
        // Total periode menjadi 30 hari kalender.
        $mulai = CarbonImmutable::parse(
            $filter['mulai']
                ?? $sampai->subDays(29)->toDateString()
        )->startOfDay();

        // Validasi tanggal mulai tidak boleh lebih besar dari tanggal akhir.
        if ($mulai->gt($sampai)) {
            throw ValidationException::withMessages([
                'mulai' => 'Tanggal mulai tidak boleh melebihi tanggal akhir.',
            ]);
        }

        // Maksimal 90 hari kalender (termasuk tanggal awal dan akhir).
        if ($mulai->diffInDays($sampai) > 89) {
            throw ValidationException::withMessages([
                'mulai' => 'Rentang grafik maksimal 90 hari.',
            ]);
        }

        // Konversi string program menjadi enum.
        $program = isset($filter['program'])
            ? AbsensiProgram::from($filter['program'])
            : null;

        return [
            $mulai,
            $sampai,
            $program,
        ];
    }
}
