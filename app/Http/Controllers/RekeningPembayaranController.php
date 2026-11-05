<?php

namespace App\Http\Controllers;

use App\Http\Requests\RekeningRequest;
use App\Http\Resources\RekeningResource;
use App\Models\PembayaranRekening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekening tujuan transfer (admin).
 */
class RekeningPembayaranController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('pembayaran/rekening', [
            'rekening' => RekeningResource::collection(
                PembayaranRekening::query()->orderByDesc('aktif')->orderBy('urutan')->orderBy('nama_bank')->get()
            )->resolve($request),
        ]);
    }

    public function store(RekeningRequest $request): RedirectResponse
    {
        PembayaranRekening::create($request->rekeningData() + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function update(RekeningRequest $request, PembayaranRekening $rekening): RedirectResponse
    {
        $rekening->update($request->rekeningData());

        return back()->with('success', 'Rekening berhasil diperbarui.');
    }

    public function setAktif(Request $request, PembayaranRekening $rekening): RedirectResponse
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $rekening->update(['aktif' => $request->boolean('aktif')]);

        return back()->with('success', $rekening->aktif
            ? "Rekening {$rekening->nama_bank} diaktifkan."
            : "Rekening {$rekening->nama_bank} dinonaktifkan dan tidak lagi ditawarkan kepada pembayar.");
    }
}
