<?php

namespace App\Http\Controllers;

use App\Enums\StatusTagihan;
use App\Http\Requests\AjukanPembayaranRequest;
use App\Http\Resources\RekeningResource;
use App\Models\PembayaranRekening;
use App\Models\Tagihan;
use App\Services\Pembayaran\PembayaranService;
use App\Services\Pembayaran\TampilanTagihan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tagihan milik santri yang login beserta pengajuan pembayarannya.
 */
class TagihanSantriController extends Controller
{
    public function __construct(private TampilanTagihan $tampilan) {}

    public function index(Request $request): Response
    {
        $status = StatusTagihan::tryFrom((string) $request->query('status'));
        $milikSaya = Tagihan::query()->terlihatOleh($request->user());

        return Inertia::render('pembayaran/tagihan/index', [
            'mode' => 'santri',
            'tagihan' => $this->tampilan->daftar($milikSaya, $status, '', $request),
            'jumlahPerStatus' => $this->tampilan->jumlahPerStatus($milikSaya),
            'filter' => ['status' => $status?->value, 'cari' => ''],
            'menungguVerifikasi' => 0,
        ]);
    }

    public function show(Request $request, Tagihan $tagihan): Response
    {
        Gate::authorize('view', $tagihan);

        $bisaBayar = Gate::allows('bayar', $tagihan);

        return Inertia::render('pembayaran/tagihan/show', [
            'mode' => 'santri',
            'tagihan' => $this->tampilan->detail($tagihan, $request),
            'bisaBayar' => $bisaBayar,
            'rekeningOptions' => $bisaBayar
                ? RekeningResource::collection(PembayaranRekening::query()->aktifUrut()->get())->resolve($request)
                : [],
        ]);
    }

    public function bayar(AjukanPembayaranRequest $request, Tagihan $tagihan, PembayaranService $pembayaran): RedirectResponse
    {
        $pembayaran->ajukan($tagihan, $request->user(), $request->pembayaranData(), $request->file('bukti_transfer'));

        return to_route('santri.tagihan.show', $tagihan)
            ->with('success', 'Bukti pembayaran terkirim. Admin akan memverifikasinya segera.');
    }
}
