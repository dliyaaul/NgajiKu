<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTagihanRequest;
use App\Services\Pembayaran\PembayaranException;
use App\Services\Pembayaran\TagihanRegulerGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Generate tagihan bulanan layanan Reguler: pratinjau (GET) lalu konfirmasi (POST).
 */
class GenerateTagihanController extends Controller
{
    public function __construct(private TagihanRegulerGenerator $generator) {}

    /**
     * Form periode. Bila periode diisi (query string), tampilkan pratinjau santri yang akan ditagih.
     */
    public function create(Request $request): Response
    {
        $pratinjau = null;
        $galat = null;
        $values = [
            'periode_mulai' => now()->startOfMonth()->toDateString(),
            'periode_selesai' => now()->endOfMonth()->toDateString(),
            'tanggal_jatuh_tempo' => now()->startOfMonth()->addDays(9)->toDateString(),
        ];

        if ($request->hasAny(['periode_mulai', 'periode_selesai'])) {
            $aturan = new GenerateTagihanRequest;
            $values = [...$values, ...$request->validate($aturan->rules(), $aturan->messages(), $aturan->attributes())];

            try {
                $pratinjau = $this->generator->pratinjau(
                    CarbonImmutable::parse($values['periode_mulai']),
                    CarbonImmutable::parse($values['periode_selesai']),
                );
            } catch (PembayaranException $e) {
                $galat = $e->getMessage();
            }
        }

        return Inertia::render('pembayaran/generate', [
            'values' => [...$values, 'tanggal_jatuh_tempo' => $values['tanggal_jatuh_tempo'] ?? ''],
            'pratinjau' => $pratinjau,
            'galat' => $galat,
        ]);
    }

    public function store(GenerateTagihanRequest $request): RedirectResponse
    {
        $hasil = $this->generator->generate($request->mulai(), $request->selesai(), $request->jatuhTempo(), $request->user());

        $pesan = "{$hasil['total_dibuat']} tagihan berhasil dibuat.";

        if ($hasil['total_dilewati_duplikat'] > 0) {
            $pesan .= " {$hasil['total_dilewati_duplikat']} santri dilewati karena sudah memiliki tagihan pada periode ini.";
        }

        return to_route('admin.pembayaran.index')->with('success', $pesan);
    }
}
