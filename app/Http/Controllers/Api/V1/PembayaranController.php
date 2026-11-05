<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Http\Requests\AjukanPembayaranRequest;
use App\Http\Requests\VerifikasiPembayaranRequest;
use App\Http\Resources\PembayaranResource;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Pembayaran\PembayaranService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PembayaranController extends Controller
{
    public function __construct(private PembayaranService $pembayaran) {}

    /**
     * Daftar pembayaran untuk admin. Bawaan: yang menunggu verifikasi, terlama lebih dulu.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', Rule::enum(StatusPembayaran::class)]]);

        $status = StatusPembayaran::tryFrom((string) $request->query('status')) ?? StatusPembayaran::MenungguVerifikasi;

        return PembayaranResource::collection(
            Pembayaran::query()
                ->with(['rekeningTujuan', 'tagihan' => fn ($query) => $query->denganPembayar()])
                ->where('status', $status)
                ->orderBy('created_at', $status === StatusPembayaran::MenungguVerifikasi ? 'asc' : 'desc')
                ->paginate(20)
                ->withQueryString()
        );
    }

    public function show(Pembayaran $pembayaran): PembayaranResource
    {
        return new PembayaranResource($pembayaran->load(['rekeningTujuan', 'log', 'tagihan' => fn ($query) => $query->denganPembayar()]));
    }

    /**
     * Pemilik tagihan mengirim bukti transfer (multipart/form-data, field `bukti_transfer`).
     */
    public function store(AjukanPembayaranRequest $request, Tagihan $tagihan): JsonResponse
    {
        $pembayaran = $this->pembayaran->ajukan($tagihan, $request->user(), $request->pembayaranData(), $request->file('bukti_transfer'));

        return (new PembayaranResource($pembayaran->load(['rekeningTujuan', 'log'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Admin menerima atau menolak pembayaran (`status=diterima|ditolak`, `catatan` wajib saat ditolak).
     */
    public function verifikasi(VerifikasiPembayaranRequest $request, Pembayaran $pembayaran): PembayaranResource
    {
        $pembayaran = $this->pembayaran->verifikasi($pembayaran, $request->user(), $request->statusBaru(), $request->validated('catatan'));

        return new PembayaranResource($pembayaran->load(['rekeningTujuan', 'log', 'tagihan' => fn ($query) => $query->denganPembayar()]));
    }
}
