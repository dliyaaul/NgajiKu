<?php

namespace App\Http\Controllers;

use App\Enums\StatusTagihan;
use App\Models\Tagihan;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RingkasanAbsensi;
use App\Services\Dashboard\RingkasanDashboardAdmin;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private RingkasanAbsensi $ringkasan) {}

    public function admin(
        Request $request,
        RingkasanDashboardAdmin $dashboard
    ): Response {
        $dataDashboard = $dashboard->ambil();

        return Inertia::render('dashboard/admin', [
            // Statistik akademik, keuangan, dan aktivitas terbaru.
            ...$dataDashboard,

            // Ringkasan absensi yang sudah ada.
            ...$this->ringkasan->untukPengelola($request->user()),

            // Mempertahankan kompatibilitas dengan PengingatPembayaran.
            'pembayaranMenunggu' => $dataDashboard['keuangan']['menungguVerifikasi']['jumlah'],
        ]);
    }

    public function pengajar(Request $request): Response
    {
        return Inertia::render('dashboard/pengajar', $this->ringkasan->untukPengelola($request->user()));
    }

    public function santri(Request $request, AbsensiService $absensi): Response
    {
        $riwayat = $absensi->getRiwayatSantri($request->user());
        $belumDibayar = Tagihan::query()
            ->terlihatOleh($request->user())
            ->whereIn('status', [StatusTagihan::BelumBayar, StatusTagihan::Ditolak])
            ->toBase()
            ->selectRaw('count(*) as jumlah, coalesce(sum(total), 0) as total')
            ->first();

        return Inertia::render('dashboard/santri', [
            ...$this->ringkasan->untukSantri($riwayat['items']),
            'gagal' => $riwayat['gagal'],
            'tagihanBelumDibayar' => ['jumlah' => (int) $belumDibayar->jumlah, 'total' => (float) $belumDibayar->total],
        ]);
    }

    public function staff(): Response
    {
        return Inertia::render('dashboard/staff');
    }
}
