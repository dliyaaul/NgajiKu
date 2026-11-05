<?php

namespace App\Http\Controllers;

use App\Http\Requests\TarifRequest;
use App\Models\PembayaranTarif;
use App\Services\Pembayaran\OpsiTarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tarif pembayaran per jenis layanan (admin).
 */
class TarifPembayaranController extends Controller
{
    public function __construct(private OpsiTarif $opsi) {}

    public function index(): Response
    {
        $tarif = PembayaranTarif::query()
            ->with(['kelasType', 'program:id,nama', 'lembaga:id,nama_lembaga', 'santriProgram.santri:id,username'])
            ->orderByDesc('aktif')
            ->latest()
            ->get();

        return Inertia::render('pembayaran/tarif/index', [
            'tarif' => $tarif->map(fn (PembayaranTarif $item) => [
                'id' => $item->id,
                'nama_tarif' => $item->nama_tarif,
                'jenis' => $item->kelasType->jenis()?->value,
                'jenis_label' => $item->kelasType->nama,
                'target' => $item->santriProgram?->santri?->username
                    ?? collect([$item->lembaga?->nama_lembaga, $item->program?->nama])->filter()->implode(' · ')
                    ?: 'Semua santri',
                'nominal' => (float) $item->nominal,
                'satuan' => $item->satuan,
                'berlaku_mulai' => $item->berlaku_mulai->toDateString(),
                'berlaku_sampai' => $item->berlaku_sampai?->toDateString(),
                'aktif' => $item->aktif,
            ]),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null, [
            'nama_tarif' => '',
            'kelas_type_id' => '',
            'program_id' => '',
            'santri_program_id' => '',
            'lembaga_id' => '',
            'nominal' => '',
            'satuan' => '',
            'berlaku_mulai' => now()->toDateString(),
            'berlaku_sampai' => '',
            'aktif' => true,
        ]);
    }

    public function store(TarifRequest $request): RedirectResponse
    {
        PembayaranTarif::create($request->tarifData() + ['created_by' => $request->user()->id]);

        return to_route('admin.pembayaran.tarif.index')->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function edit(PembayaranTarif $tarif): Response
    {
        return $this->form($tarif->id, [
            'nama_tarif' => $tarif->nama_tarif,
            'kelas_type_id' => $tarif->kelas_type_id,
            'program_id' => $tarif->program_id ?? '',
            'santri_program_id' => $tarif->santri_program_id ?? '',
            'lembaga_id' => $tarif->lembaga_id ?? '',
            'nominal' => (string) (float) $tarif->nominal,
            'satuan' => $tarif->satuan,
            'berlaku_mulai' => $tarif->berlaku_mulai->toDateString(),
            'berlaku_sampai' => $tarif->berlaku_sampai?->toDateString() ?? '',
            'aktif' => $tarif->aktif,
        ]);
    }

    public function update(TarifRequest $request, PembayaranTarif $tarif): RedirectResponse
    {
        $tarif->update($request->tarifData());

        return to_route('admin.pembayaran.tarif.index')->with('success', 'Tarif berhasil diperbarui.');
    }

    public function setAktif(Request $request, PembayaranTarif $tarif): RedirectResponse
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $tarif->update(['aktif' => $request->boolean('aktif')]);

        return back()->with('success', $tarif->aktif ? 'Tarif diaktifkan.' : 'Tarif dinonaktifkan.');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function form(?string $tarifId, array $values): Response
    {
        return Inertia::render('pembayaran/tarif/form', [
            'tarifId' => $tarifId,
            'values' => $values,
            'opsi' => $this->opsi->semua(),
        ]);
    }
}
