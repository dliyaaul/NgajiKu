<?php

namespace App\Http\Controllers;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Pembayaran\TampilanTagihan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar & detail semua tagihan untuk admin.
 */
class TagihanController extends Controller
{
    public function __construct(private TampilanTagihan $tampilan) {}

    public function index(Request $request): Response
    {
        $status = StatusTagihan::tryFrom((string) $request->query('status'));
        $cari = trim((string) $request->query('cari'));

        return Inertia::render('pembayaran/tagihan/index', [
            'mode' => 'admin',
            'tagihan' => $this->tampilan->daftar(Tagihan::query(), $status, $cari, $request),
            'jumlahPerStatus' => $this->tampilan->jumlahPerStatus(Tagihan::query()),
            'filter' => ['status' => $status?->value, 'cari' => $cari],
            'menungguVerifikasi' => Pembayaran::query()->where('status', StatusPembayaran::MenungguVerifikasi)->count(),
        ]);
    }

    public function show(Request $request, Tagihan $tagihan): Response
    {
        return Inertia::render('pembayaran/tagihan/show', [
            'mode' => 'admin',
            'tagihan' => $this->tampilan->detail($tagihan, $request),
            'bisaBayar' => false,
            'rekeningOptions' => [],
        ]);
    }
}
