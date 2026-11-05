<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\PengajarPenggantiRequest;
use App\Models\PengajarPengganti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengajar pengganti dari luar. Yang pernah mengajar sebaiknya dinonaktifkan, bukan dihapus,
 * agar riwayat pertemuan tetap menyebut namanya.
 */
class PengajarPenggantiController extends Controller
{
    use HapusDataInduk;

    private const STATUS = ['aktif', 'nonaktif', 'semua'];

    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), self::STATUS, true) ? $request->query('status') : 'aktif';
        $cari = trim((string) $request->query('cari'));

        $pengganti = PengajarPengganti::query()
            ->withCount(['bbaqPertemuan', 'tahfidzPertemuan', 'tarjamahPertemuan'])
            ->when($status !== 'semua', fn (Builder $query) => $query->where('aktif', $status === 'aktif'))
            ->when($cari !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_hp', 'like', "%{$cari}%")))
            ->orderBy('nama')
            ->get();

        $jumlah = PengajarPengganti::query()->toBase()->selectRaw('aktif, count(*) as jumlah')->groupBy('aktif')->pluck('jumlah', 'aktif');

        return Inertia::render('lpq/pengajar-pengganti/index', [
            'pengganti' => $pengganti->map(fn (PengajarPengganti $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'no_hp' => $item->no_hp,
                'aktif' => $item->aktif,
                'jumlah_pertemuan' => $item->bbaq_pertemuan_count + $item->tahfidz_pertemuan_count + $item->tarjamah_pertemuan_count,
            ]),
            'jumlahPerStatus' => [
                'aktif' => (int) ($jumlah[1] ?? 0),
                'nonaktif' => (int) ($jumlah[0] ?? 0),
                'semua' => (int) $jumlah->sum(),
            ],
            'filter' => ['status' => $status, 'cari' => $cari],
        ]);
    }

    public function store(PengajarPenggantiRequest $request): RedirectResponse
    {
        PengajarPengganti::create($request->penggantiData() + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Pengajar pengganti berhasil ditambahkan.');
    }

    public function update(PengajarPenggantiRequest $request, PengajarPengganti $pengajarPengganti): RedirectResponse
    {
        $pengajarPengganti->update($request->penggantiData());

        return back()->with('success', 'Data pengajar pengganti berhasil diperbarui.');
    }

    public function setAktif(Request $request, PengajarPengganti $pengajarPengganti): RedirectResponse
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $pengajarPengganti->update(['aktif' => $request->boolean('aktif')]);

        return back()->with('success', $pengajarPengganti->aktif ? 'Pengajar pengganti diaktifkan.' : 'Pengajar pengganti dinonaktifkan.');
    }

    public function destroy(PengajarPengganti $pengajarPengganti): RedirectResponse
    {
        $pernahMengajar = $pengajarPengganti->bbaqPertemuan()->exists()
            || $pengajarPengganti->tahfidzPertemuan()->exists()
            || $pengajarPengganti->tarjamahPertemuan()->exists();

        if ($pernahMengajar) {
            return back()->with('error', "{$pengajarPengganti->nama} sudah tercatat di pertemuan. Nonaktifkan saja agar riwayat tetap utuh.");
        }

        return $this->hapusDataInduk($pengajarPengganti, 'Pengajar pengganti berhasil dihapus.', 'Pengajar pengganti masih dipakai data lain.');
    }
}
