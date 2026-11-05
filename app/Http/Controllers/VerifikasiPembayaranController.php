<?php

namespace App\Http\Controllers;

use App\Enums\StatusPembayaran;
use App\Http\Requests\VerifikasiPembayaranRequest;
use App\Http\Resources\PembayaranResource;
use App\Models\Pembayaran;
use App\Services\Pembayaran\PembayaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Antrean verifikasi pembayaran untuk admin.
 */
class VerifikasiPembayaranController extends Controller
{
    public function index(Request $request): Response
    {
        $status = StatusPembayaran::tryFrom((string) $request->query('status')) ?? StatusPembayaran::MenungguVerifikasi;
        $menunggu = $status === StatusPembayaran::MenungguVerifikasi;

        $halaman = Pembayaran::query()
            ->with(['rekeningTujuan', 'tagihan' => fn ($query) => $query->denganPembayar()])
            ->where('status', $status)
            ->orderBy($menunggu ? 'created_at' : 'diverifikasi_at', $menunggu ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $jumlah = Pembayaran::query()->toBase()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return Inertia::render('pembayaran/verifikasi', [
            'pembayaran' => PembayaranResource::collection($halaman->getCollection())->resolve($request),
            'pagination' => [
                'halaman' => $halaman->currentPage(),
                'total_halaman' => $halaman->lastPage(),
                'total' => $halaman->total(),
                'sebelumnya' => $halaman->previousPageUrl(),
                'berikutnya' => $halaman->nextPageUrl(),
            ],
            'filter' => $status->value,
            'jumlahPerStatus' => collect(StatusPembayaran::cases())
                ->mapWithKeys(fn (StatusPembayaran $item) => [$item->value => (int) ($jumlah[$item->value] ?? 0)]),
        ]);
    }

    public function update(VerifikasiPembayaranRequest $request, Pembayaran $pembayaran, PembayaranService $service): RedirectResponse
    {
        $status = $request->statusBaru();

        $service->verifikasi($pembayaran, $request->user(), $status, $request->validated('catatan'));

        return back()->with('success', $status === StatusPembayaran::Diterima
            ? "Pembayaran {$pembayaran->nomor_pembayaran} diterima. Tagihan ditandai lunas."
            : "Pembayaran {$pembayaran->nomor_pembayaran} ditolak. Pembayar dapat mengirim ulang bukti.");
    }
}
