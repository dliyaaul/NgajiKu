<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\LembagaRequest;
use App\Models\Lembaga;
use App\Models\PembayaranTarif;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lembaga mitra (sekolah/instansi) yang mengikuti program LPQ.
 */
class LembagaController extends Controller
{
    use HapusDataInduk;

    public function index(Request $request): Response
    {
        $cari = trim((string) $request->query('cari'));
        $programId = (string) $request->query('program');
        $pbm = in_array($request->query('pbm'), LembagaRequest::PBM, true) ? $request->query('pbm') : '';

        $lembaga = Lembaga::query()
            ->with(['program:id,nama', 'kelas.pengajar:id,username'])
            ->withCount(['santriPrograms', 'koordinator'])
            ->when($cari !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nama_lembaga', 'like', "%{$cari}%")
                ->orWhere('alamat', 'like', "%{$cari}%")))
            ->when($programId !== '', fn (Builder $query) => $query->where('program_id', $programId))
            ->when($pbm !== '', fn (Builder $query) => $query->where('pbm_type', $pbm))
            ->orderBy('nama_lembaga')
            ->get();

        $jumlahPbm = Lembaga::query()->toBase()->selectRaw('pbm_type, count(*) as jumlah')->groupBy('pbm_type')->pluck('jumlah', 'pbm_type');

        return Inertia::render('lpq/lembaga/index', [
            'lembaga' => $lembaga->map(fn (Lembaga $item) => [
                'id' => $item->id,
                'nama_lembaga' => $item->nama_lembaga,
                'pbm_type' => $item->pbm_type,
                'alamat' => $item->alamat,
                'program' => $item->program?->nama,
                'kelas' => $item->kelas?->nama_kelas,
                'pengajar' => $item->kelas?->pengajar?->username,
                'jumlah_santri' => $item->santri_programs_count,
                'jumlah_koordinator' => $item->koordinator_count,
            ]),
            'ringkasan' => [
                'total' => (int) $jumlahPbm->sum(),
                'offline' => (int) ($jumlahPbm['Offline'] ?? 0),
                'online' => (int) ($jumlahPbm['Online'] ?? 0),
            ],
            'filter' => ['cari' => $cari, 'program' => $programId, 'pbm' => $pbm],
            'opsi' => ['program' => $this->opsiProgram()],
        ]);
    }

    public function create(): Response
    {
        return $this->form(null, ['nama_lembaga' => '', 'pbm_type' => 'Offline', 'program_id' => '', 'alamat' => '']);
    }

    public function store(LembagaRequest $request): RedirectResponse
    {
        $lembaga = Lembaga::create($request->lembagaData());

        return to_route('admin.lpq.lembaga.show', $lembaga)->with('success', 'Lembaga berhasil ditambahkan.');
    }

    public function show(Lembaga $lembaga): Response
    {
        $lembaga->load([
            'program:id,nama',
            'kelas' => fn ($query) => $query->with('pengajar:id,username', 'kelasType:id,nama'),
            'koordinator' => fn ($query) => $query->orderBy('username'),
        ]);

        $santri = $lembaga->santriPrograms()
            ->with(['santri:id,username,email,no_hp', 'kelasPengajaran:id,nama_kelas'])
            ->get()
            ->sortBy(fn (SantriProgram $item) => mb_strtolower((string) $item->santri?->username))
            ->values();

        return Inertia::render('lpq/lembaga/show', [
            'lembaga' => [
                'id' => $lembaga->id,
                'nama_lembaga' => $lembaga->nama_lembaga,
                'pbm_type' => $lembaga->pbm_type,
                'alamat' => $lembaga->alamat,
                'program' => $lembaga->program?->nama,
                'created_at' => $lembaga->created_at?->toDateString(),
                'kelas' => $lembaga->kelas ? [
                    'id' => $lembaga->kelas->id,
                    'nama_kelas' => $lembaga->kelas->nama_kelas,
                    'aktif' => $lembaga->kelas->aktif,
                    'pengajar' => $lembaga->kelas->pengajar?->username,
                    'kelas_type_id' => $lembaga->kelas->kelas_type_id,
                ] : null,
                'koordinator' => $lembaga->koordinator->map(fn (User $user) => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                ]),
            ],
            'santri' => $santri->map(fn (SantriProgram $item) => [
                'id' => $item->santri_id,
                'username' => $item->santri?->username,
                'email' => $item->santri?->email,
                'no_hp' => $item->santri?->no_hp,
                'kelas' => $item->kelasPengajaran?->nama_kelas,
            ]),
        ]);
    }

    public function edit(Lembaga $lembaga): Response
    {
        return $this->form($lembaga->id, [
            'nama_lembaga' => $lembaga->nama_lembaga,
            'pbm_type' => $lembaga->pbm_type,
            'program_id' => $lembaga->program_id ?? '',
            'alamat' => $lembaga->alamat ?? '',
        ]);
    }

    public function update(LembagaRequest $request, Lembaga $lembaga): RedirectResponse
    {
        $lembaga->update($request->lembagaData());

        return to_route('admin.lpq.lembaga.show', $lembaga)->with('success', 'Data lembaga berhasil diperbarui.');
    }

    /**
     * Kelas & pendaftaran memakai SET NULL ke lembaga, sehingga penghapusan diam-diam akan
     * merusak aturan kelas Lembaga. Karena itu lembaga yang sudah dipakai ditolak.
     */
    public function destroy(Lembaga $lembaga): RedirectResponse
    {
        $dipakai = $lembaga->kelasPengajaran()->exists()
            || $lembaga->santriPrograms()->exists()
            || PembayaranTarif::query()->where('lembaga_id', $lembaga->id)->exists()
            || Tagihan::query()->where('lembaga_id', $lembaga->id)->exists();

        if ($dipakai) {
            return back()->with('error', "{$lembaga->nama_lembaga} masih memiliki kelas, santri, tarif, atau tagihan sehingga tidak dapat dihapus.");
        }

        $response = $this->hapusDataInduk($lembaga, 'Lembaga berhasil dihapus.', "{$lembaga->nama_lembaga} masih dipakai data lain.");

        return $lembaga->exists ? $response : to_route('admin.lpq.lembaga.index')->with('success', 'Lembaga berhasil dihapus.');
    }

    /**
     * @param  array<string, string>  $values
     */
    private function form(?string $lembagaId, array $values): Response
    {
        return Inertia::render('lpq/lembaga/form', [
            'lembagaId' => $lembagaId,
            'values' => $values,
            'opsi' => ['program' => $this->opsiProgram(), 'pbm' => LembagaRequest::PBM],
        ]);
    }

    /**
     * @return Collection<int, Program>
     */
    private function opsiProgram(): Collection
    {
        return Program::query()->orderBy('nama')->get(['id', 'nama']);
    }
}
