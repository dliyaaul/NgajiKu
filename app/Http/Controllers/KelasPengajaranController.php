<?php

namespace App\Http\Controllers;

use App\Enums\JenisLayanan;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\KelasPengajaranRequest;
use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\Program;
use App\Models\RiwayatKelasSantri;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelas pengajaran di dalam satu jenis kelas.
 */
class KelasPengajaranController extends Controller
{
    use HapusDataInduk;

    private const STATUS = ['aktif', 'nonaktif', 'semua'];

    public function index(Request $request, KelasType $kelasType): Response
    {
        $status = in_array($request->query('status'), self::STATUS, true) ? $request->query('status') : 'semua';
        $programId = (string) $request->query('program');
        $cari = trim((string) $request->query('cari'));

        $kelas = $kelasType->kelasPengajaran()
            ->with(['program:id,nama', 'pengajar:id,username', 'lembaga:id,nama_lembaga'])
            ->withCount('santriPrograms')
            ->when($status !== 'semua', fn (Builder $query) => $query->where('aktif', $status === 'aktif'))
            ->when($programId !== '', fn (Builder $query) => $query->where('program_id', $programId))
            ->when($cari !== '', fn (Builder $query) => $query->where('nama_kelas', 'like', "%{$cari}%"))
            ->orderByDesc('aktif')
            ->orderBy('nama_kelas')
            ->get();

        return Inertia::render('lpq/jenis-kelas/kelas', [
            'jenisKelas' => [
                'id' => $kelasType->id,
                'nama' => $kelasType->nama,
                'jenis' => $kelasType->jenis()?->value,
            ],
            'kelas' => $kelas->map(fn (KelasPengajaran $item) => [
                'id' => $item->id,
                'nama_kelas' => $item->nama_kelas,
                'aktif' => $item->aktif,
                'program_id' => $item->program_id,
                'program' => $item->program?->nama,
                'pengajar_id' => $item->pengajar_id,
                'pengajar' => $item->pengajar?->username,
                'lembaga_id' => $item->lembaga_id,
                'lembaga' => $item->lembaga?->nama_lembaga,
                'jumlah_santri' => $item->santri_programs_count,
            ]),
            'filter' => ['status' => $status, 'program' => $programId, 'cari' => $cari],
            'opsi' => [
                'program' => Program::query()->orderBy('nama')->get(['id', 'nama']),
                'pengajar' => User::query()->where('role', UserRole::Pengajar)->orderBy('username')->get(['id', 'username as nama']),
                'lembaga' => $kelasType->jenis() === JenisLayanan::Lembaga
                    ? Lembaga::query()
                        ->leftJoin('kelas_pengajaran', 'kelas_pengajaran.lembaga_id', '=', 'lembaga.id')
                        ->orderBy('nama_lembaga')
                        ->get(['lembaga.id', 'lembaga.nama_lembaga as nama', 'lembaga.program_id', 'kelas_pengajaran.id as kelas_id'])
                    : [],
            ],
        ]);
    }

    public function store(KelasPengajaranRequest $request, KelasType $kelasType): RedirectResponse
    {
        $kelasType->kelasPengajaran()->create($request->kelasData() + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Pengajar kelas yang berganti ikut diperbarui pada pendaftaran santri di kelas tersebut.
     */
    public function update(KelasPengajaranRequest $request, KelasPengajaran $kelas): RedirectResponse
    {
        DB::transaction(function () use ($request, $kelas): void {
            $kelas->update($request->kelasData());

            if ($kelas->wasChanged('pengajar_id')) {
                $kelas->santriPrograms()->update(['pengajar_id' => $kelas->pengajar_id]);
            }
        });

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function setAktif(Request $request, KelasPengajaran $kelas): RedirectResponse
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        $kelas->update(['aktif' => $request->boolean('aktif')]);

        return back()->with('success', $kelas->aktif ? 'Kelas diaktifkan.' : 'Kelas dinonaktifkan.');
    }

    public function destroy(KelasPengajaran $kelas): RedirectResponse
    {
        $adaRiwayat = RiwayatKelasSantri::query()
            ->where(fn (Builder $query) => $query->where('kelas_asal_id', $kelas->id)->orWhere('kelas_tujuan_id', $kelas->id))
            ->exists();

        if ($kelas->sudahDipakai() || $adaRiwayat) {
            return back()->with('error', "Kelas {$kelas->nama_kelas} sudah memiliki santri, pertemuan, atau riwayat. Nonaktifkan saja agar data tetap utuh.");
        }

        return $this->hapusDataInduk($kelas, 'Kelas berhasil dihapus.', "Kelas {$kelas->nama_kelas} masih dipakai data lain.");
    }
}
