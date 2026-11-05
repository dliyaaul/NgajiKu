<?php

namespace App\Http\Controllers;

use App\Enums\JenisLayanan;
use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\KelasTypeRequest;
use App\Models\KelasType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jenis kelas (Reguler, Privat, Lembaga) beserta ringkasan kelas & santrinya.
 */
class KelasTypeController extends Controller
{
    use HapusDataInduk;

    public function index(): Response
    {
        $jenisKelas = KelasType::query()
            ->withCount([
                'kelasPengajaran',
                'kelasPengajaran as kelas_aktif_count' => fn ($query) => $query->where('aktif', true),
                'santriPrograms',
            ])
            ->orderBy('nama')
            ->get();

        $terpakai = $jenisKelas->map(fn (KelasType $item) => $item->jenis())->filter();

        return Inertia::render('lpq/jenis-kelas/index', [
            'jenisKelas' => $jenisKelas->map(fn (KelasType $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'jenis' => $item->jenis()?->value,
                'satuan_tarif' => $item->jenis()?->satuanTarif(),
                'jumlah_kelas' => $item->kelas_pengajaran_count,
                'jumlah_kelas_aktif' => $item->kelas_aktif_count,
                'jumlah_santri' => $item->santri_programs_count,
            ]),
            'jenisTersedia' => collect(JenisLayanan::cases())
                ->reject(fn (JenisLayanan $jenis) => $terpakai->contains($jenis))
                ->map(fn (JenisLayanan $jenis) => $jenis->label())
                ->values(),
        ]);
    }

    public function store(KelasTypeRequest $request): RedirectResponse
    {
        KelasType::create(['nama' => $request->validated('nama')]);

        return back()->with('success', 'Jenis kelas berhasil ditambahkan.');
    }

    public function destroy(KelasType $kelasType): RedirectResponse
    {
        return $this->hapusDataInduk(
            $kelasType,
            'Jenis kelas berhasil dihapus.',
            "Jenis kelas {$kelasType->nama} masih dipakai kelas, santri, atau tarif sehingga tidak dapat dihapus.",
        );
    }
}
